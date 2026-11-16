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
        if ($request->ajax()) {
            $query = AccountSubCategory::with('accountCategory');

            if ($request->has('category_id') && $request->category_id != '') {
                $query->where('account_category_id', $request->category_id);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('category', fn($row) => $row->accountCategory->name ?? '-')
                ->addColumn('actions', fn($row) => view('admin.account-sub-categories.partials.actions', compact('row'))->render())
                ->rawColumns(['actions'])
                ->make(true);
        }

        $categories = AccountCategory::orderBy('name')->get();
        return view('admin.account-sub-categories.index', compact('categories'));
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

        return response()->json(['success' => true]);
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

        return response()->json(['success' => true]);
    }


    public function destroy($id)
    {
        $subcategory = AccountSubCategory::findOrFail($id);

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

        return response()->json(['success' => true]);
    }
}
