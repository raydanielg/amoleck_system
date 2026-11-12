<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@amoleck.co.tz'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('Admin@123'),
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'must_change_password' => true,
            ]
        );

        $defaults = [
            'clinic_name' => 'Amoleck Group Company LTD',
            'clinic_address' => '',
            'clinic_phone' => '',
            'clinic_email' => '',
            'clinic_description' => '',
            'report_footer' => '',
            'report_signature' => '',
            'currency' => 'TZS',
            'timezone' => 'Africa/Dar_es_Salaam',
            'date_format' => 'd/m/Y',
            'session_timeout_minutes' => '120',
            'patient_number_prefix' => 'PT',
            'patient_number_next' => '0',
            'patient_require_phone' => '0',
            'patient_require_dob' => '1',
            'patient_require_sex' => '1',
            'patient_emergency_contact_enabled' => '1',
            'patient_duplicate_warning_enabled' => '1',
            'visit_reference_prefix' => 'VS',
            'visit_reference_next' => '0',
            'visit_multiple_services' => '1',
            'visit_require_service' => '1',
            'visit_require_notes' => '0',
            'visit_correction_window_hours' => '0',
            'expense_reference_prefix' => 'EXP',
            'expense_reference_next' => '0',
            'expense_approval_mode' => '0',
            'expense_receipts_enabled' => '1',
            'payment_methods' => 'Cash,Bank Transfer,Mobile Money,Card,Other',
            // Billing & revenue
            'billing_enabled' => '1',
            'invoice_reference_prefix' => 'INV',
            'invoice_reference_next' => '0',
            'payment_reference_prefix' => 'PAY',
            'payment_reference_next' => '0',
            'refund_reference_prefix' => 'RFD',
            'refund_reference_next' => '0',
            'invoice_after_treatment' => '0',
            'allow_discounts' => '1',
            // Expense approvals
            'expense_approval_threshold' => '0',
            'expense_max_staff_amount' => '0',
            'expense_receipt_required' => '0',
            'expense_payment_step_enabled' => '0',
            'expense_resubmit_allowed' => '1',
            // Appointments & follow-ups
            'appointments_enabled' => '1',
            'appointment_reference_prefix' => 'APT',
            'appointment_reference_next' => '0',
            // Notifications
            'notify_patient_registered' => '1',
            'notify_expense_submitted' => '1',
            'notify_invoice_created' => '1',
            'notify_payment_recorded' => '1',
            'notify_refund_recorded' => '1',
            'notify_followup_due' => '1',
            'notify_settings_changed' => '1',
            'notify_user_deactivated' => '1',
            'notify_sale_recorded' => '1',
            'notify_low_stock' => '1',
            'notify_purchase_received' => '1',
            'notify_return_recorded' => '1',
            'tax_enabled' => '0',
            'tax_percent' => '18',
            'staff_can_pos' => '0',
            'pos_allow_backorders' => '0',
            'sale_prefix' => 'SAL',
            'return_prefix' => 'RET',
            'purchase_prefix' => 'PUR',
            'receipt_footer' => 'Thank you for your business!',
            'signature_label' => 'Authorized Signature',
            'delivery_prefix' => 'DLV',
            'payment_verification_methods' => 'Bank Transfer,Office Payment Number',
            'invoice_due_days' => '7',
            'invoice_terms' => 'Payment due within the stated period.',
        ];

        foreach ($defaults as $key => $value) {
            \App\Models\ClinicSetting::firstOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value, 'updated_by' => $admin->id]
            );
        }

        $messageTemplates = [
            ['patient.registered', 'patient', 'Patient Registration', 'Hello {{patient_name}}, you have been registered at {{clinic_name}}. Your patient number is {{patient_number}}.'],
            ['appointment.booked', 'patient', 'Appointment Confirmation', 'Dear {{patient_name}}, your appointment at {{clinic_name}} is confirmed for {{appointment_date}}.'],
            ['followup.due', 'patient', 'Follow-up Reminder', 'Dear {{patient_name}}, reminder of your follow-up visit on {{due_date}} at {{clinic_name}}. Call {{clinic_phone}}.'],
            ['invoice.issued', 'billing', 'Invoice Issued', 'Dear {{patient_name}}, invoice {{invoice_number}} for {{invoice_amount}} issued by {{clinic_name}}. Due {{due_date}}.'],
            ['payment.received', 'billing', 'Payment Received', 'Dear {{patient_name}}, we received {{amount_paid}} for invoice {{invoice_number}}. Balance: {{outstanding_balance}}.'],
            ['payment.verified', 'billing', 'Payment Confirmed', 'Dear {{patient_name}}, payment of {{amount_paid}} for invoice {{invoice_number}} confirmed. Balance: {{outstanding_balance}}.'],
            ['order.ready', 'orders', 'Order Ready', 'Dear {{customer_name}}, your order {{tracking_number}} is ready for collection at {{clinic_name}}.'],
            ['order.dispatched', 'orders', 'Order Dispatched', 'Dear {{customer_name}}, order {{tracking_number}} has been dispatched. {{clinic_name}}.'],
            ['order.delivered', 'orders', 'Delivery Completed', 'Dear {{customer_name}}, order {{tracking_number}} delivered. Thank you for choosing {{clinic_name}}.'],
        ];
        foreach ($messageTemplates as [$key, $category, $name, $body]) {
            \App\Models\MessageTemplate::firstOrCreate(['key' => $key], ['name' => $name, 'category' => $category, 'body' => $body]);
        }

        foreach (['patient.registered', 'appointment.booked', 'followup.due', 'invoice.issued', 'payment.received', 'payment.verified', 'order.ready', 'order.dispatched', 'order.delivered'] as $eventKey) {
            \App\Models\NotificationRule::firstOrCreate(['event_key' => $eventKey], ['label' => ucwords(str_replace('.', ' ', $eventKey)), 'enabled' => false]);
        }

        $serviceCategories = [
            'Assessment' => [
                ['name' => 'Initial Physiotherapy Assessment', 'code' => 'PT-ASSESS-01', 'price' => 40000],
                ['name' => 'Follow-up Assessment', 'code' => 'PT-ASSESS-02', 'price' => 25000],
            ],
            'Physical Rehabilitation' => [
                ['name' => 'Orthopaedic Rehabilitation', 'code' => 'PT-ORTHO', 'price' => 30000],
                ['name' => 'Sports Injury Rehabilitation', 'code' => 'PT-SPORTS', 'price' => 35000],
            ],
            'Manual Therapy' => [
                ['name' => 'Manual Therapy', 'code' => 'PT-MANUAL', 'price' => 30000],
                ['name' => 'Massage Therapy', 'code' => 'PT-MASSAGE', 'price' => 20000],
            ],
            'Electrotherapy' => [
                ['name' => 'Electrotherapy', 'code' => 'PT-ELECTRO', 'price' => 25000],
            ],
            'Specialised Rehabilitation' => [
                ['name' => 'Neurological Rehabilitation', 'code' => 'PT-NEURO', 'price' => 40000],
                ['name' => 'Paediatric Physiotherapy', 'code' => 'PT-PAEDS', 'price' => 30000],
                ['name' => 'Occupational Therapy', 'code' => 'PT-OCC', 'price' => 30000],
            ],
            'Exercise Therapy' => [
                ['name' => 'Exercise Therapy', 'code' => 'PT-EXER', 'price' => 20000],
            ],
            'Home-based Care' => [
                ['name' => 'Home-based Physiotherapy', 'code' => 'PT-HOME', 'price' => 50000],
            ],
        ];

        $order = 0;
        foreach ($serviceCategories as $categoryName => $services) {
            $category = ServiceCategory::firstOrCreate(
                ['name' => $categoryName],
                ['sort_order' => $order++, 'created_by' => $admin->id]
            );

            foreach ($services as $service) {
                Service::firstOrCreate(
                    ['service_code' => $service['code']],
                    [
                        'name' => $service['name'],
                        'service_category_id' => $category->id,
                        'standard_price' => $service['price'] ?? null,
                        'sort_order' => $order++,
                        'created_by' => $admin->id,
                    ]
                );
            }
        }

        $expenseCategories = [
            'Medical Supplies', 'Equipment Maintenance', 'Electricity', 'Water',
            'Rent', 'Transport', 'Staff Welfare', 'Cleaning',
            'Internet and Communication', 'Other Expenses',
        ];

        foreach ($expenseCategories as $i => $name) {
            ExpenseCategory::firstOrCreate(['name' => $name], ['sort_order' => $i]);
        }
    }
}
