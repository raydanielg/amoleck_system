<x-app-layout>
    <x-slot name="header">Edit Service — {{ $service->name }}</x-slot>
    <div class="card shadow-sm border-0" style="max-width: 850px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.services.update', $service) }}">
                @csrf
                @method('PUT')
                @include('admin.services._form')
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
