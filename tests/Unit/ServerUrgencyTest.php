<?php

namespace Tests\Unit;

use App\Enums\RenewalUrgency;
use App\Enums\ServerStatus;
use App\Models\Server;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ServerUrgencyTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_no_renewal_date_means_no_urgency(): void
    {
        $server = new Server(['renewal_date' => null]);
        $this->assertSame(RenewalUrgency::None, $server->renewalUrgency());
    }

    public function test_past_and_near_dates_are_critical(): void
    {
        Carbon::setTestNow('2026-10-05 12:00:00');

        $overdue = new Server(['renewal_date' => '2026-10-01']);
        $inSeven = new Server(['renewal_date' => '2026-10-12']);

        $this->assertSame(RenewalUrgency::Critical, $overdue->renewalUrgency());
        $this->assertSame(RenewalUrgency::Critical, $inSeven->renewalUrgency());
    }

    public function test_dates_within_thirty_days_are_warning_and_beyond_are_none(): void
    {
        Carbon::setTestNow('2026-10-05 12:00:00');

        $inTen = new Server(['renewal_date' => '2026-10-15']);
        $inThirty = new Server(['renewal_date' => '2026-11-04']);
        $inThirtyOne = new Server(['renewal_date' => '2026-11-05']);

        $this->assertSame(RenewalUrgency::Warning, $inTen->renewalUrgency());
        $this->assertSame(RenewalUrgency::Warning, $inThirty->renewalUrgency());
        $this->assertSame(RenewalUrgency::None, $inThirtyOne->renewalUrgency());
    }

    public function test_billing_warning_only_for_payment_required_within_fourteen_days(): void
    {
        Carbon::setTestNow('2026-10-05 12:00:00');

        $dueSoon = new Server(['status' => ServerStatus::PaymentRequired, 'renewal_date' => '2026-10-15']);
        $dueLater = new Server(['status' => ServerStatus::PaymentRequired, 'renewal_date' => '2026-11-15']);
        $activeDueSoon = new Server(['status' => ServerStatus::Active, 'renewal_date' => '2026-10-15']);

        $this->assertTrue($dueSoon->billingWarning());
        $this->assertFalse($dueLater->billingWarning());
        $this->assertFalse($activeDueSoon->billingWarning());
    }
}
