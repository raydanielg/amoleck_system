<x-app-layout>
    <x-slot name="header">{{ __('Invoice Preview — '.$invoice->invoice_reference) }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-primary btn-sm"><i class="hgi-stroke hgi-download-01 me-1"></i>Download PDF</a>
        <button onclick="document.getElementById('invFrame').contentWindow.print()" class="btn btn-outline-secondary btn-sm"><i class="hgi-stroke hgi-printer me-1"></i>Print</button>
        <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </x-slot>
    <div class="bg-white shadow-sm border rounded" style="max-width:900px;margin:0 auto;overflow-x:auto">
        <iframe id="invFrame" src="{{ route('admin.invoices.preview-frame', $invoice) }}" style="width:100%;border:0;display:block;min-height:600px" title="Invoice preview"></iframe>
    </div>
    <script>
        const f = document.getElementById('invFrame');
        const fit = () => { try { f.style.height = Math.max(600, f.contentDocument.documentElement.scrollHeight + 16) + 'px'; } catch(e){} };
        f.addEventListener('load', fit);
        window.addEventListener('resize', fit);
    </script>
</x-app-layout>
