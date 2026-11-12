<x-app-layout>
    <x-slot name="header">Edit Visit {{ $visit->visit_reference }}</x-slot>

    <div class="card shadow-sm border-0" style="max-width: 1000px;">
        <div class="card-body">
            <form method="POST" action="{{ route('visits.update', $visit) }}">
                @csrf
                @method('PUT')
                @include('visits._form')

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('visits.show', $visit) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
