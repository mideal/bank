<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_add(): void
    {
        $result = new Money('100.00');
        $this->assertSame('150.00', $result->add(new Money('50.00'))->amount);
    }

    public function test_subtract(): void
    {
        $result = new Money('100.00');
        $this->assertSame('75.00', $result->subtract(new Money('25.00'))->amount);
    }

    public function test_is_less_than(): void
    {
        $this->assertTrue((new Money('50.00'))->isLessThan(new Money('100.00')));
        $this->assertFalse((new Money('100.00'))->isLessThan(new Money('50.00')));
        $this->assertFalse((new Money('100.00'))->isLessThan(new Money('100.00')));
    }

    public function test_invalid_amount_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Money('abc');
    }

    public function test_is_immutable(): void
    {
        $original = new Money('100.00');
        $original->add(new Money('50.00'));
        $this->assertSame('100.00', $original->amount);
    }

    public function test_precision_is_two_decimal_places(): void
    {
        $result = new Money('100.00')->add(new Money('0.005'));
        $this->assertSame('100.00', $result->amount);
    }
}
