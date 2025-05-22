<?php

namespace Acme\Basket;

use PDO;

class Basket
{
    private PDO $pdo;
    private array $items = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function add(string $productCode, int $quantity = 1): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Quantity must be greater than zero");
        }

        $stmt = $this->pdo->prepare('SELECT code, price FROM products WHERE code = ?');
        $stmt->execute([$productCode]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new \InvalidArgumentException("Product not found: {$productCode}");
        }

        // Add the product multiple times based on quantity
        for ($i = 0; $i < $quantity; $i++) {
            $this->items[] = $product;
        }
    }

    public function total(): float
    {
        if (empty($this->items)) {
            return 0.0;
        }

        $subtotal = $this->calculateSubtotal();
        $discount = $this->calculateDiscount();
        $deliveryCost = $this->calculateDeliveryCost($subtotal - $discount);

        return (float) number_format($subtotal - $discount + $deliveryCost, 2, '.', '');
    }

    private function calculateSubtotal(): float
    {
        return array_sum(array_column($this->items, 'price'));
    }

    private function calculateDiscount(): float
    {
        $discount = 0.0;
        $productCounts = array_count_values(array_column($this->items, 'code'));
        
        // Prepare statements outside the loop for better performance
        $offerStmt = $this->pdo->prepare('
            SELECT offer_type, discount_value, min_quantity 
            FROM special_offers 
            WHERE product_code = ?
        ');
        
        $priceStmt = $this->pdo->prepare('SELECT price FROM products WHERE code = ?');

        foreach ($productCounts as $productCode => $quantity) {
            $offerStmt->execute([$productCode]);
            $offer = $offerStmt->fetch(PDO::FETCH_ASSOC);

            if ($offer && $quantity >= $offer['min_quantity']) {
                if ($offer['offer_type'] === 'HALF_PRICE_SECOND') {
                    $priceStmt->execute([$productCode]);
                    $product = $priceStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $discountQuantity = floor($quantity / 2);
                    $discount += $discountQuantity * ($product['price'] * $offer['discount_value']);
                }
            }
        }

        return $discount;
    }

    private function calculateDeliveryCost(float $total): float
    {
        $stmt = $this->pdo->prepare('
            SELECT delivery_cost 
            FROM delivery_rules 
            WHERE min_amount <= ? 
            AND (max_amount IS NULL OR max_amount >= ?)
            ORDER BY min_amount DESC 
            LIMIT 1
        ');
        $stmt->execute([$total, $total]);
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);

        return $rule ? $rule['delivery_cost'] : 0.0;
    }
}
