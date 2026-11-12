<x-app-layout>
    <x-slot name="header">{{ __('Register Patient') }}</x-slot>

    <div class="card shadow-sm border-0" style="max-width: 900px;">
        <div class="card-body">
            @if (session('warning'))
                <div class="alert alert-warning d-flex align-items-center justify-content-between">
                    <span><i class="hgi-stroke hgi-alert-02 me-2"></i>{{ session('warning') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('patients.store') }}">
                @csrf
                @include('patients._form', ['patient' => null])

                @if (session('duplicate_ids'))
                    <div class="form-check mt-3">
                        <input type="checkbox" name="confirm_duplicate" value="1" class="form-check-input" id="confirm_duplicate">
                        <label for="confirm_duplicate" class="form-check-label">Register anyway — this is not a duplicate.</label>
                    </div>
                @endif

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Patient</button>
                    <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
