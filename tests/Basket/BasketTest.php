<?php

namespace Tests\Basket;

use Acme\Basket\Basket;
use Acme\Database\DatabaseInitializer;
use PHPUnit\Framework\TestCase;
use PDO;

class BasketTest extends TestCase
{
    private PDO $pdo;
    private Basket $basket;
    private array $productPrices = [];
    private array $deliveryRules = [];
    private array $specialOffers = [];

    protected function setUp(): void
    {
        $databasePath = __DIR__ . '/../../database/cart.sqlite';
        
        // Connect to the existing database
        $this->pdo = new PDO("sqlite:{$databasePath}");
        
        // Load product prices, delivery rules, and special offers from the database
        $this->loadProductPrices();
        $this->loadDeliveryRules();
        $this->loadSpecialOffers();
        
        // New basket instance for each test
        $this->basket = new Basket($this->pdo);
    }
    
    private function loadProductPrices(): void
    {
        $stmt = $this->pdo->query('SELECT code, price FROM products');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->productPrices[$row['code']] = (float)$row['price'];
        }
    }
    
    private function loadDeliveryRules(): void
    {
        $stmt = $this->pdo->query('SELECT min_amount, max_amount, delivery_cost FROM delivery_rules ORDER BY min_amount');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->deliveryRules[] = [
                'min' => (float)$row['min_amount'],
                'max' => $row['max_amount'] !== null ? (float)$row['max_amount'] : null,
                'cost' => (float)$row['delivery_cost']
            ];
        }
    }
    
    private function loadSpecialOffers(): void
    {
        $stmt = $this->pdo->query('SELECT product_code, offer_type, discount_value, min_quantity FROM special_offers');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->specialOffers[$row['product_code']] = [
                'type' => $row['offer_type'],
                'discount' => (float)$row['discount_value'],
                'min_quantity' => (int)$row['min_quantity']
            ];
        }
    }
    
    private function calculateExpectedTotal(array $products): float
    {
        // Calculate subtotal
        $subtotal = 0;
        $productCounts = [];
        
        foreach ($products as $item) {
            if (is_array($item)) {
                $code = $item['code'];
                $quantity = $item['quantity'];
            } else {
                $code = $item;
                $quantity = 1;
            }
            
            $productCounts[$code] = isset($productCounts[$code]) ? $productCounts[$code] + $quantity : $quantity;
            $subtotal += $this->productPrices[$code] * $quantity;
        }
        
        // Calculate discounts
        $discount = 0;
        foreach ($productCounts as $code => $quantity) {
            if (isset($this->specialOffers[$code]) && $quantity >= $this->specialOffers[$code]['min_quantity']) {
                $offer = $this->specialOffers[$code];
                if ($offer['type'] === 'HALF_PRICE_SECOND') {
                    $discountQuantity = floor($quantity / 2);
                    $discount += $discountQuantity * ($this->productPrices[$code] * $offer['discount']);
                }
            }
        }
        
        // Calculate delivery cost
        $afterDiscount = $subtotal - $discount;
        $deliveryCost = 0;
        
        foreach ($this->deliveryRules as $rule) {
            if ($afterDiscount >= $rule['min'] && ($rule['max'] === null || $afterDiscount <= $rule['max'])) {
                $deliveryCost = $rule['cost'];
                break;
            }
        }
        
        // Format to 2 decimal places for consistent comparison
        return (float) number_format($subtotal - $discount + $deliveryCost, 2, '.', '');
    }

    public function testEmptyBasketTotalIsZero(): void
    {
        $this->assertEquals(0.0, $this->basket->total());
    }

    /**
     * @dataProvider basketProvider
     */
    public function testBasketTotal(array $products): void
    {
        foreach ($products as $item) {
            if (is_array($item)) {
                $this->basket->add($item['code'], $item['quantity']);
            } else {
                $this->basket->add($item);
            }
        }

        $expectedTotal = $this->calculateExpectedTotal($products);
        $this->assertEquals($expectedTotal, $this->basket->total());
    }

    public function basketProvider(): array
    {
        return [
            'B01, G01' => [
                ['B01', 'G01']
            ],
            'R01, R01' => [
                [['code' => 'R01', 'quantity' => 2]]
            ],
            'R01, G01' => [
                ['R01', 'G01']
            ],
            'B01, B01, R01, R01, R01' => [
                [
                    ['code' => 'B01', 'quantity' => 2],
                    ['code' => 'R01', 'quantity' => 3]
                ]
            ]
        ];
    }
}
