<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::where('user_id', Auth::id())->get();
        return view('dashboard.services.index', compact('services'));
    }

    public function show($id)
    {
        $service = Service::where('user_id', Auth::id())->findOrFail($id);
        return view('dashboard.services.show', compact('service'));
    }

    public function login($id)
    {
        $service = Service::where('user_id', Auth::id())->findOrFail($id);
        return redirect($service->getLoginUrl());
    }

    public function upgrade(Request $request, $id)
    {
        $service = Service::where('user_id', Auth::id())->findOrFail($id);
        // Upgrade logic here
        return back()->with('success', 'Upgrade requested.');
    }

    public function cancel(Request $request, $id)
    {
        $service = Service::where('user_id', Auth::id())->findOrFail($id);
        $service->status = 'cancelled';
        $service->cancellation_reason = $request->input('reason');
        $service->cancelled_by = Auth::id();
        $service->save();
        return back()->with('success', 'Cancellation requested.');
    }

    public function addons($id)
    {
        $service = Service::where('user_id', Auth::id())->findOrFail($id);
        return view('dashboard.services.addons', compact('service'));
    }
}
