<?php

namespace App\Support;

class Permissions
{
    /** Permission catalog: key => [group, description] */
    public const CATALOG = [
        'patients.view' => ['Patients', 'View patient records'],
        'patients.register' => ['Patients', 'Register new patients'],
        'patients.edit' => ['Patients', 'Edit patient details'],
        'appointments.manage' => ['Appointments', 'Book, reschedule and cancel appointments'],
        'reception.queue' => ['Reception', 'Check-in patients and manage the queue'],
        'billing.view' => ['Billing', 'View invoices and balances'],
        'billing.create' => ['Billing', 'Create invoices'],
        'payments.record' => ['Billing', 'Record payments (verification stays with Admin)'],
        'receipts.print' => ['Billing', 'Print receipts and slips'],
        'messaging.send' => ['Communication', 'Send SMS to patients'],
        'clinical.view' => ['Clinical', 'View treatment records and clinical notes'],
        'clinical.assess' => ['Clinical', 'Create and edit physiotherapy assessments'],
        'self.attendance' => ['Self-Service', 'Clock in/out and view own attendance'],
        'self.leave' => ['Self-Service', 'Apply for own leave'],
        'self.payslip' => ['Self-Service', 'View and download own payslips'],
        'self.requests' => ['Self-Service', 'Submit HR requests'],
        'accounting.dashboard' => ['Accounting & Finance', 'View financial dashboard'],
        'accounting.journals' => ['Accounting & Finance', 'Create journal entries'],
        'accounting.post' => ['Accounting & Finance', 'Post/approve journals'],
        'accounting.verify_payments' => ['Accounting & Finance', 'Verify payments'],
        'accounting.expenses' => ['Accounting & Finance', 'Manage and approve expenses'],
        'accounting.receivables' => ['Accounting & Finance', 'Manage accounts receivable/payable'],
        'accounting.banking' => ['Accounting & Finance', 'Manage cash, bank and reconciliation'],
        'accounting.reports' => ['Accounting & Finance', 'Financial reports and exports'],
        'accounting.periods' => ['Accounting & Finance', 'Close/reopen accounting periods'],
        'exec.dashboard' => ['Management & Analytics', 'View executive dashboard'],
        'exec.financial' => ['Management & Analytics', 'View detailed financial analytics'],
        'exec.hr' => ['Management & Analytics', 'View HR and staff analytics'],
        'exec.branches' => ['Management & Analytics', 'View branch comparisons'],
        'exec.insights' => ['Management & Analytics', 'View and manage insights'],
        'exec.reports' => ['Management & Analytics', 'Reports center and exports'],
        'docs.view' => ['Files & Folders', 'View authorized files and folders'],
        'docs.upload' => ['Files & Folders', 'Upload files and new versions'],
        'docs.folders' => ['Files & Folders', 'Create and organize folders'],
        'docs.edit' => ['Files & Folders', 'Rename, move and tag files'],
        'docs.download' => ['Files & Folders', 'Download files'],
        'docs.share' => ['Files & Folders', 'Share files and folders internally'],
        'docs.external' => ['Files & Folders', 'Create external secure links'],
        'docs.delete' => ['Files & Folders', 'Move files to trash'],
        'docs.restore' => ['Files & Folders', 'Restore or permanently delete files'],
        'docs.versions' => ['Files & Folders', 'Manage and restore versions'],
        'docs.perms' => ['Files & Folders', 'Manage file/folder permissions'],
        'board.view' => ['Document Board', 'View announcements and official documents'],
        'board.ack' => ['Document Board', 'Acknowledge official notices'],
        'board.publish' => ['Document Board', 'Publish announcements and documents'],
        'board.manage' => ['Document Board', 'Edit, schedule, pin and archive publications'],
        'board.reports' => ['Document Board', 'View acknowledgment and delivery reports'],
    ];

    /** Default grants per role. Admin always has everything. */
    public const ROLE_DEFAULTS = [
        'RECEPTIONIST' => [
            'patients.view', 'patients.register', 'patients.edit', 'appointments.manage',
            'reception.queue', 'billing.view', 'payments.record', 'receipts.print',
            'messaging.send', 'self.attendance', 'self.leave', 'self.requests',
        ],
        'CLINIC_STAFF' => [
            'patients.view', 'patients.register', 'patients.edit', 'appointments.manage',
            'reception.queue', 'billing.view', 'payments.record', 'receipts.print',
            'clinical.view', 'messaging.send',
            'self.attendance', 'self.leave', 'self.payslip', 'self.requests',
        ],
    ];

    public static function groups(): array
    {
        $groups = [];
        foreach (self::CATALOG as $key => [$group, $desc]) {
            $groups[$group][] = ['key' => $key, 'description' => $desc];
        }
        return $groups;
    }
}
