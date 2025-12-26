<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class ServiceCategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index()
    {
        $categories = ServiceCategory::ordered()->paginate(20);
        return view('admin.categories.index', ['categories' => $categories]);
    }

    /**
     * Show the form for creating a new category
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:service_categories',
            'description' => 'nullable|string',
            'icon' => ['nullable', 'string', 'max:1000', new \App\Rules\SafeHtml()],
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        ServiceCategory::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Service category created successfully');
    }

    /**
     * Show the form for editing a category
     */
    public function edit(ServiceCategory $category)
    {
        return view('admin.categories.edit', ['category' => $category]);
    }

    /**
     * Update the category
     */
    public function update(Request $request, ServiceCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:service_categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'icon' => ['nullable', 'string', 'max:1000', new \App\Rules\SafeHtml()],
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $category->update($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Service category updated successfully');
    }

    /**
     * Delete the category
     */
    public function destroy(ServiceCategory $category)
    {
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Service category deleted successfully');
    }
}
