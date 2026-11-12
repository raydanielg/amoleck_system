<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    public function index()
    {
        return view('admin.communication.templates', [
            'templates' => MessageTemplate::orderBy('category')->orderBy('name')->paginate(30),
        ]);
    }

    public function store(Request $request)
    {
        MessageTemplate::create($request->validate([
            'key' => 'required|string|max:60|unique:message_templates,key',
            'name' => 'required|string|max:120',
            'category' => 'required|string|max:60',
            'body' => 'required|string|max:1600',
        ]));

        return back()->with('success', 'Template created.');
    }

    public function update(Request $request, MessageTemplate $messageTemplate)
    {
        $messageTemplate->update($request->validate([
            'name' => 'required|string|max:120',
            'category' => 'required|string|max:60',
            'body' => 'required|string|max:1600',
        ]));

        return back()->with('success', 'Template updated.');
    }

    public function toggle(MessageTemplate $messageTemplate)
    {
        $messageTemplate->update(['is_active' => ! $messageTemplate->is_active]);

        return back()->with('success', 'Template '.($messageTemplate->is_active ? 'activated' : 'deactivated').'.');
    }
}
