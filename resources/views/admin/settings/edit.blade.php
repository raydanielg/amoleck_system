@php $s = fn (string $key, $default = '') => old($key, $settings[$key] ?? $default); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('System Settings') }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('admin.expense-categories.index') }}" class="btn btn-outline-secondary btn-sm">Expense Categories</a>
    </x-slot>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card shadow-sm border-0 mb-3" style="max-width: 950px;">
            <div class="card-header bg-white fw-semibold">Clinic Profile</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Clinic Name <span class="text-danger">*</span></label>
                    <input type="text" name="clinic_name" value="{{ $s('clinic_name') }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="clinic_email" value="{{ $s('clinic_email') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="clinic_phone" value="{{ $s('clinic_phone') }}" class="form-control">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Address</label>
                    <input type="text" name="clinic_address" value="{{ $s('clinic_address') }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="clinic_description" rows="2" class="form-control">{{ $s('clinic_description') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Report Footer</label>
                    <input type="text" name="report_footer" value="{{ $s('report_footer') }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Report Signature Line</label>
                    <input type="text" name="report_signature" value="{{ $s('report_signature') }}" class="form-control">
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3" style="max-width: 950px;">
            <div class="card-header bg-white fw-semibold">Regional Settings</div>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency" value="{{ $s('currency', 'TZS') }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Timezone</label>
                    <input type="text" name="timezone" value="{{ $s('timezone', 'Africa/Dar_es_Salaam') }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date Format</label>
                    <select name="date_format" class="form-select">
                        @foreach (['d/m/Y' => 'DD/MM/YYYY', 'Y-m-d' => 'YYYY-MM-DD', 'd-m-Y' => 'DD-MM-YYYY'] as $v => $l)
                            <option value="{{ $v }}" @selected($s('date_format', 'd/m/Y') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Session Timeout (min)</label>
                    <input type="number" name="session_timeout_minutes" value="{{ $s('session_timeout_minutes', '120') }}" min="5" max="1440" class="form-control" required>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3" style="max-width: 950px;">
            <div class="card-header bg-white fw-semibold">Patient Settings</div>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label">Patient No. Prefix</label>
                    <input type="text" name="patient_number_prefix" value="{{ $s('patient_number_prefix', 'PT') }}" class="form-control" required>
                </div>
                <div class="col-12 d-flex flex-wrap gap-4">
                    <div class="form-check"><input type="checkbox" name="patient_require_phone" value="1" class="form-check-input" @checked($s('patient_require_phone'))><label class="form-check-label">Phone required</label></div>
                    <div class="form-check"><input type="checkbox" name="patient_require_dob" value="1" class="form-check-input" @checked($s('patient_require_dob', '1'))><label class="form-check-label">Date of birth required</label></div>
                    <div class="form-check"><input type="checkbox" name="patient_require_sex" value="1" class="form-check-input" @checked($s('patient_require_sex', '1'))><label class="form-check-label">Sex required</label></div>
                    <div class="form-check"><input type="checkbox" name="patient_emergency_contact_enabled" value="1" class="form-check-input" @checked($s('patient_emergency_contact_enabled', '1'))><label class="form-check-label">Emergency contact fields</label></div>
                    <div class="form-check"><input type="checkbox" name="patient_duplicate_warning_enabled" value="1" class="form-check-input" @checked($s('patient_duplicate_warning_enabled', '1'))><label class="form-check-label">Duplicate patient warning</label></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3" style="max-width: 950px;">
            <div class="card-header bg-white fw-semibold">Visit Settings</div>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label">Visit Ref. Prefix</label>
                    <input type="text" name="visit_reference_prefix" value="{{ $s('visit_reference_prefix', 'VS') }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Staff Correction Window (hrs)</label>
                    <input type="number" name="visit_correction_window_hours" value="{{ $s('visit_correction_window_hours', '0') }}" min="0" class="form-control">
                    <div class="form-text">0 = completed visits are read-only for staff.</div>
                </div>
                <div class="col-12 d-flex flex-wrap gap-4">
                    <div class="form-check"><input type="checkbox" name="visit_multiple_services" value="1" class="form-check-input" @checked($s('visit_multiple_services', '1'))><label class="form-check-label">Allow multiple services per visit</label></div>
                    <div class="form-check"><input type="checkbox" name="visit_require_service" value="1" class="form-check-input" @checked($s('visit_require_service', '1'))><label class="form-check-label">At least one service required</label></div>
                    <div class="form-check"><input type="checkbox" name="visit_require_notes" value="1" class="form-check-input" @checked($s('visit_require_notes'))><label class="form-check-label">Treatment notes required</label></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3" style="max-width: 950px;">
            <div class="card-header bg-white fw-semibold">Expense Settings</div>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label">Expense Ref. Prefix</label>
                    <input type="text" name="expense_reference_prefix" value="{{ $s('expense_reference_prefix', 'EXP') }}" class="form-control" required>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Payment Methods (comma separated)</label>
                    <input type="text" name="payment_methods" value="{{ $s('payment_methods') }}" class="form-control" required>
                </div>
                <div class="col-12 d-flex flex-wrap gap-4">
                    <div class="form-check"><input type="checkbox" name="expense_approval_mode" value="1" class="form-check-input" @checked($s('expense_approval_mode'))><label class="form-check-label">Enable expense approval workflow</label></div>
                    <div class="form-check"><input type="checkbox" name="expense_receipts_enabled" value="1" class="form-check-input" @checked($s('expense_receipts_enabled', '1'))><label class="form-check-label">Allow receipt attachments</label></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3" style="max-width: 950px;">
            <div class="card-header bg-white fw-semibold">Document Branding & Receipts</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">Document Logo</label>
                    @if ($s('clinic_logo'))<div class="mb-2"><img src="{{ Storage::url($s('clinic_logo')) }}" style="max-height:56px" class="rounded border p-1"></div>@endif
                    <input type="file" name="clinic_logo" class="form-control" accept="image/*">
                    <div class="form-text">Shown on receipts and PDF documents.</div>
                </div>
                <div class="col-md-4"><label class="form-label">Website</label><input type="text" name="clinic_website" value="{{ $s('clinic_website') }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Business Reg. No.</label><input type="text" name="clinic_reg_number" value="{{ $s('clinic_reg_number') }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Tax ID / TIN</label><input type="text" name="clinic_tax_id" value="{{ $s('clinic_tax_id') }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Receipt Footer</label><input type="text" name="receipt_footer" value="{{ $s('receipt_footer', 'Thank you for your business!') }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Signature Label</label><input type="text" name="signature_label" value="{{ $s('signature_label', 'Authorized Signature') }}" class="form-control"></div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3" style="max-width: 950px;">
            <div class="card-header bg-white fw-semibold">Medical Supplies, POS & Tax</div>
            <div class="card-body row g-3">
                <div class="col-md-3"><label class="form-label">Sale Prefix</label><input type="text" name="sale_prefix" value="{{ $s('sale_prefix', 'SAL') }}" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Return Prefix</label><input type="text" name="return_prefix" value="{{ $s('return_prefix', 'RET') }}" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Purchase Prefix</label><input type="text" name="purchase_prefix" value="{{ $s('purchase_prefix', 'PUR') }}" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Delivery Prefix</label><input type="text" name="delivery_prefix" value="{{ $s('delivery_prefix', 'DLV') }}" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Tax %</label><input type="number" step="0.01" min="0" max="100" name="tax_percent" value="{{ $s('tax_percent', '18') }}" class="form-control"></div>
                <div class="col-12 d-flex flex-wrap gap-4">
                    <div class="form-check"><input type="checkbox" name="tax_enabled" value="1" class="form-check-input" @checked($s('tax_enabled'))><label class="form-check-label">Charge tax on product sales</label></div>
                    <div class="form-check"><input type="checkbox" name="staff_can_pos" value="1" class="form-check-input" @checked($s('staff_can_pos'))><label class="form-check-label">Allow Clinic Staff to use POS</label></div>
                    <div class="form-check"><input type="checkbox" name="pos_allow_backorders" value="1" class="form-check-input" @checked($s('pos_allow_backorders'))><label class="form-check-label">Allow selling out-of-stock items</label></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3" style="max-width: 950px;">
            <div class="card-header bg-white fw-semibold">Payment Channels (shown on invoices)</div>
            <div class="card-body row g-3">
                <div class="col-md-4"><label class="form-label">Office Payment Number</label><input type="text" name="office_payment_number" value="{{ $s('office_payment_number') }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Office/Payment Name</label><input type="text" name="office_payment_name" value="{{ $s('office_payment_name') }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Office Payment Instructions</label><input type="text" name="office_payment_instructions" value="{{ $s('office_payment_instructions') }}" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Bank Name</label><input type="text" name="bank_name" value="{{ $s('bank_name') }}" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Account Holder</label><input type="text" name="bank_account_name" value="{{ $s('bank_account_name') }}" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Account Number</label><input type="text" name="bank_account_number" value="{{ $s('bank_account_number') }}" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">Branch</label><input type="text" name="bank_branch" value="{{ $s('bank_branch') }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Bank Payment Instructions</label><input type="text" name="bank_instructions" value="{{ $s('bank_instructions') }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Methods requiring verification</label><input type="text" name="payment_verification_methods" value="{{ $s('payment_verification_methods', 'Bank Transfer,Office Payment Number') }}" class="form-control"><div class="form-text">Comma separated — payments via these stay pending until verified.</div></div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3" style="max-width: 950px;">
            <div class="card-header bg-white fw-semibold">Billing & Revenue</div>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label">Invoice Prefix</label>
                    <input type="text" name="invoice_reference_prefix" value="{{ $s('invoice_reference_prefix', 'INV') }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Prefix</label>
                    <input type="text" name="payment_reference_prefix" value="{{ $s('payment_reference_prefix', 'PAY') }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Refund Prefix</label>
                    <input type="text" name="refund_reference_prefix" value="{{ $s('refund_reference_prefix', 'RFD') }}" class="form-control" required>
                </div>
                <div class="col-md-3"><label class="form-label">Default Due Days</label><input type="number" name="invoice_due_days" value="{{ $s('invoice_due_days', '7') }}" min="0" class="form-control"></div>
                <div class="col-md-9"><label class="form-label">Invoice Terms</label><input type="text" name="invoice_terms" value="{{ $s('invoice_terms') }}" class="form-control"></div>
                <div class="col-12 d-flex flex-wrap gap-4">
                    <div class="form-check"><input type="checkbox" name="billing_enabled" value="1" class="form-check-input" @checked($s('billing_enabled', '1'))><label class="form-check-label">Enable patient billing (invoices & payments)</label></div>
                    <div class="form-check"><input type="checkbox" name="invoice_after_treatment" value="1" class="form-check-input" @checked($s('invoice_after_treatment'))><label class="form-check-label">Invoice after treatment</label></div>
                    <div class="form-check"><input type="checkbox" name="allow_discounts" value="1" class="form-check-input" @checked($s('allow_discounts', '1'))><label class="form-check-label">Allow invoice discounts</label></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3" style="max-width: 950px;">
            <div class="card-header bg-white fw-semibold">Approval Rules</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">Auto-approve below amount</label>
                    <input type="number" name="expense_approval_threshold" value="{{ $s('expense_approval_threshold', '0') }}" min="0" step="0.01" class="form-control">
                    <div class="form-text">0 = all expenses need approval (when approval workflow is on).</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Staff submission limit</label>
                    <input type="number" name="expense_max_staff_amount" value="{{ $s('expense_max_staff_amount', '0') }}" min="0" step="0.01" class="form-control">
                    <div class="form-text">0 = no limit.</div>
                </div>
                <div class="col-12 d-flex flex-wrap gap-4">
                    <div class="form-check"><input type="checkbox" name="expense_receipt_required" value="1" class="form-check-input" @checked($s('expense_receipt_required'))><label class="form-check-label">Receipt attachment required</label></div>
                    <div class="form-check"><input type="checkbox" name="expense_payment_step_enabled" value="1" class="form-check-input" @checked($s('expense_payment_step_enabled'))><label class="form-check-label">Separate "Mark as Paid" step after approval</label></div>
                    <div class="form-check"><input type="checkbox" name="expense_resubmit_allowed" value="1" class="form-check-input" @checked($s('expense_resubmit_allowed', '1'))><label class="form-check-label">Allow resubmission of returned expenses</label></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3" style="max-width: 950px;">
            <div class="card-header bg-white fw-semibold">Appointments</div>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label">Appointment Prefix</label>
                    <input type="text" name="appointment_reference_prefix" value="{{ $s('appointment_reference_prefix', 'APT') }}" class="form-control" required>
                </div>
                <div class="col-12">
                    <div class="form-check"><input type="checkbox" name="appointments_enabled" value="1" class="form-check-input" @checked($s('appointments_enabled', '1'))><label class="form-check-label">Enable appointment scheduling</label></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3" style="max-width: 950px;">
            <div class="card-header bg-white fw-semibold">Notification Preferences (Admin)</div>
            <div class="card-body row g-3">
                <div class="col-12 d-flex flex-wrap gap-4">
                    <div class="form-check"><input type="checkbox" name="notify_patient_registered" value="1" class="form-check-input" @checked($s('notify_patient_registered', '1'))><label class="form-check-label">New patient registered</label></div>
                    <div class="form-check"><input type="checkbox" name="notify_expense_submitted" value="1" class="form-check-input" @checked($s('notify_expense_submitted', '1'))><label class="form-check-label">Expense submitted</label></div>
                    <div class="form-check"><input type="checkbox" name="notify_invoice_created" value="1" class="form-check-input" @checked($s('notify_invoice_created', '1'))><label class="form-check-label">Invoice created</label></div>
                    <div class="form-check"><input type="checkbox" name="notify_payment_recorded" value="1" class="form-check-input" @checked($s('notify_payment_recorded', '1'))><label class="form-check-label">Payment recorded</label></div>
                    <div class="form-check"><input type="checkbox" name="notify_refund_recorded" value="1" class="form-check-input" @checked($s('notify_refund_recorded', '1'))><label class="form-check-label">Refund recorded</label></div>
                    <div class="form-check"><input type="checkbox" name="notify_followup_due" value="1" class="form-check-input" @checked($s('notify_followup_due', '1'))><label class="form-check-label">Follow-up due</label></div>
                    <div class="form-check"><input type="checkbox" name="notify_settings_changed" value="1" class="form-check-input" @checked($s('notify_settings_changed', '1'))><label class="form-check-label">Settings changed</label></div>
                    <div class="form-check"><input type="checkbox" name="notify_user_deactivated" value="1" class="form-check-input" @checked($s('notify_user_deactivated', '1'))><label class="form-check-label">Account deactivated</label></div>
                    <div class="form-check"><input type="checkbox" name="notify_sale_recorded" value="1" class="form-check-input" @checked($s('notify_sale_recorded', '1'))><label class="form-check-label">New product sale</label></div>
                    <div class="form-check"><input type="checkbox" name="notify_low_stock" value="1" class="form-check-input" @checked($s('notify_low_stock', '1'))><label class="form-check-label">Low / out of stock</label></div>
                    <div class="form-check"><input type="checkbox" name="notify_purchase_received" value="1" class="form-check-input" @checked($s('notify_purchase_received', '1'))><label class="form-check-label">Goods received</label></div>
                    <div class="form-check"><input type="checkbox" name="notify_return_recorded" value="1" class="form-check-input" @checked($s('notify_return_recorded', '1'))><label class="form-check-label">Product return</label></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3" style="max-width: 950px;">
            <div class="card-header bg-white fw-semibold">Data & Backups</div>
            <div class="card-body small">
                <dl class="row mb-3">
                    <dt class="col-3 text-muted">Database</dt>
                    <dd class="col-9">{{ config('database.default') }} · {{ config('database.connections.'.config('database.default').'.database') }}</dd>
                    <dt class="col-3 text-muted">Environment</dt>
                    <dd class="col-9">{{ config('app.env') }}</dd>
                </dl>
                <p class="text-muted mb-2">Backups run on the server, not through this interface. Recommended daily schedule:</p>
                <ul class="mb-2">
                    <li><code>php artisan backup:run</code> (if spatie/laravel-backup is installed), or</li>
                    <li>MySQL: <code>mysqldump -u user -p database &gt; backup.sql</code></li>
                    <li>SQLite: copy the database file from <code>database/</code></li>
                    <li>Also archive <code>storage/app/</code> (receipts, uploads) and <code>.env</code> securely.</li>
                </ul>
                <p class="text-muted mb-0">Keep encrypted off-site copies. Test restore procedures regularly — see <code>docs/DEPLOYMENT.md</code>.</p>
            </div>
        </div>

        <div style="max-width: 950px;">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</x-app-layout>
