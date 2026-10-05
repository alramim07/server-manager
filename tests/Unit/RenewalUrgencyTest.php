<?php

namespace Tests\Unit;

use App\Enums\RenewalUrgency;
use PHPUnit\Framework\TestCase;

class RenewalUrgencyTest extends TestCase
{
    public function test_each_urgency_exposes_a_text_class_for_presentation(): void
    {
        $this->assertSame('text-rose-600 dark:text-rose-400 font-semibold', RenewalUrgency::Critical->textClass());
        $this->assertSame('text-amber-600 dark:text-amber-400', RenewalUrgency::Warning->textClass());
        $this->assertSame('text-slate-500 dark:text-slate-400', RenewalUrgency::None->textClass());
    }
}
