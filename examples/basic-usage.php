<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Simp\Adjustment\lib\Adjustment;
use Simp\Adjustment\lib\Adjustments;
use Simp\Price\lib\Price;

$orderTotal = new Price(100.00, 'USD');

$adjustments = new Adjustments(
    new Adjustment('Service fee', 'Order service fee', 5.00),
    new Adjustment(
        'Seasonal discount',
        'Ten percent seasonal discount',
        -10.0,
        Adjustment::TYPE_PERCENTAGE,
    ),
);

$adjustments->applyTo($orderTotal);

printf(
    "Adjusted total: %s %s\n",
    $orderTotal->getCurrency()->getCode(),
    $orderTotal->getAmount(),
);
