# Acme Widget Co - Sales System

A proof of concept for Acme Widget Co's new sales system, implemented in PHP 8 with SQLite.

## Requirements

- PHP 8.0 or higher
- SQLite3 extension for PHP
- Composer (for autoloading)

## Project Structure

```
.
├── README.md
├── composer.json
├── phpunit.xml
├── bin
│   └── init-db.php
├── database
│   ├── cart.sqlite
│   └── schema.sql
├── src
│   ├── Basket
│   │   └── Basket.php
│   └── Database
│       └── DatabaseInitializer.php
└── tests
    └── Basket
        └── BasketTest.php
```

## Setup

1. Clone the repository
2. Run `composer install`
3. Initialize the database: `php bin/init-db.php`
4. Run the tests: `composer test`

## How It Works

The system implements a shopping basket with the following features:

- Products are stored in the database with their code and price
- Delivery charges are calculated based on the total order amount
- Special offers are applied (buy one red widget, get the second half price)

### Database Structure

The system uses a SQLite database with the following tables:

1. `products` - Stores product information (code, name, price)
2. `delivery_rules` - Stores delivery cost rules based on order amount
3. `special_offers` - Stores special offer configurations

### Basket Interface

```php
// Connect to the database
$pdo = new PDO("sqlite:database/cart.sqlite");

// Create a basket
$basket = new Acme\Basket\Basket($pdo);

// Add products
$basket->add('R01');        // Add a single product
$basket->add('G01', 2);     // Add multiple quantities

// Calculate total
$total = $basket->total();  // Discounts and delivery cost are applied in this function
```

## Testing

The system includes PHPUnit tests that verify all the example cases:

```bash
# Run all tests
composer test

# Run a specific test file
composer test-file -- tests\Basket\BasketTest.php
```

### Example Test Cases

- B01, G01 = $37.85
- R01, R01 = $54.37
- R01, G01 = $60.85
- B01, B01, R01, R01, R01 = $98.27

## Assumptions

1. The product catalog, delivery rules, and offers are stored in a SQLite database
2. The "buy one red widget, get the second half price" offer applies to pairs of red widgets (if you buy 3, only 1 gets the discount)
3. Offers are applied before calculating delivery charges
4. All prices are in USD
5. Product codes are unique
6. The system does not handle user authentication or checkout process as this is a proof of concept
