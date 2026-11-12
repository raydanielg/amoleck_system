<x-app-layout>
    <x-slot name="header">Edit Category — {{ $category->name }}</x-slot>
    <div class="card shadow-sm border-0" style="max-width: 700px;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.service-categories.update', $category) }}">
                @csrf
                @method('PUT')
                @include('admin.service-categories._form')
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('admin.service-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
