<?php

namespace App\Models;

use App\Enums\RenewalUrgency;
use App\Enums\ServerStatus;
use Carbon\Carbon;
use Database\Factories\ServerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'owner_id',
    'name',
    'ip_address',
    'operating_system',
    'provider',
    'status',
    'renewal_date',
    'notes',
])]
class Server extends Model
{
    /** @use HasFactory<ServerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'owner_id' => 'integer',
            'status' => ServerStatus::class,
            'renewal_date' => 'date:Y-m-d',
            'deployed_apps_count' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function deployedApps(): HasMany
    {
        return $this->hasMany(DeployedApp::class);
    }

    /**
     * Persistently mark active servers past their renewal date as payment
     * required, so badges, stats, and filters all reflect the expiry.
     */
    public static function flipExpiredToPaymentRequired(): void
    {
        static::query()
            ->where('status', ServerStatus::Active)
            ->whereNotNull('renewal_date')
            ->whereDate('renewal_date', '<', now()->toDateString())
            ->update(['status' => ServerStatus::PaymentRequired->value]);
    }

    public function renewalUrgency(): RenewalUrgency
    {
        if ($this->renewal_date === null) {
            return RenewalUrgency::None;
        }

        $days = $this->daysUntilRenewal();

        return match (true) {
            $days <= 7 => RenewalUrgency::Critical,
            $days <= 30 => RenewalUrgency::Warning,
            default => RenewalUrgency::None,
        };
    }

    public function billingWarning(): bool
    {
        return $this->status === ServerStatus::PaymentRequired
            && $this->renewal_date !== null
            && $this->daysUntilRenewal() <= 14;
    }

    private function daysUntilRenewal(): int
    {
        $renewalUtc = Carbon::createFromFormat('Y-m-d', $this->renewal_date->toDateString(), 'UTC')->startOfDay();
        $todayUtc = Carbon::createFromFormat('Y-m-d', now()->toDateString(), 'UTC')->startOfDay();

        return (int) (($renewalUtc->getTimestamp() - $todayUtc->getTimestamp()) / 86400);
    }
}
