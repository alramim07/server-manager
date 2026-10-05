<?php

namespace App\Enums;

enum ServerStatus: string
{
    case Active = 'active';
    case PaymentRequired = 'payment_required';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::PaymentRequired => 'Payment required',
            self::Inactive => 'Inactive',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-100 text-emerald-800',
            self::PaymentRequired => 'bg-rose-100 text-rose-800',
            self::Inactive => 'bg-slate-200 text-slate-700',
        };
    }
}
