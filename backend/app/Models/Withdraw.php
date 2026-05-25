<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdraw extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'withdraws';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tutor_id',
        'bank',
        'nomor_rekening',
        'nama_pemilik',      // Optional: nama pemilik rekening
        'nominal',
        'admin_fee',
        'total_diterima',    // nominal - admin_fee
        'status',
        'processed_at',
        'notes',
        'rejected_reason',   // Alasan jika ditolak
        'proof_transfer',    // Bukti transfer (path file)
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'nominal' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'total_diterima' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'nomor_rekening',    // Optional: hide full account number in API
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'masked_rekening',
        'formatted_nominal',
        'formatted_admin_fee',
        'formatted_total',
    ];

    // ===== CONSTANTS =====

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROSES = 'proses';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REJECTED = 'rejected';

    public const BANK_LIST = [
        'bca' => 'BCA (Bank Central Asia)',
        'mandiri' => 'Mandiri',
        'bni' => 'BNI (Bank Negara Indonesia)',
        'bri' => 'BRI (Bank Rakyat Indonesia)',
        'danamon' => 'Danamon',
        'permata' => 'Permata Bank',
        'dana' => 'DANA',
        'gopay' => 'GoPay',
        'ovo' => 'OVO',
        'shopeepay' => 'ShopeePay',
    ];

    // ===== RELATIONSHIPS =====

    /**
     * Get the tutor who owns this withdrawal.
     */
    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    // ===== ACCESSORS / ATTRIBUTES =====

    /**
     * Mask rekening number for display (e.g., 801234xxx)
     */
    public function getMaskedRekeningAttribute(): string
    {
        $rek = $this->nomor_rekening;
        if (strlen($rek) <= 4) return $rek;
        return substr($rek, 0, 4) . str_repeat('x', strlen($rek) - 4);
    }

    /**
     * Format nominal to Rupiah string
     */
    public function getFormattedNominalAttribute(): string
    {
        return 'Rp ' . number_format($this->nominal, 0, ',', '.');
    }

    /**
     * Format admin fee to Rupiah string
     */
    public function getFormattedAdminFeeAttribute(): string
    {
        return 'Rp ' . number_format($this->admin_fee ?? 0, 0, ',', '.');
    }

    /**
     * Format total received (nominal - admin_fee) to Rupiah string
     */
    public function getFormattedTotalAttribute(): string
    {
        $total = $this->total_diterima ?? ($this->nominal - ($this->admin_fee ?? 0));
        return 'Rp ' . number_format($total, 0, ',', '.');
    }

    /**
     * Get human-readable bank name
     */
    public function getBankNameAttribute(): string
    {
        return self::BANK_LIST[$this->bank] ?? strtoupper($this->bank);
    }

    /**
     * Get status label for UI
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_PROSES => 'Diproses',
            self::STATUS_SUCCESS => 'Sukses',
            self::STATUS_FAILED => 'Gagal',
            self::STATUS_REJECTED => 'Ditolak',
        ];
        return $labels[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClassAttribute(): string
    {
        $classes = [
            self::STATUS_PENDING => 'badge bg-warning text-dark',
            self::STATUS_PROSES => 'badge bg-primary text-white',
            self::STATUS_SUCCESS => 'badge bg-success text-white',
            self::STATUS_FAILED => 'badge bg-danger text-white',
            self::STATUS_REJECTED => 'badge bg-secondary text-white',
        ];
        return $classes[$this->status] ?? 'badge bg-secondary';
    }

    // ===== SCOPES =====

    /**
     * Scope a query to only include pending withdrawals.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include successful withdrawals.
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope a query to only include failed/rejected withdrawals.
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', [self::STATUS_FAILED, self::STATUS_REJECTED]);
    }

    /**
     * Scope a query to only include withdrawals within date range.
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // ===== HELPER METHODS =====

    /**
     * Calculate total amount received after admin fee
     */
    public function calculateTotalReceived(): float
    {
        return $this->nominal - ($this->admin_fee ?? 0);
    }

    /**
     * Check if withdrawal is still pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if withdrawal has been processed successfully
     */
    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Mark withdrawal as processed (by admin)
     */
    public function markAsProcessed(string $proofPath = null): bool
    {
        return $this->update([
            'status' => self::STATUS_SUCCESS,
            'processed_at' => now(),
            'proof_transfer' => $proofPath,
            'total_diterima' => $this->calculateTotalReceived()
        ]);
    }

    /**
     * Mark withdrawal as failed/rejected
     */
    public function markAsFailed(string $reason, string $status = self::STATUS_REJECTED): bool
    {
        return $this->update([
            'status' => $status,
            'rejected_reason' => $reason,
            'processed_at' => now()
        ]);
    }

    /**
     * Get withdrawal data for API response
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'tutor' => $this->tutor?->only(['id', 'name', 'email']),
            'bank' => $this->bank,
            'bank_name' => $this->bank_name,
            'nomor_rekening' => $this->masked_rekening, // Masked for security
            'nama_pemilik' => $this->nama_pemilik,
            'nominal' => (float) $this->nominal,
            'admin_fee' => (float) ($this->admin_fee ?? 0),
            'total_diterima' => (float) $this->calculateTotalReceived(),
            'formatted_nominal' => $this->formatted_nominal,
            'formatted_total' => $this->formatted_total,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_badge_class' => $this->status_badge_class,
            'created_at' => $this->created_at?->toISOString(),
            'processed_at' => $this->processed_at?->toISOString(),
            'notes' => $this->notes,
            'rejected_reason' => $this->rejected_reason,
        ];
    }
}
