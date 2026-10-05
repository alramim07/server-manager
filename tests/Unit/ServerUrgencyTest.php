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

    public function test_same_day_renewal_is_critical(): void
    {
        Carbon::setTestNow('2026-10-05 12:00:00');

        $today = new Server(['renewal_date' => '2026-10-05']);

        $this->assertSame(RenewalUrgency::Critical, $today->renewalUrgency());
    }

    public function test_seven_days_is_critical_and_eight_is_warning(): void
    {
        Carbon::setTestNow('2026-10-05 12:00:00');

        $inSeven = new Server(['renewal_date' => '2026-10-12']);
        $inEight = new Server(['renewal_date' => '2026-10-13']);

        $this->assertSame(RenewalUrgency::Critical, $inSeven->renewalUrgency());
        $this->assertSame(RenewalUrgency::Warning, $inEight->renewalUrgency());
    }

    public function test_billing_warning_boundary_is_exactly_fourteen_days(): void
    {
        Carbon::setTestNow('2026-10-05 12:00:00');

        $atFourteen = new Server(['status' => ServerStatus::PaymentRequired, 'renewal_date' => '2026-10-19']);
        $atFifteen = new Server(['status' => ServerStatus::PaymentRequired, 'renewal_date' => '2026-10-20']);

        $this->assertTrue($atFourteen->billingWarning());
        $this->assertFalse($atFifteen->billingWarning());
    }

    public function test_day_math_stays_correct_across_a_dst_transition(): void
    {
        $previousTimezone = date_default_timezone_get();
        date_default_timezone_set('America/New_York');

        try {
            Carbon::setTestNow('2026-03-05 12:00:00');

            $server = new Server(['renewal_date' => '2026-03-13']);

            $this->assertSame(RenewalUrgency::Warning, $server->renewalUrgency());
        } finally {
            date_default_timezone_set($previousTimezone);
        }
    }
}
