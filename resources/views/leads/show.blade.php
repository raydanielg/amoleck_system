<x-app-layout>
    <x-slot name="header">{{ $lead->full_name }} <span class="text-muted small">{{ $lead->lead_reference }}</span></x-slot>
    <x-slot name="headerActions">
        @if (auth()->user()->hasPermission('leads.update'))
            <a href="{{ route('leads.edit', $lead) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
        @endif
        <a href="{{ route('leads.index') }}" class="btn btn-sm btn-outline-secondary">All Leads</a>
    </x-slot>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white fw-semibold">Lead Details</div>
                <dl class="row small mb-0 card-body py-2">
                    <dt class="col-5 text-muted">Phone</dt><dd class="col-7">{{ $lead->phone }}</dd>
                    <dt class="col-5 text-muted">Email</dt><dd class="col-7">{{ $lead->email ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Source</dt><dd class="col-7">{{ $lead->source->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Campaign / Ad</dt><dd class="col-7">{{ $lead->campaign_name ?? '—' }}{{ $lead->ad_name ? ' / '.$lead->ad_name : '' }}</dd>
                    <dt class="col-5 text-muted">Interested Service</dt><dd class="col-7">{{ $lead->interestedService->name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Received</dt><dd class="col-7">{{ $lead->received_at->format('d M Y') }}</dd>
                    <dt class="col-5 text-muted">Expected Visit</dt><dd class="col-7">{{ $lead->expected_visit_at?->format('d M Y') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Interest</dt><dd class="col-7">{{ $lead->interest_level ? str_replace('_', ' ', $lead->interest_level) : '—' }}</dd>
                    <dt class="col-5 text-muted">Contact Status</dt><dd class="col-7"><span class="badge text-bg-light border">{{ str_replace('_', ' ', $lead->contact_status) }}</span></dd>
                    <dt class="col-5 text-muted">Sales Manager</dt><dd class="col-7">{{ $lead->assignee->name ?? 'Unassigned' }}{{ $lead->assigner ? ' · by '.$lead->assigner->name : '' }}</dd>
                    <dt class="col-5 text-muted">Next Follow-up</dt>
                    <dd class="col-7">
                        @if ($lead->next_followup_at)
                            <span class="badge {{ $lead->followUpOverdue() ? 'text-bg-danger' : 'text-bg-warning' }}">{{ $lead->next_followup_at->format('d M Y') }}</span>
                        @else — @endif
                    </dd>
                    @if ($lead->isConverted())
                        <dt class="col-5 text-muted">Patient</dt><dd class="col-7"><a href="{{ route('patients.show', $lead->patient) }}">{{ $lead->patient->patient_number }}</a> · converted {{ $lead->converted_at->format('d M Y') }}</dd>
                    @endif
                </dl>
            </div>

            @if (! $lead->isConverted())
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white fw-semibold">Visit Status</div>
                    <div class="card-body">
                        <div class="mb-2"><span class="badge text-bg-primary">{{ $visitLabels[$lead->visit_status] ?? $lead->visit_status }}</span></div>
                        @forelse ($transitions as $next)
                            <form method="POST" action="{{ route('leads.status', $lead) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="visit_status" value="{{ $next }}">
                                <button class="btn btn-sm btn-outline-primary mb-1">{{ $visitLabels[$next] }}</button>
                            </form>
                        @empty
                            <p class="small text-muted mb-0">This lead has reached a final status.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            @if ($managesAll)
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white fw-semibold">Assign Sales Manager</div>
                    <form method="POST" action="{{ route('leads.assign', $lead) }}" class="card-body">
                        @csrf
                        <select name="assigned_to" class="form-select mb-2" required>
                            <option value="">— Select manager —</option>
                            @foreach ($managers as $m)<option value="{{ $m->id }}" @selected($lead->assigned_to === $m->id)>{{ $m->name }}</option>@endforeach
                        </select>
                        <button class="btn btn-sm btn-primary w-100">{{ $lead->assigned_to ? 'Reassign' : 'Assign' }}</button>
                    </form>
                </div>
            @endif

            @if (! $lead->isConverted() && auth()->user()->hasPermission('leads.convert'))
                <div class="card shadow-sm border-0 border-success mb-3">
                    <div class="card-header bg-white fw-semibold text-success">Convert to Patient</div>
                    <form method="POST" action="{{ route('leads.convert', $lead) }}" class="card-body">
                        @csrf
                        @if ($patientMatches->isNotEmpty())
                            <label class="form-label small">Possible existing patient (verify identity):</label>
                            <select name="patient_id" class="form-select form-select-sm mb-2">
                                <option value="">— Not an existing patient —</option>
                                @foreach ($patientMatches as $p)<option value="{{ $p->id }}">{{ $p->full_name }} · {{ $p->patient_number }} · {{ $p->phone }}</option>@endforeach
                            </select>
                            <div class="text-center small text-muted mb-2">— or —</div>
                        @endif
                        <div class="form-check mb-2">
                            <input type="checkbox" name="create_patient" value="1" class="form-check-input" id="createPatient">
                            <label class="form-check-label small" for="createPatient">Register as a new patient</label>
                        </div>
                        <button class="btn btn-sm btn-success w-100">Convert Lead</button>
                    </form>
                </div>
            @endif

            @if (auth()->user()->hasPermission('leads.delete'))
                <form method="POST" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('Delete this lead? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger w-100">Delete Lead</button>
                </form>
            @endif
        </div>

        <div class="col-lg-8">
            @if (auth()->user()->hasPermission('leads.followup') && ! $lead->isConverted())
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white fw-semibold">Record Follow-up</div>
                    <form method="POST" action="{{ route('leads.follow-ups.store', $lead) }}" class="card-body row g-2">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label">Contacted?</label>
                            <select name="reached" class="form-select form-select-sm">
                                <option value="1">Reached</option>
                                <option value="0">Not reached</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Interest</label>
                            <select name="interest_level" class="form-select form-select-sm">
                                <option value="">— Unchanged —</option>
                                <option value="interested">Interested</option>
                                <option value="needs_info">Needs more info</option>
                                <option value="not_interested">Not interested</option>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Outcome</label><input name="outcome" class="form-control form-control-sm" placeholder="e.g. Promised to visit"></div>
                        <div class="col-md-3"><label class="form-label">Next Follow-up</label><input type="date" name="next_followup_at" class="form-control form-control-sm"></div>
                        <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Conversation summary, agreements..."></textarea></div>
                        <div class="col-12"><button class="btn btn-sm btn-primary">Save Follow-up</button></div>
                    </form>
                </div>
            @endif

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white fw-semibold">Follow-up History ({{ $lead->followUps->count() }})</div>
                <div class="card-body">
                    @forelse ($lead->followUps as $fu)
                        <div class="border-start border-3 {{ $fu->reached ? 'border-success' : 'border-danger' }} ps-3 mb-3">
                            <div class="d-flex justify-content-between">
                                <strong class="small">{{ $fu->reached ? 'Reached' : 'Not reached' }}{{ $fu->interest_level ? ' · '.str_replace('_', ' ', $fu->interest_level) : '' }}</strong>
                                <span class="small text-muted">{{ $fu->contacted_at->format('d M Y H:i') }} · {{ $fu->user->name ?? 'system' }}</span>
                            </div>
                            @if ($fu->outcome)<div class="small">{{ $fu->outcome }}</div>@endif
                            @if ($fu->notes)<div class="small text-muted">{{ $fu->notes }}</div>@endif
                            @if ($fu->next_followup_at)<div class="small"><span class="badge text-bg-warning">Next: {{ $fu->next_followup_at->format('d M Y') }}</span></div>@endif
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No follow-ups recorded yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Activity Timeline</div>
                <div class="card-body">
                    @foreach ($lead->activities as $activity)
                        <div class="d-flex justify-content-between small py-1 border-bottom">
                            <span><span class="badge text-bg-light border me-1">{{ $activity->type }}</span>{{ $activity->description }}</span>
                            <span class="text-muted text-nowrap ms-2">{{ $activity->created_at->format('d M H:i') }} · {{ $activity->user->name ?? 'system' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
