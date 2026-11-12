@php
    $flashTypes = [
        'success' => ['success', 'hgi-checkmark-circle-02', __('Success')],
        'status' => ['success', 'hgi-checkmark-circle-02', __('Success')],
        'error' => ['danger', 'hgi-cancel-circle', __('Error')],
        'warning' => ['warning', 'hgi-alert-02', __('Warning')],
        'info' => ['info', 'hgi-information-circle', __('Info')],
    ];

    $toasts = collect($flashTypes)
        ->filter(fn ($t, $key) => session($key))
        ->map(fn ($t, $key) => [...$t, [session($key)]])
        ->values();

    if ($errors->any()) {
        $toasts->push(['danger', 'hgi-cancel-circle', __('Error'), $errors->all()]);
    }
@endphp

<div class="toast-container position-fixed top-0 end-0 p-3">
    @foreach ($toasts as [$type, $icon, $title, $messages])
        <div class="toast app-toast app-toast-{{ $type }} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body">
                <span class="app-toast-icon"><i class="hgi-stroke {{ $icon }}"></i></span>
                <span class="app-toast-text">
                    <strong>{{ $title }}:</strong> {{ implode(' · ', $messages) }}
                </span>
                <button type="button" class="btn-close app-toast-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    @endforeach
</div>
