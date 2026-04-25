<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Reservation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected string $activitySection = 'Réservations';

    // ─── Statuts disponibles ─────────────────────────────────────────────────

    const STATUS_DRAFT               = 'draft';
    const STATUS_PENDING             = 'pending';
    const STATUS_ACCEPTED            = 'accepted';
    const STATUS_REFUSED             = 'refused';
    const STATUS_WAITING_PAYMENT     = 'waiting_payment';
    const STATUS_PARTIALLY_PAID      = 'partially_paid';
    const STATUS_PAID                = 'paid';
    const STATUS_CONFIRMED           = 'confirmed';
    const STATUS_MODIFICATION_PENDING = 'modification_pending';
    const STATUS_CANCELLED           = 'cancelled';

    const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_ACCEPTED,
        self::STATUS_REFUSED,
        self::STATUS_WAITING_PAYMENT,
        self::STATUS_PARTIALLY_PAID,
        self::STATUS_PAID,
        self::STATUS_CONFIRMED,
        self::STATUS_MODIFICATION_PENDING,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'reference', 'hotel_id', 'secure_link_id',
        'agency_name', 'contact_name', 'email', 'phone',
        'check_in', 'check_out', 'total_persons', 'special_requests',
        'flexible_dates', 'flexible_hotel',
        'total_price', 'currency', 'price_breakdown',
        'discount_percent',
        'taxe_total', 'tariff_code',
        'group_discount_amount', 'group_discount_detail',
        'supplement_total', 'promo_discount_rate', 'promo_discount_amount',
        'payment_deadline',
        'status', 'modification_data', 'previous_status',
        'handled_by', 'admin_notes', 'refusal_reason', 'refused_with_suggestion', 'suggestion_copied',
        'payment_token', 'payment_token_expires_at',
        'payment_amount_requested', 'agency_id',
        'is_read',
    ];

    protected $casts = [
        'check_in'                  => 'date',
        'check_out'                 => 'date',
        'total_price'              => 'float',
        'taxe_total'               => 'float',
        'price_breakdown'          => 'array',
        'modification_data'        => 'array',
        'payment_token_expires_at' => 'datetime',
        'group_discount_amount'    => 'float',
        'group_discount_detail'    => 'array',
        'supplement_total'         => 'float',
        'promo_discount_rate'      => 'float',
        'promo_discount_amount'    => 'float',
        'payment_deadline'         => 'date',
        'flexible_dates'              => 'boolean',
        'flexible_hotel'              => 'boolean',
        'refused_with_suggestion'     => 'boolean',
        'suggestion_copied'           => 'boolean',
        'is_read'                     => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $reservation) {
            if (empty($reservation->reference)) {
                $reservation->reference = self::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        $year = now()->year;
        $last = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('MH-%d-%05d', $year, $last);
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function secureLink()
    {
        return $this->belongsTo(SecureLink::class);
    }

    public function rooms()
    {
        return $this->hasMany(ReservationRoom::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(StatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function logs()
    {
        return $this->hasMany(\App\Models\ReservationLog::class)->orderBy('created_at', 'desc');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function paymentSchedules()
    {
        return $this->hasMany(PaymentSchedule::class)->orderBy('installment_number');
    }

    public function supplements()
    {
        return $this->hasMany(ReservationSupplement::class)->with('supplement');
    }

    public function guestRegistrations()
    {
        return $this->hasMany(GuestRegistration::class)->orderBy('guest_index');
    }

    public function agency()
    {
        return $this->belongsTo(\App\Models\Agency::class);
    }

    public function extras()
    {
        return $this->hasMany(ReservationExtra::class)->orderBy('created_at');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_REFUSED]);
    }

    // ─── Helpers & Accessors ─────────────────────────────────────────────────

    public function getNightsAttribute(): int
    {
        // Multi-séjour : sommer les nuits de chaque séjour distinct
        if ($this->relationLoaded('rooms')) {
            $grouped = $this->rooms
                ->filter(fn ($r) => $r->check_in && $r->check_out)
                ->unique(fn ($r) => $r->check_in->format('Y-m-d') . '_' . $r->check_out->format('Y-m-d'));
            if ($grouped->count() > 1) {
                return (int) $grouped->sum(fn ($r) => $r->check_in->diffInDays($r->check_out));
            }
        }
        return (int) $this->check_in->diffInDays($this->check_out);
    }

    /**
     * Retourne les chambres groupées par séjour (check_in + check_out).
     * Pour les réservations multi-séjour.
     */
    public function getSejoursAttribute(): \Illuminate\Support\Collection
    {
        if (! $this->relationLoaded('rooms')) {
            $this->load('rooms.roomType');
        }
        return $this->rooms
            ->groupBy(fn ($r) => ($r->check_in?->format('Y-m-d') ?? 'global')
                               . '_'
                               . ($r->check_out?->format('Y-m-d') ?? 'global'))
            ->values()
            ->map(fn ($rooms) => [
                'check_in'  => $rooms->first()->check_in  ?? $this->check_in,
                'check_out' => $rooms->first()->check_out ?? $this->check_out,
                'nights'    => ($rooms->first()->check_in && $rooms->first()->check_out)
                    ? $rooms->first()->check_in->diffInDays($rooms->first()->check_out)
                    : $this->check_in->diffInDays($this->check_out),
                'rooms'     => $rooms,
            ]);
    }

    /** Montant total déjà payé (paiements validés). */
    public function getAmountPaidAttribute(): float
    {
        return (float) $this->payments->where('status', 'completed')->sum('amount');
    }

    /** Reste à payer. */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) ($this->total_price ?? 0) - $this->amount_paid);
    }

    /** Pourcentage payé (0-100). */
    public function getPaymentPercentAttribute(): int
    {
        if (! $this->total_price) return 0;
        return (int) min(100, round($this->amount_paid / $this->total_price * 100));
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT               => 'Brouillon',
            self::STATUS_PENDING             => 'En attente',
            self::STATUS_ACCEPTED            => 'Acceptée',
            self::STATUS_REFUSED             => 'Refusée',
            self::STATUS_WAITING_PAYMENT     => 'En attente de paiement',
            self::STATUS_PARTIALLY_PAID      => 'Partiellement payée',
            self::STATUS_PAID                => 'Payée',
            self::STATUS_CONFIRMED           => 'Confirmée',
            self::STATUS_MODIFICATION_PENDING => 'Modification en attente',
            self::STATUS_CANCELLED           => 'Annulée',
            default                          => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT               => 'gray',
            self::STATUS_PENDING             => 'yellow',
            self::STATUS_ACCEPTED            => 'blue',
            self::STATUS_REFUSED             => 'red',
            self::STATUS_WAITING_PAYMENT     => 'orange',
            self::STATUS_PARTIALLY_PAID      => 'indigo',
            self::STATUS_PAID                => 'teal',
            self::STATUS_CONFIRMED           => 'green',
            self::STATUS_MODIFICATION_PENDING => 'purple',
            self::STATUS_CANCELLED           => 'red',
            default                          => 'gray',
        };
    }

    public function canBeModifiedByClient(): bool
    {
        if (! in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_WAITING_PAYMENT,
            self::STATUS_PARTIALLY_PAID,
            self::STATUS_CONFIRMED,
        ])) {
            return false;
        }

        // Blocage si l'arrivée est dans 7 jours ou moins
        $checkIn = $this->check_in instanceof \Carbon\Carbon
            ? $this->check_in
            : \Carbon\Carbon::parse($this->check_in);

        $daysUntilArrival = now()->startOfDay()->diffInDays($checkIn->copy()->startOfDay(), false);

        return $daysUntilArrival > 7;
    }

    public function canBeCancelledByClient(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_WAITING_PAYMENT,
            self::STATUS_PARTIALLY_PAID,
        ]);
    }

    /** Vérifie si un paiement peut être enregistré. */
    public function canReceivePayment(): bool
    {
        return in_array($this->status, [
            self::STATUS_WAITING_PAYMENT,
            self::STATUS_PARTIALLY_PAID,
            self::STATUS_ACCEPTED,
        ]);
    }

    /** Vrai si la date limite de paiement est dépassée. */
    public function isPaymentDeadlineExpired(): bool
    {
        return $this->payment_deadline !== null && $this->payment_deadline->isPast();
    }

    /**
     * Vrai si au moins une échéance non payée est dépassée.
     * Utilisé pour griser l'accès client à la réservation.
     */
    public function hasOverdueSchedule(): bool
    {
        if (! $this->relationLoaded('paymentSchedules')) {
            $this->load('paymentSchedules.payment');
        }
        return $this->paymentSchedules
            ->filter(fn ($s) => ! $s->isPaid())
            ->contains(fn ($s) => $s->due_date->isPast());
    }

    public function hasValidPaymentToken(): bool
    {
        return $this->payment_token
            && $this->payment_token_expires_at
            && $this->payment_token_expires_at->isFuture();
    }

    public function generatePaymentToken(): string
    {
        $token = Str::random(64);
        $this->update([
            'payment_token'            => $token,
            'payment_token_expires_at' => now()->addDays(7),
        ]);
        return $token;
    }
}
