<x-app-layout>
    <x-slot name="header">New Visit — {{ $patient->full_name }} <span class="text-muted fs-6 fw-normal">({{ $patient->patient_number }})</span></x-slot>

    <div class="card shadow-sm border-0" style="max-width: 1000px;">
        <div class="card-body">
            <form method="POST" action="{{ route('patients.visits.store', $patient) }}">
                @csrf
                @include('visits._form', ['visit' => null])

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" name="complete" value="1" class="btn btn-primary">Save & Complete</button>
                    <button type="submit" class="btn btn-outline-primary">Save as Draft</button>
                    <a href="{{ route('patients.show', $patient) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
