<x-app-layout>
    <x-slot name="header">{{ __('Assessments — '.$patient->full_name) }}</x-slot>
    <x-slot name="headerActions">
        @if (auth()->user()->isAdmin() || auth()->user()->hasPermission('clinical.assess'))
            <a href="{{ route('assessments.create',$patient) }}" class="btn btn-primary btn-sm"><i class="hgi-stroke hgi-plus-sign me-1"></i>New Assessment</a>
        @endif
        <a href="{{ route('patients.show',$patient) }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </x-slot>
    <div class="card shadow-sm border-0">
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Reference</th><th>Date</th><th>Assessed by</th><th>Impression</th><th>Visit</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($assessments as $a)
                    <tr>
                        <td class="fw-semibold">{{ $a->reference }}</td>
                        <td class="small">{{ $a->assessed_at->format('d M Y H:i') }}</td>
                        <td class="small">{{ $a->assessor->name }}</td>
                        <td class="small text-muted">{{ Str::limit($a->pt_impression, 60) }}</td>
                        <td class="small">{{ $a->visit?->visit_reference ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('assessments.show',$a) }}" class="btn btn-sm btn-outline-primary">View</a>
                            <a href="{{ route('assessments.print',$a) }}" class="btn btn-sm btn-outline-secondary" target="_blank">Print</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No assessments recorded</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="card-footer bg-white">{{ $assessments->links() }}</div>
    </div>
</x-app-layout>
