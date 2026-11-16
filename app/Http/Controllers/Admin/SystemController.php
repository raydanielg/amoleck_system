<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemController extends Controller
{
    /** System health dashboard — diagnostics without exposing secrets. */
    public function health()
    {
        $checks = [];

        // Database connectivity
        try {
            DB::select('select 1');
            $checks[] = ['label' => 'Database connection', 'ok' => true, 'detail' => DB::connection()->getDriverName().' · '.DB::connection()->getDatabaseName()];
        } catch (\Throwable $e) {
            $checks[] = ['label' => 'Database connection', 'ok' => false, 'detail' => 'FAILED'];
        }

        // Scheduler heartbeat — updated by messages:process
        $lastRun = Cache::get('scheduler.last_run');
        $checks[] = [
            'label' => 'Scheduler / cron',
            'ok' => $lastRun && now()->diffInMinutes($lastRun) < 10,
            'detail' => $lastRun ? 'Last run '.$lastRun->diffForHumans() : 'Never ran — cPanel cron not configured',
        ];

        // Queue backlog
        $pendingJobs = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $checks[] = ['label' => 'Queued jobs pending', 'ok' => $pendingJobs < 50, 'detail' => (string) $pendingJobs];
        $checks[] = ['label' => 'Failed jobs', 'ok' => $failedJobs === 0, 'detail' => (string) $failedJobs];

        // Storage writable
        $writable = is_writable(storage_path('app')) && is_writable(storage_path('logs'));
        $checks[] = ['label' => 'Storage writable', 'ok' => $writable, 'detail' => $writable ? 'storage/ + logs OK' : 'permission problem — chmod -R 775 storage'];

        // SMS delivery health (last 24h)
        $recentFailed = Message::where('status', 'failed')->where('created_at', '>=', now()->subDay())->count();
        $checks[] = ['label' => 'Failed SMS (24h)', 'ok' => $recentFailed === 0, 'detail' => (string) $recentFailed];

        // Active sessions
        $sessions = Schema::hasTable('sessions') ? DB::table('sessions')->where('last_activity', '>=', now()->subHour()->getTimestamp())->count() : 0;
        $checks[] = ['label' => 'Active sessions (1h)', 'ok' => true, 'detail' => (string) $sessions];

        $env = [
            'PHP' => PHP_VERSION,
            'Laravel' => app()->version(),
            'Environment' => config('app.env'),
            'Debug' => config('app.debug') ? 'ON — DISABLE IN PRODUCTION' : 'off',
            'Timezone' => config('app.timezone'),
            'Session driver' => config('session.driver'),
            'Cache driver' => config('cache.default'),
            'Queue driver' => config('queue.default'),
            'Disk free' => round(disk_free_space(base_path()) / 1e9, 1).' GB',
        ];

        return view('admin.system.health', [
            'checks' => $checks,
            'env' => $env,
            'debugOn' => (bool) config('app.debug'),
        ]);
    }
}
