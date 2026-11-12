<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsGateway;
use App\Services\AuditLogger;
use App\Services\Messaging\MessagingService;
use Illuminate\Http\Request;

class GatewaySettingController extends Controller
{
    public function edit()
    {
        return view('admin.communication.gateway', ['gateway' => SmsGateway::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'provider' => 'required|string|max:40',
            'label' => 'nullable|string|max:120',
            'base_url' => 'nullable|url|max:255',
            'endpoint' => 'nullable|string|max:255',
            'api_key' => 'nullable|string|max:500',
            'api_secret' => 'nullable|string|max:500',
            'username' => 'nullable|string|max:120',
            'password' => 'nullable|string|max:500',
            'sender_id' => 'nullable|string|max:40',
            'country_code' => 'nullable|string|max:8',
            'timeout_seconds' => 'nullable|integer|min:5|max:120',
            'webhook_secret' => 'nullable|string|max:120',
            'enabled' => 'boolean',
            'cost_per_segment' => 'nullable|numeric|min:0',
            'manual_balance' => 'nullable|numeric|min:0',
        ]);

        $gateway = SmsGateway::current();
        // keep existing secrets when the masked field is left blank
        foreach (['api_key', 'api_secret', 'password', 'webhook_secret'] as $secret) {
            if (empty($data[$secret]) || $data[$secret] === '••••••••') {
                unset($data[$secret]);
            }
        }
        if (isset($data['manual_balance'])) {
            $data['balance_checked_at'] = now();
        }
        $data['enabled'] = $request->boolean('enabled');
        $gateway->update($data);
        AuditLogger::log('sms_gateway.updated', 'sms_gateway', $gateway->id);

        return back()->with('success', 'Gateway settings saved.');
    }

    public function testSms(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:20',
            'message' => 'required|string|max:480',
        ]);
        try {
            $message = MessagingService::sendNow([
                'phone' => $data['phone'], 'body' => $data['message'], 'type' => 'test',
                'recipient_name' => 'Test SMS', 'created_by' => $request->user()->id,
            ]);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            $message->status === 'failed' ? 'error' : 'success',
            $message->status === 'failed'
                ? 'Failed: '.$message->failure_reason
                : 'Accepted by gateway ('.$message->status.'). Delivery is not yet confirmed.'
        );
    }
}
