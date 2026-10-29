<?php

declare(strict_types=1);

namespace Simp\Adjustment\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Simp\Adjustment\lib\Adjustment;
use Simp\Adjustment\Tests\Support\PriceStub;

require_once __DIR__ . '/../Support/PriceStub.php';
require_once __DIR__ . '/../../src/lib/Adjustment.php';

final class AdjustmentTest extends TestCase
{
    public function testStoresAdjustmentDetails(): void
    {
        $adjustment = new Adjustment('Service fee', 'Order service fee', 5.0);

        self::assertSame('Service fee', $adjustment->getName());
        self::assertSame('Order service fee', $adjustment->getDescription());
        self::assertSame(5.0, $adjustment->getValue());
        self::assertSame(Adjustment::TYPE_FIXED, $adjustment->getType());
    }

    public function testCalculatesAndAppliesFixedAdjustment(): void
    {
        $orderTotal = new PriceStub(100.0);
        $adjustment = new Adjustment('Discount', 'Fixed discount', -12.5);

        self::assertSame(-12.5, $adjustment->calculateAmount($orderTotal));
        self::assertSame($orderTotal, $adjustment->applyTo($orderTotal));
        self::assertSame(87.5, $orderTotal->getAmount());
    }

    public function testCalculatesPercentageAgainstCurrentOrderTotal(): void
    {
        $orderTotal = new PriceStub(200.0);
        $adjustment = new Adjustment(
            'Discount',
            'Percentage discount',
            -12.5,
            Adjustment::TYPE_PERCENTAGE,
        );

        self::assertSame(-25.0, $adjustment->calculateAmount($orderTotal));
        $adjustment->applyTo($orderTotal);
        self::assertSame(175.0, $orderTotal->getAmount());
    }

    public function testUsesCustomCalculator(): void
    {
        $orderTotal = new PriceStub(200.0);
        $adjustment = new Adjustment(
            'Custom discount',
            'Custom calculation',
            10.0,
            Adjustment::TYPE_CUSTOM,
            static fn (float $total, float $value): float => -$total * ($value / 100),
        );

        self::assertSame(-20.0, $adjustment->calculateAmount($orderTotal));
        $adjustment->applyTo($orderTotal);
        self::assertSame(180.0, $orderTotal->getAmount());
    }

    public function testRejectsInvalidConstructorArguments(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Adjustment(' ', 'Description', 1.0);
    }

    public function testCustomTypeRequiresCalculator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Adjustment('Custom', 'Description', 1.0, Adjustment::TYPE_CUSTOM);
    }

    public function testCustomCalculatorMustReturnFiniteNumber(): void
    {
        $adjustment = new Adjustment(
            'Custom',
            'Invalid result',
            1.0,
            Adjustment::TYPE_CUSTOM,
            static fn (): string => 'invalid',
        );

        $this->expectException(InvalidArgumentException::class);
        $adjustment->calculateAmount(new PriceStub(100.0));
    }
}
