<x-app-layout>
    <x-slot name="header">{{ __('Notification Rules') }}</x-slot>
    <p class="text-muted small">Automatic SMS sent when events happen. Requires a configured &amp; enabled gateway.</p>
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>Event</th><th>Template</th><th>Delay</th><th>Enabled</th><th></th></tr></thead>
                <tbody>
                    @foreach ($rules as $r)
                        <tr>
                            <td class="fw-semibold">{{ $r->label }}<div class="small text-muted fw-normal">{{ $r->event_key }}</div></td>
                            <td>
                                <form method="POST" action="{{ route('admin.communication.rules.update', $r) }}" class="d-flex gap-2 align-items-center">
                                    @csrf @method('PUT')
                                    <select name="template_id" class="form-select form-select-sm" style="width:220px"><option value="">— choose template —</option>@foreach ($templates as $t)<option value="{{ $t->id }}" @selected($r->template_id === $t->id)>{{ $t->name }}</option>@endforeach</select>
                                    <input type="number" name="delay_minutes" value="{{ $r->delay_minutes }}" min="0" class="form-control form-control-sm" style="width:80px" title="Delay (min)">
                            </td>
                            <td>
                                    <div class="form-check form-switch mb-0"><input type="checkbox" name="enabled" value="1" class="form-check-input" @checked($r->enabled)></div>
                            </td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-primary">Save</button></form></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
