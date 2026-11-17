<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccountCategory;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class AccountCategoriesController extends Controller
{
    public function index()
    {
        $categories = AccountCategory::query()->withCount('subCategories')->orderBy('id')->get();
        $accounts = \App\Models\Account::query()
            ->join('account_sub_categories as s', 's.id', '=', 'accounts.account_sub_category_id')
            ->groupBy('s.account_category_id')->selectRaw('s.account_category_id, COUNT(*) AS n')
            ->pluck('n', 's.account_category_id');

        return page('admin.account-categories.index', compact('categories', 'accounts'));
    }

    public function data()
    {
        return DataTables::of(AccountCategory::query())
            ->addIndexColumn()
            ->editColumn('bi_icon', function ($row) {
                return $row->bi_icon ?? '-';
            })
            ->editColumn('bi_icon_color', function ($row) {
                return $row->bi_icon_color ?? '#333';
            })
            ->addColumn('actions', function ($row) {
                return view('admin.account-categories.partials.actions', compact('row'))->render();
            })
            ->rawColumns(['actions'])
            ->make(true);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:account_categories,name',
            'code_prefix' => 'required|string',
            'bi_icon' => 'nullable|string',
            'bi_icon_color' => 'nullable|string',
        ]);

        $category = AccountCategory::create($validated);

        // Activity Log
        logActivity(
            $category,
            "دسته‌بندی حساب ایجاد شد: {$category->name}",
            [
                'category_id' => $category->id,
                'name' => $category->name,
                'code_prefix' => $category->code_prefix,
            ],
            'created'
        );

        return done(__('administration.account_category_created'));
    }


    public function update(Request $request, $id)
    {
            $request->validate([
                'name' => 'required|string|max:255|unique:account_categories,name,'.$id,
                'code_prefix' => 'required|string|max:10',
                'bi_icon' => 'nullable|string',
                'bi_icon_color' => 'nullable|string'
            ]);

            $category = AccountCategory::findOrFail($id);

            // Store original values
            $original = $category->getOriginal();

            // Update category
            $category->update([
                'name' => $request->name,
                'code_prefix' => $request->code_prefix,
                'bi_icon' => $request->bi_icon,
                'bi_icon_color' => $request->bi_icon_color
            ]);

            // Persian labels for fields
            $labels = [
                'name' => 'نام',
                'code_prefix' => 'پیشوند کد',
                'bi_icon' => 'آیکن',
                'bi_icon_color' => 'رنگ آیکن'
            ];

            $changes = [];

            foreach ($category->getChanges() as $field => $newValue) {

                if ($field === 'updated_at') {
                    continue;
                }

                $oldValue = $original[$field] ?? null;

                if ($oldValue != $newValue) {

                    $label = $labels[$field] ?? $field;

                    $changes[] = "{$label}: {$oldValue} → {$newValue}";
                }
            }

            // Log only if something actually changed
            if (!empty($changes)) {

                $description = "دسته‌بندی حساب بروزرسانی شد (" . implode(' | ', $changes) . ")";

                logActivity(
                    $category,
                    $description,
                    [
                        'category_id' => $category->id,
                        'changes' => $changes
                    ],
                    'updated'
                );
            }

            return done(__('Saved.'));
    }
    public function destroy($id)
    {
        $category = AccountCategory::withCount('subCategories')->findOrFail($id);
        if ($category->sub_categories_count > 0) {
            return done(__('This category has account types under it, so it cannot be deleted.'), null, false);
        }

        $name = $category->name;

        $category->delete();

        // Activity Log
        logActivity(
            $category,
            "دسته‌بندی حساب حذف شد: {$name}",
            [
                'category_id' => $id,
                'name' => $name
            ],
            'deleted'
        );

        return done(__('administration.category_deleted_period'));
    }
}
