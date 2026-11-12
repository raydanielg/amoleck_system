<x-app-layout>
    <x-slot name="header">{{ __('Invoice Preview — '.$invoice->invoice_reference) }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-primary btn-sm"><i class="hgi-stroke hgi-download-01 me-1"></i>Download PDF</a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="hgi-stroke hgi-printer me-1"></i>Print</button>
        <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </x-slot>
    <div class="mx-auto bg-white shadow-sm border rounded" style="max-width:820px">
        <iframe srcdoc="{{ e(view('pdf.invoice', ['invoice' => $invoice, 'clinic' => $clinic])->render()) }}" style="width:100%; min-height:1000px; border:0" title="Invoice preview"></iframe>
    </div>
</x-app-layout>
