<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClinicSetting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    private const KEYS = [
        'clinic_name', 'clinic_address', 'clinic_phone', 'clinic_email', 'clinic_description',
        'clinic_website', 'clinic_reg_number', 'clinic_tax_id', 'receipt_footer', 'signature_label',
        'report_footer', 'report_signature',
        'currency', 'timezone', 'date_format', 'session_timeout_minutes',
        'patient_number_prefix', 'patient_require_phone', 'patient_require_dob',
        'patient_require_sex', 'patient_emergency_contact_enabled', 'patient_duplicate_warning_enabled',
        'visit_reference_prefix', 'visit_multiple_services', 'visit_require_service',
        'visit_require_notes', 'visit_correction_window_hours',
        'expense_reference_prefix', 'expense_approval_mode', 'expense_receipts_enabled', 'payment_methods',
        'billing_enabled', 'invoice_reference_prefix', 'payment_reference_prefix', 'refund_reference_prefix',
        'invoice_after_treatment', 'allow_discounts',
        'expense_approval_threshold', 'expense_max_staff_amount', 'expense_receipt_required',
        'expense_payment_step_enabled', 'expense_resubmit_allowed',
        'appointments_enabled', 'appointment_reference_prefix',
        'notify_patient_registered', 'notify_expense_submitted', 'notify_invoice_created',
        'notify_payment_recorded', 'notify_refund_recorded', 'notify_followup_due',
        'notify_settings_changed', 'notify_user_deactivated',
        'notify_sale_recorded', 'notify_low_stock', 'notify_purchase_received', 'notify_return_recorded',
        'tax_enabled', 'tax_percent', 'staff_can_pos', 'pos_allow_backorders',
        'sale_prefix', 'return_prefix', 'purchase_prefix',
    ];

    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => ClinicSetting::pluck('setting_value', 'setting_key'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'clinic_name' => ['required', 'string', 'max:255'],
            'clinic_address' => ['nullable', 'string', 'max:255'],
            'clinic_phone' => ['nullable', 'string', 'max:50'],
            'clinic_email' => ['nullable', 'email', 'max:255'],
            'clinic_description' => ['nullable', 'string', 'max:1000'],
            'clinic_website' => ['nullable', 'string', 'max:255'],
            'clinic_reg_number' => ['nullable', 'string', 'max:120'],
            'clinic_tax_id' => ['nullable', 'string', 'max:120'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
            'signature_label' => ['nullable', 'string', 'max:120'],
            'clinic_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp|max:2048'],
            'tax_enabled' => ['boolean'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'staff_can_pos' => ['boolean'],
            'pos_allow_backorders' => ['boolean'],
            'sale_prefix' => ['required', 'string', 'max:10'],
            'return_prefix' => ['required', 'string', 'max:10'],
            'purchase_prefix' => ['required', 'string', 'max:10'],
            'delivery_prefix' => ['required', 'string', 'max:10'],
            'payment_verification_methods' => ['nullable', 'string', 'max:500'],
            'invoice_terms' => ['nullable', 'string', 'max:1000'],
            'invoice_due_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'office_payment_number' => ['nullable', 'string', 'max:60'],
            'office_payment_name' => ['nullable', 'string', 'max:160'],
            'office_payment_instructions' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'bank_account_name' => ['nullable', 'string', 'max:160'],
            'bank_account_number' => ['nullable', 'string', 'max:60'],
            'bank_branch' => ['nullable', 'string', 'max:120'],
            'bank_instructions' => ['nullable', 'string', 'max:500'],
            'report_footer' => ['nullable', 'string', 'max:500'],
            'report_signature' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:64'],
            'date_format' => ['required', 'string', 'max:20'],
            'session_timeout_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'patient_number_prefix' => ['required', 'string', 'max:10'],
            'patient_require_phone' => ['boolean'],
            'patient_require_dob' => ['boolean'],
            'patient_require_sex' => ['boolean'],
            'patient_emergency_contact_enabled' => ['boolean'],
            'patient_duplicate_warning_enabled' => ['boolean'],
            'visit_reference_prefix' => ['required', 'string', 'max:10'],
            'visit_multiple_services' => ['boolean'],
            'visit_require_service' => ['boolean'],
            'visit_require_notes' => ['boolean'],
            'visit_correction_window_hours' => ['integer', 'min:0', 'max:720'],
            'expense_reference_prefix' => ['required', 'string', 'max:10'],
            'expense_approval_mode' => ['boolean'],
            'expense_receipts_enabled' => ['boolean'],
            'payment_methods' => ['required', 'string', 'max:500'],
            'billing_enabled' => ['boolean'],
            'invoice_reference_prefix' => ['required', 'string', 'max:10'],
            'payment_reference_prefix' => ['required', 'string', 'max:10'],
            'refund_reference_prefix' => ['required', 'string', 'max:10'],
            'invoice_after_treatment' => ['boolean'],
            'allow_discounts' => ['boolean'],
            'expense_approval_threshold' => ['nullable', 'numeric', 'min:0'],
            'expense_max_staff_amount' => ['nullable', 'numeric', 'min:0'],
            'expense_receipt_required' => ['boolean'],
            'expense_payment_step_enabled' => ['boolean'],
            'expense_resubmit_allowed' => ['boolean'],
            'appointments_enabled' => ['boolean'],
            'appointment_reference_prefix' => ['required', 'string', 'max:10'],
            'notify_patient_registered' => ['boolean'],
            'notify_expense_submitted' => ['boolean'],
            'notify_invoice_created' => ['boolean'],
            'notify_payment_recorded' => ['boolean'],
            'notify_refund_recorded' => ['boolean'],
            'notify_followup_due' => ['boolean'],
            'notify_settings_changed' => ['boolean'],
            'notify_user_deactivated' => ['boolean'],
        ]);

        $booleans = [
            'patient_require_phone', 'patient_require_dob', 'patient_require_sex',
            'patient_emergency_contact_enabled', 'patient_duplicate_warning_enabled',
            'visit_multiple_services', 'visit_require_service', 'visit_require_notes',
            'expense_approval_mode', 'expense_receipts_enabled',
            'billing_enabled', 'invoice_after_treatment', 'allow_discounts',
            'expense_receipt_required', 'expense_payment_step_enabled', 'expense_resubmit_allowed',
            'appointments_enabled',
            'notify_patient_registered', 'notify_expense_submitted', 'notify_invoice_created',
            'notify_payment_recorded', 'notify_refund_recorded', 'notify_followup_due',
            'notify_settings_changed', 'notify_user_deactivated',
            'notify_sale_recorded', 'notify_low_stock', 'notify_purchase_received', 'notify_return_recorded',
            'tax_enabled', 'staff_can_pos', 'pos_allow_backorders',
        ];

        foreach ($booleans as $key) {
            $validated[$key] = $request->boolean($key) ? '1' : '0';
        }

        if ($request->hasFile('clinic_logo')) {
            $path = $request->file('clinic_logo')->store('branding', 'public');
            $old = \App\Support\Settings::get('clinic_logo');
            if ($old && $old !== $path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
            }
            $validated['clinic_logo'] = $path;
        }

        $changes = [];

        foreach (self::KEYS as $key) {
            if (! array_key_exists($key, $validated)) {
                continue;
            }
            $old = \App\Support\Settings::get($key);
            if ((string) $old !== (string) $validated[$key]) {
                \App\Support\Settings::set($key, $validated[$key], $request->user()->id);
                $changes[$key] = ['old' => $old, 'new' => $validated[$key]];
            }
        }

        if ($changes) {
            AuditLogger::log('settings.updated', 'clinic_setting', null, null, $changes);

            \App\Services\NotificationService::notifyAdmins(
                'settings.changed', 'System settings changed',
                "{$request->user()->name} updated ".count($changes).' setting(s): '.implode(', ', array_slice(array_keys($changes), 0, 5)),
                route('admin.settings.edit'),
            );
        }

        return back()->with('success', 'Settings saved.');
    }
}
