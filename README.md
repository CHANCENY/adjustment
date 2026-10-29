# Adjustment

A PHP library for applying fixed, percentage-based, and custom adjustments to a
[`simp/price`](https://packagist.org/packages/simp/price) `Price`.

## Requirements

- PHP 8.1 or later
- Composer

## Installation

```bash
composer require simp/adjustment
```

## Usage

Create adjustments and apply them to a `Price`. Adjustments are applied in order,
so percentage adjustments are calculated against the total after earlier
adjustments have been applied. Use a negative value for a discount.

```php
<?php

require 'vendor/autoload.php';

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
```

The adjusted total in this example is `94.5 USD`: the fixed fee is added first,
then the percentage discount is calculated from the updated total.

### Custom adjustments

For custom calculations, pass a callable as the fifth argument. It receives the
current total amount and the adjustment value, and must return a finite number
to add to the total.

```php
$bulkDiscount = new Adjustment(
    'Bulk discount',
    'Discount orders of at least $120',
    10.00,
    Adjustment::TYPE_CUSTOM,
    static fn (float $currentTotal, float $discount): float => $currentTotal >= 120
        ? -$discount
        : 0.0,
);
```

See [`examples/basic-usage.php`](examples/basic-usage.php) and
[`examples/custom-adjustment.php`](examples/custom-adjustment.php) for complete
examples.

## Managing adjustments

`Adjustments` accepts any number of `Adjustment` objects. Use `add()` and
`remove()` to update the collection, `all()` to retrieve its items, and iterate
over it or call `count()` to inspect it.

## Tests

The test suite uses PHPUnit 13 and requires PHP 8.3 or later. Install the
development dependencies and run the tests:

```bash
composer install
vendor/bin/phpunit tests
```

## License

This library is available under the [MIT License](LICENSE).
