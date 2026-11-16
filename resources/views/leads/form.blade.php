<x-app-layout>
    <x-slot name="header">{{ $lead ? 'Edit Lead '.$lead->lead_reference : 'Register New Lead' }}</x-slot>
    <x-slot name="headerActions"><a href="{{ $lead ? route('leads.show', $lead) : route('leads.index') }}" class="btn btn-sm btn-outline-secondary">Back</a></x-slot>

    <div class="card shadow-sm border-0">
        <form method="POST" action="{{ $lead ? route('leads.update', $lead) : route('leads.store') }}" class="card-body row g-3">
            @csrf @if ($lead) @method('PUT') @endif
            <div class="col-md-4"><label class="form-label">Full Name *</label><input name="full_name" class="form-control" value="{{ old('full_name', $lead->full_name ?? '') }}" required></div>
            <div class="col-md-4"><label class="form-label">Phone Number *</label><input name="phone" class="form-control" value="{{ old('phone', $lead->phone ?? '') }}" placeholder="0712 345 678" required></div>
            <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $lead->email ?? '') }}"></div>
            <div class="col-md-4"><label class="form-label">Date Received *</label><input type="date" name="received_at" class="form-control" value="{{ old('received_at', optional($lead->received_at ?? now())->format('Y-m-d')) }}" required></div>
            <div class="col-md-4">
                <label class="form-label">Lead Source</label>
                <select name="lead_source_id" class="form-select">
                    <option value="">— Select source —</option>
                    @foreach ($sources as $src)<option value="{{ $src->id }}" @selected(old('lead_source_id', $lead->lead_source_id ?? '') == $src->id)>{{ $src->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Interested Service</label>
                <select name="interested_service_id" class="form-select">
                    <option value="">— Select service —</option>
                    @foreach ($services as $svc)<option value="{{ $svc->id }}" @selected(old('interested_service_id', $lead->interested_service_id ?? '') == $svc->id)>{{ $svc->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Assign to Sales Manager</label>
                <select name="assigned_to" class="form-select">
                    <option value="">— Unassigned —</option>
                    @foreach ($managers as $m)<option value="{{ $m->id }}" @selected(old('assigned_to', $lead->assigned_to ?? '') == $m->id)>{{ $m->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-4"><label class="form-label">Expected Visit Date</label><input type="date" name="expected_visit_at" class="form-control" value="{{ old('expected_visit_at', optional($lead->expected_visit_at ?? null)->format('Y-m-d')) }}"></div>
            <div class="col-md-4"><label class="form-label">Campaign Name</label><input name="campaign_name" class="form-control" value="{{ old('campaign_name', $lead->campaign_name ?? '') }}"></div>
            <div class="col-md-4"><label class="form-label">Ad Name</label><input name="ad_name" class="form-control" value="{{ old('ad_name', $lead->ad_name ?? '') }}"></div>
            <div class="col-md-4"><label class="form-label">Ad Platform</label><input name="platform" class="form-control" value="{{ old('platform', $lead->platform ?? '') }}" placeholder="e.g. Facebook Ads"></div>
            <div class="col-md-6"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3">{{ old('description', $lead->description ?? '') }}</textarea></div>
            <div class="col-md-6"><label class="form-label">Initial Notes</label><textarea name="initial_notes" class="form-control" rows="3">{{ old('initial_notes', $lead->initial_notes ?? '') }}</textarea></div>
            <div class="col-12"><button class="btn btn-primary">{{ $lead ? 'Save Changes' : 'Register Lead' }}</button></div>
        </form>
    </div>
</x-app-layout>
