<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Simp\Adjustment\lib\Adjustment;
use Simp\Adjustment\lib\Adjustments;
use Simp\Price\lib\Price;

$orderTotal = new Price(120.00, 'USD');

$bulkDiscount = new Adjustment(
    'Bulk discount',
    'Discount orders of at least $120',
    10.00,
    Adjustment::TYPE_CUSTOM,
    static fn (float $currentTotal, float $discount): float => $currentTotal >= 120
        ? -$discount
        : 0.0,
);

$adjustments = new Adjustments($bulkDiscount);
$adjustments->applyTo($orderTotal);

printf(
    "Total after custom adjustment: %s %s\n",
    $orderTotal->getCurrency()->getCode(),
    $orderTotal->getAmount(),
);
