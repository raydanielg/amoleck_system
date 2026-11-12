<x-app-layout>
    <x-slot name="header">{{ __('Executive Dashboard') }}</x-slot>
    <x-slot name="headerActions">
        @if (auth()->user()->hasPermission('exec.insights'))<form method="POST" action="{{ route('management.insights.refresh') }}">@csrf<input type="hidden" name="period" value="{{ $period }}"><button class="btn btn-outline-secondary btn-sm">Refresh Insights</button></form>@endif
        @if (auth()->user()->hasPermission('exec.reports'))<a href="{{ route('management.report.pdf') }}" class="btn btn-primary btn-sm"><i class="hgi-stroke hgi-file-01 me-1"></i>Monthly Report PDF</a>@endif
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#kpiDrawer">KPI Targets</button>
    </x-slot>
    <div class="card shadow-sm border-0 mb-3"><div class="card-body"><form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small">Period</label><select name="period" class="form-select" onchange="this.form.submit()">
            @foreach (['today'=>'Today','yesterday'=>'Yesterday','last7'=>'Last 7 days','last30'=>'Last 30 days','month'=>'This month','last_month'=>'Last month','year'=>'This year','custom'=>'Custom'] as $v => $l)<option value="{{ $v }}" @selected($period===$v)>{{ $l }}</option>@endforeach
        </select></div>
        @if ($period === 'custom')<div class="col-md-2"><label class="form-label small">From</label><input type="date" name="from" value="{{ $from }}" class="form-control"></div><div class="col-md-2"><label class="form-label small">To</label><input type="date" name="to" value="{{ $to }}" class="form-control"></div>@endif
        <div class="col-md-3"><label class="form-label small">Branch</label><select name="branch_id" class="form-select"><option value="">All branches</option>@foreach ($branchList as $b)<option value="{{ $b->id }}" @selected($branchId===$b->id)>{{ $b->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-outline-secondary">Apply</button></div>
    </form></div></div>
    <div class="row g-3 mb-3">
        @foreach (['revenue','expenses','patients','appointments','appointments_completed','visits','outstanding'] as $k)
            @php $c = $kpis[$k]['change']; @endphp
            <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">{{ $kpis[$k]['label'] }}</div>
                <div class="stat-value">{{ in_array($k,['revenue','expenses','outstanding']) ? number_format($kpis[$k]['value']) : $kpis[$k]['value'] }}</div>
                @if ($c !== null)<div class="small {{ ($c < 0) !== $kpis[$k]['inverse'] ? 'text-success' : 'text-danger' }}">{{ $c > 0 ? '+' : '' }}{{ $c }}% vs prev.</div>@endif
            </div></div>
        @endforeach
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Net Profit (GL)</div><div class="stat-value {{ $pnl['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($pnl['net']) }}</div></div></div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-lg-8"><div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">Revenue Trend (12 months)</div><div class="card-body"><canvas id="trendChart" height="90"></canvas></div></div></div>
        <div class="col-lg-4"><div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">Expenses by Category</div><div class="card-body"><canvas id="expChart" height="160"></canvas></div></div></div>
    </div>
    <div class="row g-3">
        <div class="col-lg-4"><div class="card shadow-sm border-0 h-100"><div class="card-header bg-white fw-semibold">Top Services (revenue)</div>
            <ul class="list-group list-group-flush small">@forelse ($services as $sv)<li class="list-group-item d-flex justify-content-between"><span>{{ $sv['label'] }} <span class="text-muted">({{ $sv['sessions'] }}x)</span></span><strong>{{ number_format($sv['revenue']) }}</strong></li>@empty<li class="list-group-item text-muted">No service revenue in period.</li>@endforelse</ul>
        </div></div>
        <div class="col-lg-4"><div class="card shadow-sm border-0 h-100"><div class="card-header bg-white fw-semibold">Operations Snapshot</div><div class="card-body small">
            <div class="d-flex justify-content-between border-bottom py-2"><span>Inventory value</span><strong>{{ number_format($inventory['value']) }}</strong></div>
            <div class="d-flex justify-content-between border-bottom py-2"><span>Low / out of stock</span><strong>{{ $inventory['low_stock'] }} / {{ $inventory['out_of_stock'] }}</strong></div>
            <div class="d-flex justify-content-between border-bottom py-2"><span>Batches expiring</span><strong class="text-warning">{{ $inventory['batches_expiring'] }}</strong></div>
            @if ($hr)<div class="d-flex justify-content-between border-bottom py-2"><span>Active employees</span><strong>{{ $hr['employees'] }}</strong></div>
            <div class="d-flex justify-content-between border-bottom py-2"><span>Present today</span><strong>{{ $hr['present_today'] }} ({{ $hr['late_today'] }} late)</strong></div>
            <div class="d-flex justify-content-between"><span>On leave</span><strong>{{ $hr['on_leave'] }}</strong></div>@endif
        </div></div></div>
        <div class="col-lg-4"><div class="card shadow-sm border-0 h-100"><div class="card-header bg-white fw-semibold">Patient Growth (6 months)</div><div class="card-body"><canvas id="patChart" height="140"></canvas></div></div></div>
    </div>
    <div class="row g-3 mt-1">
        @if (! empty($branches))
        <div class="col-lg-6"><div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">Branch Comparison</div>
            <div class="table-responsive"><table class="table table-sm mb-0"><thead class="table-light"><tr><th>Branch</th><th class="text-end">Patients</th><th class="text-end">Revenue</th><th class="text-end">Expenses</th><th class="text-end">Staff</th></tr></thead>
            <tbody>@forelse ($branches as $b)<tr><td>{{ $b['name'] }}</td><td class="text-end">{{ $b['patients'] }}</td><td class="text-end">{{ number_format($b['revenue']) }}</td><td class="text-end">{{ number_format($b['expenses']) }}</td><td class="text-end">{{ $b['employees'] }}</td></tr>@empty<tr><td colspan="5" class="text-muted text-center py-3">No branches.</td></tr>@endforelse</tbody></table></div>
        </div></div>
        @endif
        @if ($kpiStatus)
        <div class="col-lg-6"><div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">KPI Targets</div>
            <ul class="list-group list-group-flush small">@foreach ($kpiStatus as $s)<li class="list-group-item">
                <div class="d-flex justify-content-between"><span>{{ $s['kpi']->name }}</span><span>{{ $s['actual'] !== null ? number_format($s['actual']) : '—' }} / {{ number_format((float) $s['kpi']->target) }}</span></div>
                @if ($s['pct'] !== null)<div class="progress" style="height:6px"><div class="progress-bar {{ $s['pct'] >= 100 ? 'bg-success' : ($s['pct'] >= 60 ? 'bg-info' : 'bg-warning') }}" style="width:{{ min(100,$s['pct']) }}%"></div></div>@endif
            </li>@endforeach</ul>
        </div></div>
        @endif
        @if ($insights->isNotEmpty())
        <div class="col-lg-6"><div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">Management Insights</div>
            <ul class="list-group list-group-flush small">@foreach ($insights as $i)<li class="list-group-item d-flex justify-content-between align-items-start">
                <div><span class="badge {{ $i->severity==='warning'?'text-bg-warning':($i->severity==='critical'?'text-bg-danger':'text-bg-info') }}">{{ $i->category }}</span> {{ $i->title }}<div class="text-muted">{{ $i->detail }}</div></div>
                <form method="POST" action="{{ route('management.insights.ack', $i) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Ack</button></form></li>@endforeach</ul>
        </div></div>
        @endif
    </div>
    <div class="offcanvas offcanvas-end" id="kpiDrawer" style="width:400px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Set KPI Target</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('management.kpis.store') }}" class="offcanvas-body d-flex flex-column gap-2">@csrf
            <select name="key" class="form-select">@foreach (['revenue'=>'Revenue','patients'=>'New patients','appointments'=>'Appointments','appointments_completed'=>'Completed appointments','visits'=>'Visits'] as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
            <input name="name" class="form-control" placeholder="Display name" required>
            <input type="number" step="0.01" name="target" class="form-control" placeholder="Target value" required>
            <select name="unit" class="form-select"><option value="currency">Currency</option><option value="count">Count</option><option value="percent">Percent</option></select>
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Save</button></div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        new Chart(trendChart, {type:'bar',data:{labels:@json(array_column($trend,'label')),datasets:[{label:'Revenue',data:@json(array_column($trend,'revenue')),backgroundColor:'#0f2a43'}]},options:{plugins:{legend:{display:false}}}});
        new Chart(expChart, {type:'doughnut',data:{labels:@json(array_column($expenseCats,'label')),datasets:[{data:@json(array_column($expenseCats,'total')),backgroundColor:['#0f2a43','#1f6f43','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#64748b','#94a3b8']}]},options:{plugins:{legend:{position:'bottom'}}}});
        new Chart(patChart, {type:'line',data:{labels:@json(array_column($patientGrowth,'label')),datasets:[{label:'New patients',data:@json(array_column($patientGrowth,'new')),borderColor:'#1f6f43',tension:.3}]},options:{plugins:{legend:{display:false}}}});
    </script>
</x-app-layout>
