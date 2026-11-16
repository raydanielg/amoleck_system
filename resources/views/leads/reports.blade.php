<x-app-layout>
    <x-slot name="header">{{ __('Leads Reports') }}</x-slot>
    <x-slot name="headerActions"><a href="{{ route('leads.dashboard') }}" class="btn btn-sm btn-outline-secondary">Dashboard</a></x-slot>

    <form method="GET" class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2 d-flex flex-wrap gap-2 align-items-center">
            @foreach (['today' => 'Today', '7d' => 'Last 7 Days', '30d' => 'Last 30 Days', 'month' => 'This Month', 'last_month' => 'Last Month', 'custom' => 'Custom'] as $value => $label)
                <a href="{{ route('leads.reports', ['range' => $value]) }}" class="btn btn-sm {{ $range === $value ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
            @endforeach
            @if ($range === 'custom')
                <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm" style="width:auto">
                <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm" style="width:auto">
                <input type="hidden" name="range" value="custom">
                <button class="btn btn-sm btn-primary">Apply</button>
            @endif
            <span class="ms-auto small text-muted">{{ $from }} → {{ $to }}</span>
        </div>
    </form>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Leads by Source</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>Source</th><th class="text-end">Leads</th><th class="text-end">Converted</th><th class="text-end">Rate</th></tr></thead>
                        <tbody>
                            @forelse ($bySource as $row)
                                <tr>
                                    <td>{{ $row->source->name ?? 'Unknown' }}</td>
                                    <td class="text-end">{{ $row->total }}</td>
                                    <td class="text-end">{{ $row->converted }}</td>
                                    <td class="text-end">{{ $row->total > 0 ? round($row->converted / $row->total * 100, 1) : 0 }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No leads in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Sales Manager Performance</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th>Manager</th><th class="text-end">Assigned</th><th class="text-end">Contacted</th><th class="text-end">Interested</th><th class="text-end">Visited</th><th class="text-end">Converted</th><th class="text-end">Overdue</th><th class="text-end">Rate</th></tr></thead>
                        <tbody>
                            @forelse ($managerPerf as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['manager']->name }}</td>
                                    <td class="text-end">{{ $row['total'] }}</td>
                                    <td class="text-end">{{ $row['contacted'] }}</td>
                                    <td class="text-end">{{ $row['interested'] }}</td>
                                    <td class="text-end">{{ $row['visited'] }}</td>
                                    <td class="text-end">{{ $row['converted'] }}</td>
                                    <td class="text-end {{ $row['overdue'] ? 'text-danger fw-bold' : '' }}">{{ $row['overdue'] }}</td>
                                    <td class="text-end">{{ $row['rate'] }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">No assigned leads in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
