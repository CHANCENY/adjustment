<?php

declare(strict_types=1);

namespace Simp\Adjustment\Tests\Support;

use Simp\Price\lib\Price;

final class PriceStub extends Price
{
    public function __construct(private float $amount)
    {
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function add(float|Price $amount): Price
    {
        $this->amount += (float) (string) $amount;

        return $this;
    }
}
