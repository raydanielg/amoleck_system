<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\SmsGateway;
use App\Services\Messaging\GatewayManager;
use App\Services\Messaging\MessagingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ProcessScheduledMessages extends Command
{
    protected $signature = 'messages:process {--reconcile : Also pull provider logs to update delivery status} {--balance : Also refresh the credit balance}';

    protected $description = 'Send due scheduled SMS, reconcile delivery status and refresh balance';

    public function handle(): int
    {
        Cache::forever('scheduler.last_run', now());

        $gateway = SmsGateway::current();
        if (! $gateway->enabled || ! $gateway->isConfigured()) {
            $this->warn('SMS gateway is not configured or disabled.');

            return self::SUCCESS;
        }

        // 1) Send due scheduled messages
        $due = Message::where('status', 'scheduled')->where('scheduled_at', '<=', now())->limit(50)->get();
        foreach ($due as $message) {
            try {
                MessagingService::send($message);
                $this->info("Sent {$message->message_reference} → {$message->status}");
            } catch (\Throwable $e) {
                $message->setStatus('failed', 'Scheduler error: '.$e->getMessage());
            }
        }

        $adapter = GatewayManager::adapter($gateway);

        // 2) Reconcile statuses from provider logs (fallback when webhooks are not set up)
        if ($this->option('reconcile') && method_exists($adapter, 'recentLogs')) {
            $logs = $adapter->recentLogs($gateway);
            $updated = 0;
            foreach ($logs as $log) {
                $message = Message::where('provider_ref', $log['provider_ref'])->first();
                if ($message && $message->status !== $log['status'] && $message->status !== 'delivered') {
                    $message->setStatus($log['status'], 'Provider log reconciliation');
                    $updated++;
                }
            }
            $this->info("Reconciled {$updated} message(s) from ".count($logs).' provider log entries.');
        }

        // 3) Refresh credit balance
        if ($this->option('balance')) {
            $result = $adapter->balance($gateway);
            $balance = $result['balance'] ?? null;
            if (is_numeric($balance)) {
                $gateway->update(['manual_balance' => $balance, 'balance_checked_at' => now()]);
                $this->info('Balance: '.$balance);
            }
        }

        return self::SUCCESS;
    }
}
