<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccountSubCategory;
use App\Models\AccountCategory;
use Yajra\DataTables\DataTables;

class AccountSubCategoriesController extends Controller
{
    public function index(Request $request)
    {
        $categories = AccountCategory::orderBy('id')->get();
        $category = $request->integer('category_id') ?: null;
        $search = trim((string) $request->query('q', ''));
        $subCategories = AccountSubCategory::query()->with('accountCategory')->withCount('accounts')
            ->when($category, fn ($q) => $q->where('account_category_id', $category))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('account_category_id')->orderBy('id')->get();

        return page('admin.account-sub-categories.index', [
            'categories' => $categories, 'subCategories' => $subCategories,
            'filters' => ['q' => $search, 'category_id' => $category],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'account_category_id' => 'required|exists:account_categories,id'
        ]);

        $subcategory = AccountSubCategory::create(
            $request->only('name', 'account_category_id')
        );

        $category = AccountCategory::find($request->account_category_id);

        // Activity Log
        logActivity(
            $subcategory,
            "زیر دسته حساب ایجاد شد: {$subcategory->name} (دسته: {$category->name})",
            [
                'subcategory_id' => $subcategory->id,
                'subcategory_name' => $subcategory->name,
                'category_id' => $category->id,
                'category_name' => $category->name
            ],
            'created'
        );

        return done(__('Account type added.'));
    }


    public function show($id)
    {
        $subcategory = AccountSubCategory::findOrFail($id);
        return view('admin.account-sub-categories.show', compact('subcategory'));
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'account_category_id' => 'required|exists:account_categories,id'
        ]);

        $subcategory = AccountSubCategory::findOrFail($id);

        // Save original data
        $original = $subcategory->getOriginal();

        $subcategory->update(
            $request->only('name', 'account_category_id')
        );

        $labels = [
            'name' => 'نام',
            'account_category_id' => 'دسته حساب'
        ];

        $changes = [];

        foreach ($subcategory->getChanges() as $field => $newValue) {

            if ($field === 'updated_at') {
                continue;
            }

            $oldValue = $original[$field] ?? null;

            if ($field == 'account_category_id') {

                $oldCategory = AccountCategory::find($oldValue);
                $newCategory = AccountCategory::find($newValue);

                $oldValue = $oldCategory->name ?? '-';
                $newValue = $newCategory->name ?? '-';
            }

            if ($oldValue != $newValue) {

                $label = $labels[$field] ?? $field;

                $changes[] = "{$label}: {$oldValue} → {$newValue}";
            }
        }

        if (!empty($changes)) {

            $description = "زیر دسته حساب بروزرسانی شد (" . implode(' | ', $changes) . ")";

            logActivity(
                $subcategory,
                $description,
                [
                    'subcategory_id' => $subcategory->id,
                    'changes' => $changes
                ],
                'updated'
            );
        }

        return done(__('Saved.'));
    }


    public function destroy($id)
    {
        $subcategory = AccountSubCategory::withCount('accounts')->findOrFail($id);
        // deleting a type would delete every account of it (and their records), so a type in use stays
        if ($subcategory->accounts_count > 0) {
            return done(__('This account type has accounts, so it cannot be deleted.'), null, false);
        }

        $name = $subcategory->name;
        $categoryName = $subcategory->accountCategory->name ?? '-';

        $subcategory->delete();

        // Activity Log
        logActivity(
            $subcategory,
            "زیر دسته حساب حذف شد: {$name} (دسته: {$categoryName})",
            [
                'subcategory_id' => $id,
                'subcategory_name' => $name,
                'category_name' => $categoryName
            ],
            'deleted'
        );

        return done(__('Deleted.'));
    }
}
