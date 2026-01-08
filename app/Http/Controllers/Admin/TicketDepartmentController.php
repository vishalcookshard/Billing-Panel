<?php

namespace App\Http\Controllers\Admin;

use App\Models\TicketDepartment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TicketDepartmentController extends Controller
{
    public function index()
    {
        $departments = TicketDepartment::all();
        return view('admin.departments.index', compact('departments'));
    }

    public function create()
    {
        return view('admin.departments.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|unique:ticket_departments']);
        TicketDepartment::create($data);
        return redirect()->route('admin.departments.index')->with('success', 'Department created.');
    }

    public function edit($id)
    {
        $department = TicketDepartment::findOrFail($id);
        return view('admin.departments.edit', compact('department'));
    }

    public function update(Request $request, $id)
    {
        $department = TicketDepartment::findOrFail($id);
        $data = $request->validate(['name' => 'required|unique:ticket_departments,name,' . $id]);
        $department->update($data);
        return redirect()->route('admin.departments.index')->with('success', 'Department updated.');
    }

    public function destroy($id)
    {
        $department = TicketDepartment::findOrFail($id);
        $department->delete();
        return redirect()->route('admin.departments.index')->with('success', 'Department deleted.');
    }
}
