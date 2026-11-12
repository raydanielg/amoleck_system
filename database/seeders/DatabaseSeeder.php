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
            'clinic_name' => 'Amoleck Group Company LTD HMS',
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
            'Assessment & Consultation' => [
                ['name' => 'Initial Physiotherapy Assessment', 'code' => 'PT-ASSESS-01', 'price' => 45000, 'duration' => 60, 'notes' => true, 'desc' => 'Comprehensive first-visit evaluation: history, physical examination, diagnosis and treatment plan.'],
                ['name' => 'Follow-up Assessment & Review', 'code' => 'PT-ASSESS-02', 'price' => 25000, 'duration' => 30, 'desc' => 'Progress review and treatment plan adjustment for returning patients.'],
                ['name' => 'Specialist Consultation', 'code' => 'PT-CONSULT', 'price' => 60000, 'duration' => 45, 'notes' => true, 'desc' => 'Senior physiotherapist opinion for complex or referred cases.'],
                ['name' => 'Post-Surgical Assessment', 'code' => 'PT-POSTOP', 'price' => 50000, 'duration' => 60, 'notes' => true, 'desc' => 'Baseline evaluation after surgery to plan the rehabilitation protocol.'],
            ],
            'Manual Therapy' => [
                ['name' => 'Manual Therapy (Joint Mobilisation)', 'code' => 'PT-MANUAL', 'price' => 30000, 'duration' => 45, 'desc' => 'Hands-on joint mobilisation and manipulation techniques.'],
                ['name' => 'Deep Tissue Massage', 'code' => 'PT-MASSAGE', 'price' => 25000, 'duration' => 60, 'desc' => 'Therapeutic deep tissue massage for chronic muscle tension.'],
                ['name' => 'Sports Massage', 'code' => 'PT-SPMASS', 'price' => 30000, 'duration' => 60, 'desc' => 'Pre/post-event and maintenance massage for athletes.'],
                ['name' => 'Myofascial Release', 'code' => 'PT-MFR', 'price' => 30000, 'duration' => 45, 'desc' => 'Soft-tissue release for fascia restrictions and pain.'],
                ['name' => 'Trigger Point Therapy', 'code' => 'PT-TRIGGER', 'price' => 25000, 'duration' => 30, 'desc' => 'Targeted release of muscular trigger points.'],
                ['name' => 'Spinal Manipulation', 'code' => 'PT-SPINE', 'price' => 35000, 'duration' => 30, 'desc' => 'Skilled spinal mobilisation/manipulation for back and neck conditions.'],
                ['name' => 'Dry Needling', 'code' => 'PT-NEEDLE', 'price' => 35000, 'duration' => 30, 'desc' => 'Intramuscular dry needling for pain and muscle dysfunction.'],
                ['name' => 'Cupping Therapy', 'code' => 'PT-CUP', 'price' => 30000, 'duration' => 30, 'desc' => 'Therapeutic cupping for muscle recovery and circulation.'],
                ['name' => 'Kinesio Taping', 'code' => 'PT-TAPE', 'price' => 15000, 'duration' => 15, 'desc' => 'Elastic therapeutic taping for support and swelling control.'],
            ],
            'Electrotherapy & Modalities' => [
                ['name' => 'Ultrasound Therapy', 'code' => 'PT-ULTS', 'price' => 20000, 'duration' => 20, 'desc' => 'Therapeutic ultrasound for soft-tissue healing.'],
                ['name' => 'TENS Therapy', 'code' => 'PT-TENS', 'price' => 20000, 'duration' => 30, 'desc' => 'Transcutaneous electrical nerve stimulation for pain relief.'],
                ['name' => 'Interferential Therapy (IFT)', 'code' => 'PT-IFT', 'price' => 25000, 'duration' => 30, 'desc' => 'IFT current therapy for deep pain relief and swelling.'],
                ['name' => 'Electrical Muscle Stimulation (EMS)', 'code' => 'PT-EMS', 'price' => 20000, 'duration' => 30, 'desc' => 'Muscle re-education and strengthening via electrical stimulation.'],
                ['name' => 'Shortwave Diathermy', 'code' => 'PT-SWD', 'price' => 25000, 'duration' => 30, 'desc' => 'Deep heating modality for chronic joint and muscle conditions.'],
                ['name' => 'Infrared / Heat Therapy', 'code' => 'PT-HEAT', 'price' => 15000, 'duration' => 20, 'desc' => 'Superficial heat application for pain and stiffness.'],
                ['name' => 'Cryotherapy / Ice Therapy', 'code' => 'PT-ICE', 'price' => 10000, 'duration' => 15, 'desc' => 'Cold therapy for acute injury and inflammation.'],
                ['name' => 'Paraffin Wax Therapy', 'code' => 'PT-WAX', 'price' => 15000, 'duration' => 20, 'desc' => 'Wax bath therapy for hands and feet — arthritis and stiffness.'],
                ['name' => 'Traction (Cervical / Lumbar)', 'code' => 'PT-TRACTION', 'price' => 30000, 'duration' => 30, 'desc' => 'Mechanical spinal decompression for disc and nerve conditions.'],
            ],
            'Exercise Therapy' => [
                ['name' => 'Therapeutic Exercise Session', 'code' => 'PT-EXER', 'price' => 20000, 'duration' => 45, 'desc' => 'Supervised therapeutic exercise programme.'],
                ['name' => 'Gait Training', 'code' => 'PT-GAIT', 'price' => 25000, 'duration' => 45, 'desc' => 'Walking re-education after injury, surgery or neurological conditions.'],
                ['name' => 'Balance & Proprioception Training', 'code' => 'PT-BAL', 'price' => 20000, 'duration' => 30, 'desc' => 'Balance retraining for fall prevention and injury recovery.'],
                ['name' => 'Core Stabilisation Programme', 'code' => 'PT-CORE', 'price' => 20000, 'duration' => 45, 'desc' => 'Core strengthening for back pain and postural conditions.'],
                ['name' => 'Hydrotherapy Session', 'code' => 'PT-HYDRO', 'price' => 40000, 'duration' => 45, 'desc' => 'Water-based rehabilitation exercise.'],
            ],
            'Orthopaedic & Sports Rehabilitation' => [
                ['name' => 'Orthopaedic Rehabilitation', 'code' => 'PT-ORTHO', 'price' => 30000, 'duration' => 45, 'notes' => true, 'desc' => 'Rehabilitation for musculoskeletal injuries and conditions.'],
                ['name' => 'Sports Injury Rehabilitation', 'code' => 'PT-SPORTS', 'price' => 35000, 'duration' => 60, 'notes' => true, 'desc' => 'Sport-specific rehabilitation and return-to-play programme.'],
                ['name' => 'Post-Fracture Rehabilitation', 'code' => 'PT-FRACT', 'price' => 30000, 'duration' => 45, 'desc' => 'Staged rehabilitation following fracture healing.'],
                ['name' => 'ACL / Post-Surgical Rehabilitation', 'code' => 'PT-ACL', 'price' => 40000, 'duration' => 60, 'notes' => true, 'desc' => 'Protocol-driven rehabilitation after ligament reconstruction or surgery.'],
                ['name' => 'Back & Neck Pain Programme', 'code' => 'PT-BACK', 'price' => 30000, 'duration' => 45, 'desc' => 'Structured programme for chronic back and neck pain.'],
            ],
            'Neurological Rehabilitation' => [
                ['name' => 'Stroke Rehabilitation', 'code' => 'PT-STROKE', 'price' => 40000, 'duration' => 60, 'notes' => true, 'desc' => 'Motor relearning and functional recovery after stroke.'],
                ['name' => 'Spinal Cord Injury Rehabilitation', 'code' => 'PT-SCI', 'price' => 45000, 'duration' => 60, 'notes' => true, 'desc' => 'Long-term rehabilitation for spinal cord injury patients.'],
                ['name' => "Parkinson's Disease Therapy", 'code' => 'PT-PARK', 'price' => 40000, 'duration' => 60, 'notes' => true, 'desc' => 'Movement, gait and balance therapy for Parkinson’s disease.'],
                ['name' => "Bell's Palsy / Facial Nerve Therapy", 'code' => 'PT-BELL', 'price' => 30000, 'duration' => 45, 'desc' => 'Facial nerve stimulation and muscle re-education.'],
                ['name' => 'Cerebral Palsy Therapy', 'code' => 'PT-CP', 'price' => 35000, 'duration' => 60, 'notes' => true, 'desc' => 'Developmental and functional therapy for cerebral palsy.'],
            ],
            'Paediatric & Women’s Health' => [
                ['name' => 'Paediatric Physiotherapy', 'code' => 'PT-PAEDS', 'price' => 30000, 'duration' => 45, 'notes' => true, 'desc' => 'Physiotherapy for children’s developmental and musculoskeletal conditions.'],
                ['name' => 'Developmental Delay Therapy', 'code' => 'PT-DEVELOP', 'price' => 30000, 'duration' => 45, 'notes' => true, 'desc' => 'Motor milestone therapy for developmental delay.'],
                ['name' => 'Antenatal Exercise Class', 'code' => 'PT-ANTE', 'price' => 20000, 'duration' => 45, 'desc' => 'Safe exercise programme for expectant mothers.'],
                ['name' => 'Postnatal Rehabilitation', 'code' => 'PT-POSTN', 'price' => 30000, 'duration' => 45, 'desc' => 'Recovery programme after childbirth.'],
                ['name' => 'Pelvic Floor Therapy', 'code' => 'PT-PELVIC', 'price' => 35000, 'duration' => 45, 'desc' => 'Pelvic floor rehabilitation for incontinence and related conditions.'],
            ],
            'Geriatric & Special Care' => [
                ['name' => 'Geriatric Rehabilitation', 'code' => 'PT-GERIA', 'price' => 30000, 'duration' => 45, 'desc' => 'Strength, mobility and independence programmes for elderly patients.'],
                ['name' => 'Falls Prevention Programme', 'code' => 'PT-FALLS', 'price' => 25000, 'duration' => 45, 'desc' => 'Balance and strength training to prevent falls in older adults.'],
                ['name' => 'Arthritis Management', 'code' => 'PT-ARTH', 'price' => 25000, 'duration' => 45, 'desc' => 'Joint protection, exercise and pain management for arthritis.'],
                ['name' => 'Amputee Rehabilitation', 'code' => 'PT-AMPUTEE', 'price' => 40000, 'duration' => 60, 'notes' => true, 'desc' => 'Pre/post-prosthetic training and rehabilitation for amputees.'],
            ],
            'Cardiopulmonary' => [
                ['name' => 'Chest Physiotherapy', 'code' => 'PT-CHEST', 'price' => 30000, 'duration' => 30, 'desc' => 'Airway clearance and breathing techniques for chest conditions.'],
                ['name' => 'Cardiac Rehabilitation', 'code' => 'PT-CARDIAC', 'price' => 40000, 'duration' => 60, 'notes' => true, 'desc' => 'Supervised exercise programme for cardiac recovery.'],
                ['name' => 'Respiratory Therapy', 'code' => 'PT-RESP', 'price' => 25000, 'duration' => 30, 'desc' => 'Breathing exercises and respiratory muscle training.'],
            ],
            'Home & Corporate Services' => [
                ['name' => 'Home-based Physiotherapy', 'code' => 'PT-HOME', 'price' => 60000, 'duration' => 60, 'notes' => true, 'desc' => 'Full physiotherapy session delivered at the patient’s home.'],
                ['name' => 'Teleconsultation / Virtual Session', 'code' => 'PT-TELE', 'price' => 20000, 'duration' => 30, 'desc' => 'Remote consultation and guided exercise via video call.'],
                ['name' => 'Corporate Ergonomic Assessment', 'code' => 'PT-ERGO', 'price' => 150000, 'duration' => 120, 'desc' => 'On-site workplace ergonomic assessment and report for organisations.'],
                ['name' => 'Occupational Therapy', 'code' => 'PT-OCC', 'price' => 30000, 'duration' => 45, 'notes' => true, 'desc' => 'Functional rehabilitation for activities of daily living.'],
            ],
        ];

        $order = 0;
        foreach ($serviceCategories as $categoryName => $services) {
            $category = ServiceCategory::firstOrCreate(
                ['name' => $categoryName],
                ['sort_order' => $order++, 'created_by' => $admin->id]
            );

            foreach ($services as $service) {
                Service::updateOrCreate(
                    ['service_code' => $service['code']],
                    [
                        'name' => $service['name'],
                        'service_category_id' => $category->id,
                        'description' => $service['desc'] ?? null,
                        'standard_price' => $service['price'] ?? null,
                        'duration_minutes' => $service['duration'] ?? null,
                        'requires_notes' => $service['notes'] ?? false,
                        'is_active' => true,
                        'sort_order' => $order++,
                        'created_by' => $admin->id,
                    ]
                );
            }
        }

        \App\Services\AccountingService::seedCoA();

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
