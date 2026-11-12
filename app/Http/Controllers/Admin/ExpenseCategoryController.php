<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.expense-categories.index', [
            'categories' => ExpenseCategory::withCount('expenses')->orderBy('sort_order')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.expense-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $category = ExpenseCategory::create($validated);

        AuditLogger::log('expense_category.created', 'expense_category', $category->id, null, ['name' => $category->name]);

        return redirect()->route('admin.expense-categories.index')->with('success', 'Category created.');
    }

    public function edit(ExpenseCategory $expenseCategory): View
    {
        return view('admin.expense-categories.edit', ['category' => $expenseCategory]);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $old = $expenseCategory->only(array_keys($validated));
        $expenseCategory->update($validated);

        AuditLogger::log('expense_category.updated', 'expense_category', $expenseCategory->id, $old, $validated);

        return redirect()->route('admin.expense-categories.index')->with('success', 'Category updated.');
    }

    public function toggle(ExpenseCategory $expenseCategory): RedirectResponse
    {
        $expenseCategory->update(['is_active' => ! $expenseCategory->is_active]);

        AuditLogger::log($expenseCategory->is_active ? 'expense_category.activated' : 'expense_category.deactivated', 'expense_category', $expenseCategory->id);

        return back()->with('success', 'Category updated.');
    }
}
