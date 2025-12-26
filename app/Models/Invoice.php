<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'due_date',
        'service_id',
        'automation_status',
        'grace_notified_at',
        'last_status_at',
        'paid_at',
        'idempotency_key',
        'currency',
        'provisioned_at',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'grace_notified_at' => 'datetime',
        'last_status_at' => 'datetime',
        'paid_at' => 'datetime',
        'provisioned_at' => 'datetime',
    ];

    // Strict lifecycle: unpaid -> warned -> grace -> suspended -> terminated -> paid
    public const STATUS_PENDING = 'pending'; // legacy
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_WARNED = 'warned';
    public const STATUS_GRACE = 'grace';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_TERMINATED = 'terminated';
    public const STATUS_PAID = 'paid';

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_UNPAID,
            self::STATUS_WARNED,
            self::STATUS_GRACE,
            self::STATUS_SUSPENDED,
            self::STATUS_TERMINATED,
            self::STATUS_PAID,
        ];
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID || $this->paid_at !== null;
    }

    public static function allowedTransitions(): array
    {
        return [
            self::STATUS_UNPAID => [self::STATUS_WARNED, self::STATUS_PAID],
            self::STATUS_WARNED => [self::STATUS_GRACE, self::STATUS_PAID],
            self::STATUS_GRACE => [self::STATUS_SUSPENDED, self::STATUS_PAID],
            self::STATUS_SUSPENDED => [self::STATUS_TERMINATED, self::STATUS_PAID],
            self::STATUS_TERMINATED => [],
            self::STATUS_PAID => [],
        ];
    }

    public function canTransitionTo(string $to): bool
    {
        $from = $this->status ?? self::STATUS_UNPAID;
        $allowed = static::allowedTransitions();

        // Allow transition to the same state
        if ($from === $to) return true;

        return in_array($to, $allowed[$from] ?? [], true);
    }

    /**
     * Transition status in a safe, race-free way using DB lock.
     */
    public function transitionTo(string $to): bool
    {
        if (!$this->exists) return false;

        return \Illuminate\Support\Facades\DB::transaction(function () use ($to) {
            $inv = static::lockForUpdate()->find($this->id);
            if (!$inv) return false;

            if (!$inv->canTransitionTo($to)) {
                throw new \RuntimeException("Invalid status transition from {$inv->status} to {$to}");
            }

            $inv->status = $to;
            $inv->last_status_at = now();
            $inv->save();

            return true;
        });
    }

    // Prevent mutating core billing fields after payment
    public function setAttribute($key, $value)
    {
        if ($this->exists && $this->isPaid() && in_array($key, ['amount', 'user_id', 'currency', 'service_id'])) {
            throw new \RuntimeException('Cannot modify immutable invoice fields after payment');
        }

        // Prevent arbitrary status resets once reached terminal states
        if ($key === 'status' && $this->exists && $this->status === self::STATUS_PAID && $value !== self::STATUS_PAID) {
            throw new \RuntimeException('Cannot change status of a paid invoice');
        }

        return parent::setAttribute($key, $value);
    }
}
