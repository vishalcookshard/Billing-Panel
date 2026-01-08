<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Display a listing of plans
     */

    public function index()
    {
        $this->authorize('access-admin');
        $plans = Plan::with('category')->latest()->paginate(20);
        return view('admin.plans.index', ['plans' => $plans]);
    }

    /**
     * Show the form for creating a new plan
     */

    public function create()
    {
        $this->authorize('access-admin');
        $categories = ServiceCategory::active()->get();
        return view('admin.plans.create', ['categories' => $categories]);
    }

    /**
     * Store a newly created plan
     */
    public function store(Request $request)
    {
        $this->authorize('access-admin');
        $validated = $request->validate([
            'service_category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price_monthly' => 'nullable|numeric|min:0',
            'price_yearly' => 'nullable|numeric|min:0',
            'price_lifetime' => 'nullable|numeric|min:0',
            'features' => 'nullable|json',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        // Ensure unique slug per category
        $existing = Plan::where('service_category_id', $validated['service_category_id'])
            ->where('slug', $validated['slug'])
            ->exists();

        if ($existing) {
            return back()->withErrors(['slug' => 'This slug already exists for this category']);
        }

        $plan = Plan::create($validated);

        // Audit log
        \App\Models\Audit::log(auth()->id(), 'admin.plan.create', [
            'plan_id' => $plan->id,
            'actor_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan created successfully');
    }

    /**
     * Show the form for editing a plan
     */
    public function edit(Plan $plan)
    {
        $categories = ServiceCategory::active()->get();
        return view('admin.plans.edit', [
            'plan' => $plan,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the plan
     */
    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'service_category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price_monthly' => 'nullable|numeric|min:0',
            'price_yearly' => 'nullable|numeric|min:0',
            'price_lifetime' => 'nullable|numeric|min:0',
            'features' => 'nullable|json',
            'is_active' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $plan->update($validated);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan updated successfully');
    }

    /**
     * Delete the plan
     */
    public function destroy(Plan $plan)
    {
        $plan->delete();

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan deleted successfully');
    }
}
