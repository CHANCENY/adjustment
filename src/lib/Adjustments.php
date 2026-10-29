<?php

declare(strict_types=1);

namespace Simp\Adjustment\lib;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;
use Simp\Price\lib\Price;
use Traversable;

class Adjustments implements Countable, IteratorAggregate
{
    /**
     * @var list<Adjustment>
     */
    private array $adjustments = [];

    public function __construct(Adjustment ...$adjustments)
    {
        $this->adjustments = $adjustments;
    }

    public function add(Adjustment $adjustment): self
    {
        $this->adjustments[] = $adjustment;

        return $this;
    }

    public function remove(Adjustment $adjustment): bool
    {
        foreach ($this->adjustments as $index => $existingAdjustment) {
            if ($existingAdjustment === $adjustment) {
                array_splice($this->adjustments, $index, 1);

                return true;
            }
        }

        return false;
    }

    /**
     * @return list<Adjustment>
     */
    public function all(): array
    {
        return $this->adjustments;
    }

    public function applyTo(Price $orderTotal): Price
    {
        foreach ($this->adjustments as $adjustment) {
            $adjustment->applyTo($orderTotal);
        }

        return $orderTotal;
    }

    public function count(): int
    {
        return count($this->adjustments);
    }

    /**
     * @return Traversable<int, Adjustment>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->adjustments);
    }

    public function __toString(): string
    {
        return json_encode(
            array_map($this->adjustmentData(...), $this->adjustments),
            JSON_THROW_ON_ERROR,
        );
    }

    public function __serialize(): array
    {
        return ['adjustments' => array_map($this->adjustmentData(...), $this->adjustments)];
    }

    public function __unserialize(array $data): void
    {
        if (!isset($data['adjustments']) || !is_array($data['adjustments'])) {
            throw new InvalidArgumentException('Serialized adjustments must contain an adjustments list.');
        }

        $this->adjustments = [];
        foreach ($data['adjustments'] as $item) {
            if (
                !is_array($item)
                || !isset($item['name'], $item['description'], $item['value'], $item['type'])
                || !is_string($item['name'])
                || !is_string($item['description'])
                || (!is_int($item['value']) && !is_float($item['value']))
                || !is_string($item['type'])
            ) {
                throw new InvalidArgumentException('Serialized adjustment data is invalid.');
            }

            $type = $item['type'];
            $calculator = null;
            if ($type === Adjustment::TYPE_CUSTOM) {
                $calculator = static function (): never {
                    throw new LogicException('The custom calculator was not included in the serialized adjustments.');
                };
            }

            $this->adjustments[] = new Adjustment(
                $item['name'],
                $item['description'],
                (float) $item['value'],
                $type,
                $calculator,
            );
        }
    }

    public function __debugInfo(): array
    {
        return ['adjustments' => $this->adjustments];
    }

    /**
     * @return array{name: string, description: string, value: float, type: string}
     */
    private function adjustmentData(Adjustment $adjustment): array
    {
        return [
            'name' => $adjustment->getName(),
            'description' => $adjustment->getDescription(),
            'value' => $adjustment->getValue(),
            'type' => $adjustment->getType(),
        ];
    }
}
