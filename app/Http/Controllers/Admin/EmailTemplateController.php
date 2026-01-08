<?php

namespace App\Http\Controllers\Admin;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::all();
        return view('admin.email_templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.email_templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|unique:email_templates',
            'subject' => 'required',
            'body' => 'required',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);
        $data['variables'] = json_encode($data['variables'] ?? []);
        EmailTemplate::create($data);
        return redirect()->route('admin.email_templates.index')->with('success', 'Template created.');
    }

    public function edit($id)
    {
        $template = EmailTemplate::findOrFail($id);
        return view('admin.email_templates.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = EmailTemplate::findOrFail($id);
        $data = $request->validate([
            'subject' => 'required',
            'body' => 'required',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);
        $data['variables'] = json_encode($data['variables'] ?? []);
        $template->update($data);
        return redirect()->route('admin.email_templates.index')->with('success', 'Template updated.');
    }

    public function destroy($id)
    {
        $template = EmailTemplate::findOrFail($id);
        $template->delete();
        return redirect()->route('admin.email_templates.index')->with('success', 'Template deleted.');
    }
}
