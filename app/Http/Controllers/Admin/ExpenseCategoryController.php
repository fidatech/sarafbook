<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        return view('admin.expense-categories.index');
    }

    public function data()
    {
        return DataTables::of(ExpenseCategory::query())
            ->addIndexColumn()
            ->addColumn('actions', function ($cat) {

                $auth = auth()->user();
                $html = '<div class="d-flex gap-1">';

                if ($auth->can('update expense categories')) {
                    $html .= '
                        <button class="btn btn-sm btn-outline-warning edit-btn"
                            data-id="' . $cat->id . '"
                            data-name="' . $cat->name . '"
                            data-description="' . $cat->description . '"
                            title="' . __('ui.blade_edit') . '">
                            <i class="bi bi-pencil"></i>
                        </button>
                    ';
                }

                if ($auth->can('delete expense categories')) {
                    $html .= '
                        <button class="btn btn-sm btn-outline-danger delete-btn"
                            data-id="' . $cat->id . '"
                            title="' . __('ui.delete') . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    ';
                }

                $html .= '</div>';

                return $html;
            })

            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|unique:expense_categories,name',
            'description' => 'nullable|string',
        ]);

        $category = ExpenseCategory::create($request->only('name', 'description'));

        logActivity(
            $category,
            "دسته‌بندی مصارف ایجاد شد: {$category->name}",
            [
                'expense_category_id' => $category->id,
                'name' => $category->name,
                'description' => $category->description
            ],
            'created'
        );

        return response()->json(['message' => __('administration.category_created')]);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $request->validate([
            'name'        => 'required|string|unique:expense_categories,name,' . $expenseCategory->id,
            'description' => 'nullable|string',
        ]);

        $original = $expenseCategory->getOriginal();

        $expenseCategory->update($request->only('name', 'description'));

        $changes = [];

        foreach ($expenseCategory->getChanges() as $field => $newValue) {

            if ($field === 'updated_at') {
                continue;
            }

            $oldValue = $original[$field] ?? null;

            if ($oldValue != $newValue) {
                $changes[] = "{$field}: {$oldValue} → {$newValue}";
            }
        }

        if (!empty($changes)) {

            $description = "دسته‌بندی مصارف بروزرسانی شد (" . implode(' | ', $changes) . ")";

            logActivity(
                $expenseCategory,
                $description,
                [
                    'expense_category_id' => $expenseCategory->id,
                    'changes' => $changes
                ],
                'updated'
            );
        }

        return response()->json(['message' => __('administration.category_updated')]);
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        $name = $expenseCategory->name;

        $expenseCategory->delete();

        logActivity(
            $expenseCategory,
            "دسته‌بندی مصارف حذف شد: {$name}",
            [
                'expense_category_id' => $expenseCategory->id,
                'name' => $name
            ],
            'deleted'
        );

        return response()->json(['message' => __('administration.category_deleted')]);
    }
}
