<?php

declare(strict_types=1);

namespace Simp\Adjustment\Tests\Unit;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Simp\Adjustment\lib\Adjustment;
use Simp\Adjustment\lib\Adjustments;
use Simp\Adjustment\Tests\Support\PriceStub;

require_once __DIR__ . '/../Support/PriceStub.php';
require_once __DIR__ . '/../../src/lib/Adjustment.php';
require_once __DIR__ . '/../../src/lib/Adjustments.php';

final class AdjustmentsTest extends TestCase
{
    public function testAddsRemovesAndIteratesAdjustments(): void
    {
        $fee = new Adjustment('Fee', 'Service fee', 5.0);
        $discount = new Adjustment('Discount', 'Order discount', -10.0);
        $adjustments = new Adjustments($fee);

        self::assertSame($adjustments, $adjustments->add($discount));
        self::assertCount(2, $adjustments);
        self::assertSame([$fee, $discount], $adjustments->all());
        self::assertSame([$fee, $discount], iterator_to_array($adjustments));
        self::assertTrue($adjustments->remove($fee));
        self::assertFalse($adjustments->remove($fee));
        self::assertSame([$discount], $adjustments->all());
    }

    public function testAppliesAdjustmentsSequentially(): void
    {
        $orderTotal = new PriceStub(100.0);
        $adjustments = new Adjustments(
            new Adjustment('Fee', 'Fixed fee', 5.0),
            new Adjustment('Discount', 'Percentage discount', -10.0, Adjustment::TYPE_PERCENTAGE),
        );

        self::assertSame($orderTotal, $adjustments->applyTo($orderTotal));
        self::assertSame(94.5, $orderTotal->getAmount());
    }

    public function testConvertsAdjustmentsToJsonString(): void
    {
        $adjustments = new Adjustments(new Adjustment('Fee', 'Service fee', 5.0));

        self::assertSame(
            '[{"name":"Fee","description":"Service fee","value":5,"type":"fixed"}]',
            (string) $adjustments,
        );
    }

    public function testSerializesAdjustmentMetadata(): void
    {
        $adjustments = new Adjustments(
            new Adjustment('Fee', 'Service fee', 5.0),
            new Adjustment(
                'Custom',
                'Custom calculation',
                2.0,
                Adjustment::TYPE_CUSTOM,
                static fn (float $total, float $value): float => $total * $value,
            ),
        );

        $restored = unserialize(serialize($adjustments));

        self::assertInstanceOf(Adjustments::class, $restored);
        self::assertSame('Fee', $restored->all()[0]->getName());
        self::assertSame(Adjustment::TYPE_CUSTOM, $restored->all()[1]->getType());
        $this->expectException(LogicException::class);
        $restored->all()[1]->calculateAmount(new PriceStub(100.0));
    }

    public function testRejectsInvalidSerializedData(): void
    {
        $adjustments = new Adjustments();

        $this->expectException(InvalidArgumentException::class);
        $adjustments->__unserialize(['adjustments' => [['invalid' => true]]]);
    }

    public function testDebugInfoContainsAdjustments(): void
    {
        $adjustment = new Adjustment('Fee', 'Service fee', 5.0);
        $adjustments = new Adjustments($adjustment);

        self::assertSame(['adjustments' => [$adjustment]], $adjustments->__debugInfo());
    }
}
