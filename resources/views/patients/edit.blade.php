<x-app-layout>
    <x-slot name="header">{{ __('Edit Patient') }} — {{ $patient->patient_number }}</x-slot>

    <div class="card shadow-sm border-0" style="max-width: 900px;">
        <div class="card-body">
            <form method="POST" action="{{ route('patients.update', $patient) }}">
                @csrf
                @method('PUT')
                @include('patients._form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('patients.show', $patient) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
