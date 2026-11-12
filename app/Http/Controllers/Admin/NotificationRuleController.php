<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Models\NotificationRule;
use Illuminate\Http\Request;

class NotificationRuleController extends Controller
{
    public function index()
    {
        return view('admin.communication.rules', [
            'rules' => NotificationRule::with('template')->orderBy('event_key')->get(),
            'templates' => MessageTemplate::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, NotificationRule $notificationRule)
    {
        $notificationRule->update($request->validate([
            'template_id' => 'nullable|exists:message_templates,id',
            'delay_minutes' => 'nullable|integer|min:0|max:10080',
            'enabled' => 'boolean',
        ]) + ['enabled' => $request->boolean('enabled')]);

        return back()->with('success', 'Rule updated.');
    }
}
