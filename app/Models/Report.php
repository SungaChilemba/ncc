<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    public const VIOLATION_TYPES = [
        'Abuse',
        'Neglect',
        'Child Marriage',
        'Exploitation / Labour',
        'Other',
    ];

    public const GENDERS = ['Male', 'Female', 'Other'];

    public const PRIORITIES = ['standard', 'urgent'];

    public const STATUSES = [
        'received' => 'Received',
        'triage' => 'Under assessment',
        'investigation' => 'Investigation ongoing',
        'referred' => 'Referred for support',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
        'duplicate' => 'Duplicate',
        'withdrawn' => 'Withdrawn',
    ];

    public const PUBLIC_STATUS_LABELS = [
        'received' => 'Received',
        'triage' => 'Under assessment',
        'investigation' => 'Investigation ongoing',
        'referred' => 'Referred for support',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
        'duplicate' => 'Closed as duplicate',
        'withdrawn' => 'Withdrawn',
    ];

    public const PUBLIC_STATUS_MESSAGES = [
        'received' => 'Complaint received',
        'triage' => 'Case under assessment',
        'investigation' => 'Investigation ongoing',
        'referred' => 'Referred for further support',
        'resolved' => 'Case resolved',
        'closed' => 'Case closed',
        'duplicate' => 'Case closed as duplicate',
        'withdrawn' => 'Case withdrawn',
    ];

    public const TRANSITIONS = [
        'received' => ['triage', 'duplicate', 'withdrawn'],
        'triage' => ['investigation', 'referred', 'resolved', 'closed', 'duplicate', 'withdrawn'],
        'investigation' => ['referred', 'resolved', 'closed', 'duplicate', 'withdrawn'],
        'referred' => ['investigation', 'resolved', 'closed'],
        'resolved' => ['closed', 'investigation'],
        'closed' => ['investigation'],
        'duplicate' => ['investigation', 'closed'],
        'withdrawn' => ['investigation', 'closed'],
    ];

    public const CLOSED_STATUSES = ['closed', 'duplicate', 'withdrawn'];

    public const FINAL_STATUSES = ['resolved', 'closed', 'duplicate', 'withdrawn'];

    protected $fillable = [
        'reference_number',
        'access_pin_hash',
        'status',
        'priority',
        'violation_type',
        'incident_date',
        'district',
        'village',
        'child_age',
        'child_gender',
        'child_name',
        'description',
        'reporter_name',
        'reporter_contact',
        'reporter_contact_hash',
        'is_anonymous',
        'consent_given',
        'assigned_to',
        'received_at',
        'acknowledged_at',
        'closed_at',
        'closure_outcome',
        'retention_delete_at',
        'anonymised_at',
        'legal_hold',
        'legacy_reference',
    ];

    protected $hidden = [
        'access_pin_hash',
        'pin_attempts',
        'pin_locked_until',
    ];

    protected $casts = [
        'child_name' => 'encrypted',
        'description' => 'encrypted',
        'reporter_name' => 'encrypted',
        'reporter_contact' => 'encrypted',
        'incident_date' => 'date',
        'received_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'closed_at' => 'datetime',
        'retention_delete_at' => 'datetime',
        'anonymised_at' => 'datetime',
        'pin_locked_until' => 'datetime',
        'is_anonymous' => 'boolean',
        'consent_given' => 'boolean',
        'legal_hold' => 'boolean',
    ];

    public function evidence()
    {
        return $this->hasMany(ReportEvidence::class);
    }

    public function events()
    {
        return $this->hasMany(ReportEvent::class)->orderBy('created_at');
    }

    public function notes()
    {
        return $this->hasMany(ReportNote::class)->latest();
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function accessLogs()
    {
        return $this->hasMany(ReportAccessLog::class)->latest('created_at');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->caseScope()) {
            'all' => $query,
            'assigned' => $query->where('assigned_to', $user->id),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function publicStatusLabel(): string
    {
        return self::PUBLIC_STATUS_LABELS[$this->status] ?? $this->statusLabel();
    }

    public function publicStatusMessage(): string
    {
        return self::PUBLIC_STATUS_MESSAGES[$this->status] ?? $this->publicStatusLabel();
    }

    public function canTransitionTo(string $status): bool
    {
        if ($status === $this->status) {
            return false;
        }

        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, self::CLOSED_STATUSES, true);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES, true);
    }

    public function isAnonymised(): bool
    {
        return $this->anonymised_at !== null;
    }

    public function isOverdue(): bool
    {
        if ($this->isFinal() || ! $this->received_at) {
            return false;
        }

        $days = (int) $this->received_at->diffInDays(now());

        return match ($this->status) {
            'received' => $days > 2,
            'triage' => $days > 7,
            'investigation' => $days > 30,
            'referred' => $days > 45,
            default => false,
        };
    }

    /**
     * Verify a reporter PIN without leaking whether the reference exists.
     * Failed attempts are counted per case and lock the case temporarily.
     */
    public function verifyPin(string $pin): bool
    {
        if ($this->pin_locked_until && $this->pin_locked_until->isFuture()) {
            return false;
        }

        if (Hash::check($pin, $this->access_pin_hash)) {
            $this->forceFill([
                'pin_attempts' => 0,
                'pin_locked_until' => null,
            ])->save();

            return true;
        }

        $attempts = (int) $this->pin_attempts + 1;
        $lock = $attempts >= (int) config('reporting.pin_max_attempts', 5)
            ? now()->addMinutes((int) config('reporting.pin_lock_minutes', 15))
            : null;

        $this->forceFill([
            'pin_attempts' => $attempts,
            'pin_locked_until' => $lock,
        ])->save();

        return false;
    }

    public function retentionDate(): ?Carbon
    {
        return $this->retention_delete_at;
    }
}
