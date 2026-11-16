<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    public const CONTACT_NEW = 'new';

    public const CONTACT_CONTACTED = 'contacted';

    public const CONTACT_NOT_CONTACTED = 'not_contacted';

    public const INTEREST_INTERESTED = 'interested';

    public const INTEREST_NOT_INTERESTED = 'not_interested';

    public const INTEREST_NEEDS_INFO = 'needs_info';

    public const VISIT_EXPECTED = 'expected';

    public const VISIT_CONFIRMED = 'confirmed';

    public const VISIT_VISITED = 'visited';

    public const VISIT_SERVICE_RECEIVED = 'service_received';

    public const VISIT_FOLLOW_UP = 'follow_up_required';

    public const VISIT_NOT_CONVERTED = 'not_converted';

    /** Valid visit_status transitions — arbitrary jumps are rejected. */
    public const VISIT_TRANSITIONS = [
        self::VISIT_EXPECTED => [self::VISIT_CONFIRMED, self::VISIT_VISITED, self::VISIT_FOLLOW_UP, self::VISIT_NOT_CONVERTED],
        self::VISIT_CONFIRMED => [self::VISIT_VISITED, self::VISIT_FOLLOW_UP, self::VISIT_NOT_CONVERTED, self::VISIT_EXPECTED],
        self::VISIT_FOLLOW_UP => [self::VISIT_CONFIRMED, self::VISIT_EXPECTED, self::VISIT_VISITED, self::VISIT_NOT_CONVERTED],
        self::VISIT_VISITED => [self::VISIT_SERVICE_RECEIVED, self::VISIT_NOT_CONVERTED],
        self::VISIT_SERVICE_RECEIVED => [],
        self::VISIT_NOT_CONVERTED => [self::VISIT_EXPECTED, self::VISIT_FOLLOW_UP],
    ];

    public const VISIT_LABELS = [
        self::VISIT_EXPECTED => 'Expected to Visit',
        self::VISIT_CONFIRMED => 'Confirmed',
        self::VISIT_VISITED => 'Visited Clinic',
        self::VISIT_SERVICE_RECEIVED => 'Service Received',
        self::VISIT_FOLLOW_UP => 'Follow-up Required',
        self::VISIT_NOT_CONVERTED => 'Not Converted',
    ];

    protected $fillable = [
        'lead_reference', 'full_name', 'phone', 'email', 'lead_source_id',
        'campaign_name', 'ad_name', 'platform', 'interested_service_id',
        'description', 'initial_notes', 'received_at',
        'contact_status', 'interest_level', 'visit_status',
        'expected_visit_at', 'next_followup_at',
        'assigned_to', 'assigned_by', 'assigned_at',
        'converted_patient_id', 'converted_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'expected_visit_at' => 'date',
            'next_followup_at' => 'date',
            'assigned_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function source()
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function interestedService()
    {
        return $this->belongsTo(Service::class, 'interested_service_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'converted_patient_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function followUps()
    {
        return $this->hasMany(LeadFollowUp::class)->latest('contacted_at');
    }

    public function activities()
    {
        return $this->hasMany(LeadActivity::class)->latest();
    }

    public function isConverted(): bool
    {
        return $this->converted_patient_id !== null;
    }

    public function followUpOverdue(): bool
    {
        return $this->next_followup_at !== null && $this->next_followup_at->isPast()
            && ! in_array($this->visit_status, [self::VISIT_VISITED, self::VISIT_SERVICE_RECEIVED, self::VISIT_NOT_CONVERTED], true);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::VISIT_TRANSITIONS[$this->visit_status] ?? [], true);
    }

    public function recordActivity(string $type, string $description, ?int $userId = null, ?array $meta = null): void
    {
        $this->activities()->create([
            'type' => $type, 'description' => $description,
            'user_id' => $userId ?? auth()->id(), 'meta' => $meta,
        ]);
    }
}
