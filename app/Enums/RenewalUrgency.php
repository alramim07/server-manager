<?php

namespace App\Enums;

enum RenewalUrgency: string
{
    case Critical = 'critical';
    case Warning = 'warning';
    case None = 'none';

    public function textClass(): string
    {
        return match ($this) {
            self::Critical => 'text-rose-600 dark:text-rose-400 font-semibold',
            self::Warning => 'text-amber-600 dark:text-amber-400',
            self::None => 'text-slate-500 dark:text-slate-400',
        };
    }
}
