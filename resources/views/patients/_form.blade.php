@php
    $requirePhone = \App\Support\Settings::bool('patient_require_phone');
    $requireDob = \App\Support\Settings::bool('patient_require_dob', true);
    $requireSex = \App\Support\Settings::bool('patient_require_sex', true);
    $emergency = \App\Support\Settings::bool('patient_emergency_contact_enabled', true);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Full Name <span class="text-danger">*</span></label>
        <input type="text" name="full_name" value="{{ old('full_name', $patient->full_name ?? '') }}" class="form-control @error('full_name') is-invalid @enderror" required>
        @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Sex @if($requireSex)<span class="text-danger">*</span>@endif</label>
        <select name="sex" class="form-select @error('sex') is-invalid @enderror" @if($requireSex) required @endif>
            <option value="">Select...</option>
            @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say'] as $value => $label)
                <option value="{{ $value }}" @selected(old('sex', $patient->sex ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('sex')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Date of Birth @if($requireDob)<span class="text-danger">*</span>@endif</label>
        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', isset($patient) && $patient->date_of_birth ? $patient->date_of_birth->format('Y-m-d') : '') }}" max="{{ now()->toDateString() }}" class="form-control @error('date_of_birth') is-invalid @enderror" @if($requireDob) required @endif>
        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone @if($requirePhone)<span class="text-danger">*</span>@endif</label>
        <input type="text" name="phone" value="{{ old('phone', $patient->phone ?? '') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="e.g. 0712 345 678" @if($requirePhone) required @endif>
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Address</label>
        <input type="text" name="address" value="{{ old('address', $patient->address ?? '') }}" class="form-control @error('address') is-invalid @enderror">
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    @if ($emergency)
        <div class="col-12"><hr class="my-1"><div class="text-muted small fw-semibold">Emergency Contact (optional)</div></div>
        <div class="col-md-4">
            <label class="form-label">Name</label>
            <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $patient->emergency_contact_name ?? '') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Relationship</label>
            <input type="text" name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship', $patient->emergency_contact_relationship ?? '') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Phone</label>
            <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $patient->emergency_contact_phone ?? '') }}" class="form-control">
        </div>
    @endif

    <div class="col-12">
        <label class="form-label">Relevant Medical History</label>
        <textarea name="medical_history" rows="3" class="form-control @error('medical_history') is-invalid @enderror" placeholder="Allergies, conditions, previous injuries...">{{ old('medical_history', $patient->medical_history ?? '') }}</textarea>
        @error('medical_history')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
