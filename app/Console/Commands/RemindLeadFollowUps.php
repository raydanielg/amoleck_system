<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RemindLeadFollowUps extends Command
{
    protected $signature = 'leads:remind';

    protected $description = 'Notify sales managers about due and overdue lead follow-ups';

    public function handle(): int
    {
        Cache::forever('scheduler.last_run', now());

        $due = Lead::whereNotNull('assigned_to')
            ->whereNotNull('next_followup_at')
            ->where('next_followup_at', '<=', now()->toDateString())
            ->whereNotIn('visit_status', [Lead::VISIT_SERVICE_RECEIVED, Lead::VISIT_NOT_CONVERTED])
            ->with('assignee')->get();

        $sent = 0;
        foreach ($due as $lead) {
            // One reminder per lead per day
            $flag = "lead.reminded.{$lead->id}.".now()->toDateString();
            if (Cache::has($flag)) {
                continue;
            }

            $overdue = $lead->next_followup_at->isBefore(now()->startOfDay());
            NotificationService::notify($lead->assignee, 'lead.followup',
                $overdue ? 'Overdue lead follow-up' : 'Lead follow-up due today',
                "{$lead->full_name} ({$lead->lead_reference}) — follow-up {$lead->next_followup_at->diffForHumans()}",
                route('leads.show', $lead));

            Cache::put($flag, true, now()->addDay());
            $sent++;
        }

        $this->info("Sent {$sent} follow-up reminder(s).");

        return self::SUCCESS;
    }
}
