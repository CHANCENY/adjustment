<?php

declare(strict_types=1);

namespace Simp\Adjustment\lib;

use InvalidArgumentException;
use Closure;
use Simp\Price\lib\Price;

class Adjustment
{
    public const TYPE_FIXED = 'fixed';
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_CUSTOM = 'custom';

    private ?Closure $customCalculator;

    public function __construct(
        private string $name,
        private string $description,
        private float $value,
        private string $type = self::TYPE_FIXED,
        ?callable $customCalculator = null,
    ) {
        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Adjustment name cannot be empty.');
        }

        if (trim($this->description) === '') {
            throw new InvalidArgumentException('Adjustment description cannot be empty.');
        }

        if (!is_finite($this->value)) {
            throw new InvalidArgumentException('Adjustment value must be finite.');
        }

        if (!in_array($this->type, [self::TYPE_FIXED, self::TYPE_PERCENTAGE, self::TYPE_CUSTOM], true)) {
            throw new InvalidArgumentException('Adjustment type must be fixed, percentage, or custom.');
        }

        if ($this->type === self::TYPE_CUSTOM && $customCalculator === null) {
            throw new InvalidArgumentException('A custom adjustment requires a calculator.');
        }

        if ($this->type !== self::TYPE_CUSTOM && $customCalculator !== null) {
            throw new InvalidArgumentException('A calculator can only be provided for a custom adjustment.');
        }

        $this->customCalculator = $customCalculator === null
            ? null
            : Closure::fromCallable($customCalculator);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function calculateAmount(Price $orderTotal): float
    {
        if ($this->type === self::TYPE_PERCENTAGE) {
            return $orderTotal->getAmount() * ($this->value / 100);
        }

        if ($this->type === self::TYPE_CUSTOM) {
            $amount = ($this->customCalculator)($orderTotal->getAmount(), $this->value);
            if ((!is_int($amount) && !is_float($amount)) || !is_finite((float) $amount)) {
                throw new InvalidArgumentException('A custom calculator must return a finite number.');
            }

            return (float) $amount;
        }

        return $this->value;
    }

    public function applyTo(Price $orderTotal): Price
    {
        return $orderTotal->add($this->calculateAmount($orderTotal));
    }
}