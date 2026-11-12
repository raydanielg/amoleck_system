<x-app-layout>
    <x-slot name="header">{{ __('Acknowledgment Report — '.$announcement->title) }}</x-slot>
    <x-slot name="headerActions"><a href="{{ route('admin.document-board.index') }}" class="btn btn-outline-secondary btn-sm">Back</a></x-slot>
    @php
        $counts = $receipts->groupBy('status')->map->count();
        $badge = ['delivered'=>'secondary','viewed'=>'info','acknowledged'=>'success','declined'=>'danger','expired'=>'dark'];
    @endphp
    <div class="row g-3 mb-3">
        @foreach (['delivered','viewed','acknowledged','declined','expired'] as $st)
            <div class="col"><div class="card shadow-sm border-0"><div class="card-body py-2 text-center">
                <div class="fs-5 fw-bold">{{ $counts[$st] ?? 0 }}</div><div class="small text-muted text-capitalize">{{ $st }}</div>
            </div></div></div>
        @endforeach
    </div>
    <div class="card shadow-sm border-0">
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Employee</th><th>Department</th><th>Status</th><th>Delivered</th><th>Viewed</th><th>Acknowledged</th><th>Comment</th></tr></thead>
            <tbody>
                @foreach ($receipts as $r)
                    <tr>
                        <td>{{ $r->user->name }}</td>
                        <td class="small">{{ $r->user->employee?->department?->name ?? '—' }}</td>
                        <td><span class="badge text-bg-{{ $badge[$r->status] ?? 'secondary' }}">{{ ucfirst($r->status) }}</span></td>
                        <td class="small text-muted">{{ $r->delivered_at?->toDayDateTimeString() }}</td>
                        <td class="small text-muted">{{ $r->viewed_at?->toDayDateTimeString() ?? '—' }}</td>
                        <td class="small text-muted">{{ $r->acknowledged_at?->toDayDateTimeString() ?? '—' }}</td>
                        <td class="small">{{ $r->comment }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    </div>
</x-app-layout>
