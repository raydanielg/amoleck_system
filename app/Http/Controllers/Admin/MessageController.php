<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\Patient;
use App\Services\Messaging\MessagingService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $query = Message::with('template')->latest();
        if ($s = $request->input('q')) {
            $query->where(fn ($q) => $q->where('phone', 'like', "%{$s}%")->orWhere('recipient_name', 'like', "%{$s}%")->orWhere('message_reference', 'like', "%{$s}%"));
        }
        if ($st = $request->input('status')) {
            $query->where('status', $st);
        }
        if ($t = $request->input('type')) {
            $query->where('type', $t);
        }
        $messages = $query->paginate(30)->withQueryString();

        return view('admin.communication.logs', [
            'messages' => $messages,
            'templates' => MessageTemplate::where('is_active', true)->orderBy('category')->orderBy('name')->get(),
            'patients' => Patient::where('status', 'active')->orderBy('full_name')->get(['id', 'full_name', 'phone']),
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'phone']),
            'senderId' => \App\Models\SmsGateway::current()->sender_id,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'nullable|exists:patients,id',
            'customer_id' => 'nullable|exists:customers,id',
            'phone' => 'required_without_all:patient_id,customer_id|nullable|string|max:20',
            'template_id' => 'nullable|exists:message_templates,id',
            'body' => 'required|string|max:1600',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $patient = $data['patient_id'] ? Patient::find($data['patient_id']) : null;
        $customer = $data['customer_id'] ? Customer::find($data['customer_id']) : null;
        $phone = $data['phone'] ?? $patient?->phone ?? $customer?->phone;

        try {
            $message = MessagingService::compose([
                'phone' => $phone,
                'body' => $data['body'],
                'template_id' => $data['template_id'] ?? null,
                'recipient_name' => $patient?->full_name ?? $customer?->name ?? $data['phone'] ?? null,
                'patient_id' => $patient?->id,
                'customer_id' => $customer?->id,
                'type' => 'manual',
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'created_by' => $request->user()->id,
            ]);
            if (! $message->scheduled_at) {
                MessagingService::send($message);
            }
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $message->scheduled_at ? 'Message scheduled.' : "Message {$message->message_reference} {$message->status}.");
    }

    public function show(Message $message)
    {
        $message->load(['events', 'template', 'patient', 'customer']);

        return view('admin.communication.show', compact('message'));
    }

    public function retry(Message $message)
    {
        if ($message->attempts >= 3) {
            return back()->with('error', 'Retry limit reached.');
        }
        MessagingService::send($message);

        return back()->with('success', 'Retry submitted — status: '.$message->fresh()->status);
    }
}
