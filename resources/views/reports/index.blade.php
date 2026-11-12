<x-app-layout>
    <x-slot name="header">{{ __('Reports & Analytics') }}</x-slot>

    @foreach ($groups as $group => $keys)
        <h6 class="text-muted text-uppercase small fw-semibold mt-3 mb-2">{{ $group }}</h6>
        <div class="row g-3 mb-3" style="max-width: 1100px;">
            @foreach ($keys as $key)
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">{{ $types[$key] }}</span>
                            <a href="{{ route('reports.show', $key) }}" class="btn btn-sm btn-outline-primary">Open</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</x-app-layout>
