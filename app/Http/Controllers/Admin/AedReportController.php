<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Account, AedReportEntry, Currency, Transaction};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AedReportController extends Controller
{
    private function currency(): Currency
    {
        return Currency::where('code', 'AED')->firstOrFail();
    }

    private function eligibleAccounts()
    {
        return Account::whereIn('account_sub_category_id', [1, 2]);
    }

    public function index()
    {
        $currency = Currency::where('code', 'AED')->first();
        $entries = AedReportEntry::with('account')->where('currency_id', $currency?->id)->orderBy('id')->get();
        $customers = $currency ? $this->eligibleAccounts()->with('subCategory')->whereNotIn('id', $entries->pluck('account_id'))->orderBy('name')->get() : collect();

        return view('admin.reports.aed', compact('currency', 'entries', 'customers'));
    }

    public function pdf()
    {
        $currency = Currency::where('code', 'AED')->first();
        $entries = AedReportEntry::with('account')->where('currency_id', $currency?->id)->orderBy('id')->get();
        $company = \App\Models\Setting::first();
        $generatedAt = now();
        $html = view('admin.reports.exports.aed_pdf', compact('entries', 'company', 'generatedAt'))->render();
        $tempDir = storage_path('app/mpdf-temp');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($tempDir);
        $pdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $tempDir,
            'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 12,
            'margin_bottom' => 16, 'default_font' => 'dejavusans',
        ]);
        $pdf->SetTitle(__('aed.title'));
        $pdf->SetHTMLFooter('<div dir="ltr" style="direction:ltr;text-align:center;font-size:9px;color:#64748b">{PAGENO} / {nbpg}</div>');
        $pdf->WriteHTML($html);

        return response($pdf->Output('', \Mpdf\Output\Destination::STRING_RETURN))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="aed-report-'.$generatedAt->format('Y-m-d').'.pdf"');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_ids' => ['required', 'array', 'min:1'],
            'account_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        // New rows start at 3.67; each row can subsequently save its own rate.
        $data['exchange_rate'] = '3.67';
        $data['date'] = now()->toDateString();
        $currency = $this->currency();
        DB::transaction(function () use ($data, $currency) {
            // Serialize additions for the same accounts, including concurrent requests.
            $accounts = $this->eligibleAccounts()->whereIn('id', $data['account_ids'])->orderBy('id')->lockForUpdate()->get();
            if ($accounts->count() !== count($data['account_ids'])) {
                throw ValidationException::withMessages(['account_ids' => __('aed.invalid_customers')]);
            }
            if (AedReportEntry::where('currency_id', $currency->id)->whereIn('account_id', $data['account_ids'])->exists()) {
                throw ValidationException::withMessages(['account_ids' => __('aed.duplicate')]);
            }
            foreach ($accounts as $account) {
                $ledger = Transaction::where('account_id', $account->id)->where('actual_currency_id', $currency->id)
                    ->where('status', 'active')->where('created_at', '<', \Carbon\Carbon::parse($data['date'])->addDay()->startOfDay());
                $balance = $ledger->selectRaw("SUM(CASE WHEN transaction_type = 'credit' THEN actual_amount ELSE -actual_amount END) AS total")->value('total') ?? 0;
                $entry = AedReportEntry::create([
                    'account_id' => $account->id, 'currency_id' => $currency->id,
                    'aed_balance' => $balance, 'exchange_rate' => $data['exchange_rate'],
                    'converted_amount' => round((float) $balance / (float) $data['exchange_rate'], 2), 'date' => $data['date'],
                ]);
                AedReportEntry::refreshLiveBalances(collect([$entry]));
                $entry->save();
            }
        });
        return redirect()->route('admin.reports.aed.index')->with('success', __('aed.saved'));
    }

    public function update(Request $request, AedReportEntry $entry)
    {
        abort_unless($entry->currency_id === $this->currency()->id, 404);
        $data = $request->validate([
            'exchange_rate' => ['required', 'numeric', 'min:0.00000001', 'max:999999999999.99999999', 'decimal:0,8'],
        ]);
        $entry->exchange_rate = $data['exchange_rate'];
        AedReportEntry::refreshLiveBalances(collect([$entry]));
        if (!is_finite((float) $entry->converted_amount) || abs((float) $entry->converted_amount) >= 1e18) {
            throw ValidationException::withMessages(['exchange_rate' => __('aed.invalid_rate')]);
        }
        $entry->save();

        if ($request->expectsJson()) {
            return response()->json(array_merge($entry->only(['exchange_rate', 'aed_balance', 'converted_amount']), [
                'usd_balance' => $entry->usd_balance,
                'conversion_aed_balance' => $entry->conversion_aed_balance,
            ]));
        }

        return redirect()->route('admin.reports.aed.index')->with('success', __('aed.rate_saved'));
    }

    public function destroy(AedReportEntry $entry)
    {
        abort_unless($entry->currency_id === $this->currency()->id, 404);
        $entry->delete();
        return redirect()->route('admin.reports.aed.index')->with('success', __('aed.removed'));
    }
}
