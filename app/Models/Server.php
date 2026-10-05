<?php

namespace App\Models;

use App\Enums\RenewalUrgency;
use App\Enums\ServerStatus;
use Database\Factories\ServerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'owner_id',
    'name',
    'ip_address',
    'operating_system',
    'provider',
    'status',
    'deployed_apps_count',
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
            'status' => ServerStatus::class,
            'renewal_date' => 'date:Y-m-d',
            'deployed_apps_count' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
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
        return (int) floor(
            ($this->renewal_date->startOfDay()->getTimestamp() - now()->startOfDay()->getTimestamp()) / 86400
        );
    }
}
