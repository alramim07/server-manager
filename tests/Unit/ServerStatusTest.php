<?php

namespace Tests\Unit;

use App\Enums\ServerStatus;
use PHPUnit\Framework\TestCase;

class ServerStatusTest extends TestCase
{
    public function test_it_exposes_exactly_the_three_team_statuses(): void
    {
        $this->assertSame(['active', 'payment_required', 'inactive'], array_column(ServerStatus::cases(), 'value'));
    }

    public function test_each_status_has_a_human_label_and_badge_color(): void
    {
        $this->assertSame('Active', ServerStatus::Active->label());
        $this->assertSame('Payment required', ServerStatus::PaymentRequired->label());
        $this->assertSame('Inactive', ServerStatus::Inactive->label());

        $this->assertSame('bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300', ServerStatus::Active->badgeClass());
        $this->assertSame('bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300', ServerStatus::PaymentRequired->badgeClass());
        $this->assertSame('bg-slate-200 text-slate-700 dark:bg-slate-700/40 dark:text-slate-300', ServerStatus::Inactive->badgeClass());
    }
}
