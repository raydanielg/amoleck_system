<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunicationConsent;
use App\Models\Customer;
use App\Models\MessageCampaign;
use App\Models\MessageTemplate;
use App\Models\Patient;
use App\Services\Messaging\MessagingService;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index()
    {
        return view('admin.communication.campaigns', [
            'campaigns' => MessageCampaign::with('creator')->latest()->paginate(20),
            'templates' => MessageTemplate::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'body' => 'required|string|max:1600',
            'audience' => 'required|in:all_patients,recent_patients,followups,customers',
            'send' => 'nullable|in:now,schedule',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $recipients = $this->recipients($data['audience']);
        $campaign = MessageCampaign::create([
            'name' => $data['name'], 'body' => $data['body'], 'audience' => $data['audience'],
            'recipients_count' => $recipients->count(),
            'status' => ($data['send'] ?? '') === 'now' ? 'sending' : 'scheduled',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $sent = 0;
        foreach ($recipients as $r) {
            try {
                MessagingService::compose([
                    'phone' => $r->phone, 'body' => $data['body'],
                    'recipient_name' => $r->name ?? $r->full_name,
                    'patient_id' => $r instanceof Patient ? $r->id : null,
                    'customer_id' => $r instanceof Customer ? $r->id : null,
                    'type' => 'campaign', 'campaign_id' => $campaign->id,
                    'idempotency_key' => 'campaign:'.$campaign->id.':'.$r->id,
                    'created_by' => $request->user()->id,
                ]);
                $sent++;
            } catch (\RuntimeException) {
                continue; // opted out or invalid number
            }
        }

        if (($data['send'] ?? '') === 'now') {
            \App\Models\Message::where('campaign_id', $campaign->id)->where('status', 'queued')->get()->each(fn ($m) => MessagingService::send($m));
            $campaign->update(['status' => 'sent']);
        }

        return back()->with('success', "Campaign created — {$sent} recipient(s) queued.");
    }

    private function recipients(string $audience)
    {
        return match ($audience) {
            'recent_patients' => Patient::where('status', 'active')->whereNotNull('phone')->where('created_at', '>=', now()->subDays(30))->get(),
            'followups' => Patient::where('status', 'active')->whereNotNull('phone')->whereHas('followUps', fn ($q) => $q->whereDate('due_date', '>=', today()))->get(),
            'customers' => Customer::whereNotNull('phone')->get(),
            default => Patient::where('status', 'active')->whereNotNull('phone')->get(),
        };
    }
}
