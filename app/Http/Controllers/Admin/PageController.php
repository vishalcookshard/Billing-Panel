<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Display a listing of pages
     */
    public function index()
    {
        $pages = Page::latest()->paginate(20);
        return view('admin.pages.index', ['pages' => $pages]);
    }

    /**
     * Show the form for creating a new page
     */
    public function create()
    {
        return view('admin.pages.create');
    }

    /**
     * Store a newly created page
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages',
            'content' => ['required','string', new \App\Rules\SafeHtml()],
            'is_published' => 'boolean',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        Page::create($validated);

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page created successfully');
    }

    /**
     * Show the form for editing a page
     */
    public function edit(Page $page)
    {
        return view('admin.pages.edit', ['page' => $page]);
    }

    /**
     * Update the page
     */
    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug,' . $page->id,
            'content' => ['required','string', new \App\Rules\SafeHtml()],
            'is_published' => 'boolean',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        $validated['updated_by'] = auth()->id();

        $page->update($validated);

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page updated successfully');
    }

    /**
     * Delete the page
     */
    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page deleted successfully');
    }
}
