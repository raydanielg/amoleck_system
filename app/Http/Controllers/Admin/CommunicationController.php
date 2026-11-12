<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\SmsGateway;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CommunicationController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);
        $base = Message::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        $stats = [
            'total' => (clone $base)->count(),
            'delivered' => (clone $base)->where('status', 'delivered')->count(),
            'failed' => (clone $base)->where('status', 'failed')->count(),
            'pending' => (clone $base)->whereIn('status', ['queued', 'scheduled', 'submitted', 'sent'])->count(),
            'today' => Message::whereDate('created_at', today())->count(),
            'month' => Message::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'cost' => (clone $base)->sum('cost'),
        ];
        $stats['success_rate'] = $stats['total'] > 0 ? round($stats['delivered'] / $stats['total'] * 100) : null;

        $recent = Message::latest()->limit(10)->get();
        $gateway = SmsGateway::current();

        return view('admin.communication.index', compact('stats', 'recent', 'gateway'));
    }

    private function range(Request $request): array
    {
        $period = $request->query('period', 'month');
        return match ($period) {
            'today' => [today(), today()],
            'yesterday' => [today()->subDay(), today()->subDay()],
            '7d' => [today()->subDays(6), today()],
            '30d' => [today()->subDays(29), today()],
            'last_month' => [today()->subMonth()->startOfMonth(), today()->subMonth()->endOfMonth()],
            'custom' => [Carbon::parse($request->query('from', today()->subDays(29))), Carbon::parse($request->query('to', today()))],
            default => [today()->startOfMonth(), today()],
        };
    }
}
