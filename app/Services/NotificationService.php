<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public static function notify(User $user, string $type, string $title, ?string $message = null, ?string $url = null): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url,
        ]);
    }

    private const TYPE_PREFERENCES = [
        'patient.registered' => 'notify_patient_registered',
        'expense.submitted' => 'notify_expense_submitted',
        'invoice.created' => 'notify_invoice_created',
        'payment.recorded' => 'notify_payment_recorded',
        'refund.recorded' => 'notify_refund_recorded',
        'followup.due' => 'notify_followup_due',
        'settings.changed' => 'notify_settings_changed',
        'user.deactivated' => 'notify_user_deactivated',
        'sale.recorded' => 'notify_sale_recorded',
        'stock.low' => 'notify_low_stock',
        'purchase.received' => 'notify_purchase_received',
        'return.recorded' => 'notify_return_recorded',
    ];

    public static function notifyAdmins(string $type, string $title, ?string $message = null, ?string $url = null): void
    {
        $preference = self::TYPE_PREFERENCES[$type] ?? null;
        if ($preference && ! \App\Support\Settings::bool($preference, true)) {
            return;
        }

        User::where('role', User::ROLE_ADMIN)
            ->where('status', User::STATUS_ACTIVE)
            ->get()
            ->each(fn (User $admin) => static::notify($admin, $type, $title, $message, $url));
    }

    /** Notify admins once per day per follow-up to avoid spam. */
    public static function notifyFollowUpDue(int $followUpId, string $patientName, string $dueDate): void
    {
        $url = route('follow-ups.index', ['due' => 'today']);
        $exists = Notification::where('type', 'followup.due')
            ->where('url', $url)
            ->whereDate('created_at', today())
            ->where('message', 'like', "%{$patientName}%")
            ->exists();

        if ($exists) {
            return;
        }

        static::notifyAdmins('followup.due', 'Follow-up due', "{$patientName} follow-up due {$dueDate}", $url);
    }
}
