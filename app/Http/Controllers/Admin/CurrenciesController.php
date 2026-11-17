<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\ValidationException;

class CurrenciesController extends Controller
{
    public function index()
    {
        $currencies = Currency::query()->orderByDesc('is_default')->orderByDesc('is_active')->orderBy('id')->get();
        $used = $this->usedCurrencyIds();

        return page('admin.currencies.index', compact('currencies', 'used'));
    }

    /** Currencies that money was recorded in: they can be switched off but not deleted. */
    private function usedCurrencyIds(): array
    {
        $ids = [];
        foreach (['transactions' => ['currency_id'], 'account_balances' => ['currency_id'], 'exchanges' => ['from_currency_id', 'to_currency_id'],
            'remittances' => ['currency_id'], 'expenses' => ['currency_id']] as $table => $columns) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }
            foreach ($columns as $column) {
                if (\Illuminate\Support\Facades\Schema::hasColumn($table, $column)) {
                    $ids = array_merge($ids, DB::table($table)->distinct()->pluck($column)->all());
                }
            }
        }

        return array_values(array_unique(array_map('intval', array_filter($ids))));
    }

    public function fetch(Request $request)
    {
        $query = Currency::query();

        return DataTables::of($query)
            ->addColumn('name_with_flag', function ($currency) {
                $flag = $currency->flag ? asset($currency->flag) : asset('assets/flags/unknown.svg');
                return '<div class="d-flex align-items-center gap-2">'
                    . '<img src="' . $flag . '" width="24" height="18">'
                    . '<strong>' . e($currency->name) . '</strong>'
                    . '<span class="text-muted">(' . e($currency->code) . ')</span>'
                    . '</div>';
            })
            ->addColumn('symbol', function ($currency) {
                return $currency->symbol ?? '-';
            })
            ->addColumn('exchange_rate', function ($currency) {
                return rtrim(rtrim(number_format((float) $currency->exchange_rate, 2), '0'), '.');
            })
            ->addColumn('status', function ($currency) {
                $active = $currency->is_active ? '<span class="badge bg-success">' . __('administration.active') . '</span>' : '<span class="badge bg-danger">' . __('administration.inactive') . '</span>';
                $default = $currency->is_default ? '<span class="badge bg-warning ms-1">' . __('administration.default') . '</span>' : '';
                return $active . $default;
            })
            ->addColumn('actions', function ($currency) {

                $auth = auth()->user();
                $html = '<div class="d-flex gap-1">';

                if ($auth->can('update currencies')) {
                    $html .= '
                    <button class="btn btn-sm btn-outline-primary btn-edit-currency"
                            data-id="' . $currency->id . '"
                            title="' . __('ui.blade_edit') . '">
                        <i class="bi bi-pencil"></i>
                    </button>';
                }

                if ($auth->can('delete currencies')) {
                    $html .= '
                    <button class="btn btn-sm btn-outline-danger btn-delete-currency"
                            data-id="' . $currency->id . '"
                            title="' . __('ui.delete') . '">
                        <i class="bi bi-trash"></i>
                    </button>';
                }

                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['name_with_flag', 'status', 'actions'])
            ->make(true);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        try {

            $code = strtoupper(trim($request->code));

            $request->merge([
                'code' => $code,
                'is_default' => $request->boolean('is_default'),
                'is_active' => $request->boolean('is_active'),
            ]);

            /**
             * IMPORTANT:
             * USD should always be 1 before validation.
             */
            if ($code === 'USD') {
                $request->merge([
                    'exchange_rate' => 1,
                ]);
            }

            if (!$request->filled('exchange_rate')) {
                foreach ($request->all() as $key => $value) {
                    if (str_starts_with($key, 'exchange_rate_')) {
                        $request->merge([
                            'exchange_rate' => $value,
                        ]);
                        break;
                    }
                }
            }
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'code' => 'required|string|max:10|unique:currencies,code',
                'symbol' => 'nullable|string|max:10',
                'country' => 'nullable|string|max:100',
                'flag' => 'nullable|string|max:255',
                'exchange_rate' => 'required|numeric|min:0',
                'is_default' => 'boolean',
                'is_active' => 'boolean',
            ]);

            DB::beginTransaction();

            $validated['code'] = strtoupper($validated['code']);

            /**
             * Double safety:
             * Even if someone submits USD rate manually, force it to 1.
             */
            if ($validated['code'] === 'USD') {
                $validated['exchange_rate'] = 1;
            }

            $validated['flag'] = 'assets/flags/' . strtolower(substr($validated['code'], 0, 2)) . '.svg';

            if ($validated['is_default']) {
                Currency::where('is_default', true)->update(['is_default' => false]);
            }

            $currency = Currency::create($validated);

            /**
             * Sync non-USD currencies into exchange_rates table.
             */
            if ($currency->code !== 'USD') {

                $usd = Currency::where('code', 'USD')->first();

                if (!$usd) {
                    DB::rollBack();

                    return back()
                        ->withInput()
                        ->with('error', __('administration.usd_missing'));
                }

                ExchangeRate::updateOrCreate(
                    [
                        'date' => now()->toDateString(),
                        'from_currency_id' => $usd->id,
                        'to_currency_id' => $currency->id,
                    ],
                    [
                        'rate' => $currency->exchange_rate,
                        'created_by' => auth()->id(),
                    ]
                );
            }

            DB::commit();

            logActivity(
                $currency,
                "واحد پول ایجاد شد: {$currency->name} ({$currency->code})",
                [
                    'currency_id' => $currency->id,
                    'name' => $currency->name,
                    'code' => $currency->code,
                    'exchange_rate' => $currency->exchange_rate
                ],
                'created'
            );

            return redirect()
                ->route('admin.currencies.index')
                ->with('success', __('administration.currency_created'));
        } catch (ValidationException $e) {

            Log::error('Currency store validation failed', [
                'errors' => $e->errors(),
                'request' => $request->all(),
            ]);

            return back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', collect($e->errors())->flatten()->first());
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Currency store failed', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return back()
                ->withInput()
                ->with('error', __('administration.store_failed'));
        }
    }

    public function edit($id)
    {
        $currency = Currency::findOrFail($id);

        return response()->json([
            'id' => $currency->id,
            'name' => $currency->name,
            'code' => $currency->code,
            'symbol' => $currency->symbol,
            'country' => $currency->country,
            'exchange_rate' => $currency->exchange_rate,
            'is_default' => $currency->is_default,
            'is_active' => $currency->is_active,
            'flag' => $currency->flag,
        ]);
    }

    public function update(Request $request, Currency $currency)
    {
        try {

            $code = strtoupper(trim($request->code));

            $request->merge([
                'code' => $code,
                'is_default' => $request->boolean('is_default'),
                'is_active' => $request->boolean('is_active'),
            ]);

            /**
             * IMPORTANT:
             * USD should always be 1 before validation.
             */
            if ($code === 'USD') {
                $request->merge([
                    'exchange_rate' => 1,
                ]);
            }

            if (!$request->filled('exchange_rate')) {
                foreach ($request->all() as $key => $value) {
                    if (str_starts_with($key, 'exchange_rate_')) {
                        $request->merge([
                            'exchange_rate' => $value,
                        ]);
                        break;
                    }
                }
            }
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'code' => 'required|string|max:10|unique:currencies,code,' . $currency->id,
                'symbol' => 'nullable|string|max:10',
                'country' => 'nullable|string|max:100',
                'flag' => 'nullable|string|max:255',
                'exchange_rate' => 'required|numeric|min:0',
                'is_default' => 'boolean',
                'is_active' => 'boolean',
            ]);

            DB::beginTransaction();

            $original = $currency->getOriginal();

            $validated['code'] = strtoupper($validated['code']);

            /**
             * Double safety:
             * Even if someone submits USD rate manually, force it to 1.
             */
            if ($validated['code'] === 'USD') {
                $validated['exchange_rate'] = 1;
            }

            $validated['flag'] = 'assets/flags/' . strtolower(substr($validated['code'], 0, 2)) . '.svg';

            if ($validated['is_default']) {
                Currency::where('is_default', true)
                    ->where('id', '!=', $currency->id)
                    ->update(['is_default' => false]);
            }

            $currency->update($validated);

            /**
             * Sync non-USD currencies into exchange_rates table.
             */
            if ($currency->code !== 'USD') {

                $usd = Currency::where('code', 'USD')->first();

                if (!$usd) {
                    DB::rollBack();

                    return back()
                        ->withInput()
                        ->with('error', __('administration.usd_missing'));
                }

                ExchangeRate::updateOrCreate(
                    [
                        'date' => now()->toDateString(),
                        'from_currency_id' => $usd->id,
                        'to_currency_id' => $currency->id,
                    ],
                    [
                        'rate' => $currency->exchange_rate,
                        'created_by' => auth()->id(),
                    ]
                );
            }

            DB::commit();

            $labels = [
                'name' => 'نام',
                'code' => 'کد',
                'symbol' => 'سمبول',
                'country' => 'کشور',
                'exchange_rate' => 'نرخ تبدیل',
                'is_default' => 'پیش فرض',
                'is_active' => 'وضعیت فعال'
            ];

            $changes = [];

            foreach ($currency->getChanges() as $field => $newValue) {

                if ($field === 'updated_at') {
                    continue;
                }

                $oldValue = $original[$field] ?? null;

                if ($oldValue != $newValue) {
                    $label = $labels[$field] ?? $field;
                    $changes[] = "{$label}: {$oldValue} → {$newValue}";
                }
            }

            if (!empty($changes)) {

                $description = "واحد پول بروزرسانی شد (" . implode(' | ', $changes) . ")";

                logActivity(
                    $currency,
                    $description,
                    [
                        'currency_id' => $currency->id,
                        'changes' => $changes
                    ],
                    'updated'
                );
            }

            return redirect()
                ->route('admin.currencies.index')
                ->with('success', __('administration.currency_updated'));
        } catch (ValidationException $e) {

            Log::error('Currency update validation failed', [
                'errors' => $e->errors(),
                'request' => $request->all(),
                'currency_id' => $currency->id,
            ]);

            return back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', collect($e->errors())->flatten()->first());
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Currency update failed', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
                'currency_id' => $currency->id,
            ]);

            return back()
                ->withInput()
                ->with('error', __('administration.update_failed'));
        }
    }

    public function destroy(Currency $currency)
    {
        if (in_array($currency->id, $this->usedCurrencyIds(), true)) {
            return done(__('This currency has records, so it cannot be deleted. Switch it off instead.'), null, false);
        }
        if ($currency->is_default) {
            return done(__('The default currency cannot be deleted.'), null, false);
        }
        $name = $currency->name;
        $code = $currency->code;

        $currency->delete();

        logActivity(
            $currency,
            "واحد پول حذف شد: {$name} ({$code})",
            [
                'currency_id' => $currency->id,
                'name' => $name,
                'code' => $code
            ],
            'deleted'
        );

        return redirect()->route('admin.currencies.index')->with('success', __('administration.currency_deleted'));
    }

    public function toggleActive(Currency $currency)
    {
        $currency->update(['is_active' => !$currency->is_active]);

        logActivity(
            $currency,
            "وضعیت واحد پول تغییر کرد: {$currency->name} (" . ($currency->is_active ? 'فعال' : 'غیرفعال') . ")",
            [
                'currency_id' => $currency->id,
                'is_active' => $currency->is_active
            ]
        );

        return response()->json(['success' => true, 'status' => $currency->is_active]);
    }
}
