<?php

namespace App\Enums;

enum RenewalUrgency: string
{
    case Critical = 'critical';
    case Warning = 'warning';
    case None = 'none';
}
