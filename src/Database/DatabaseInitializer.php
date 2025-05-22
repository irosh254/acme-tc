<?php

namespace Acme\Database;

use PDO;
use PDOException;

class DatabaseInitializer
{
    private PDO $pdo;

    public function __construct(string $databasePath)
    {
        $this->pdo = new PDO("sqlite:{$databasePath}");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function initialize(): void
    {
        try {
            $this->pdo->beginTransaction();
            
            //  products table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS products (
                    code VARCHAR(3) PRIMARY KEY,
                    name VARCHAR(50) NOT NULL,
                    price DECIMAL(10,2) NOT NULL
                )
            ");

            // delivery_rules table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS delivery_rules (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    min_amount DECIMAL(10,2) NOT NULL,
                    max_amount DECIMAL(10,2) NULL,
                    delivery_cost DECIMAL(10,2) NOT NULL
                )
            ");

            // special_offers table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS special_offers (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    product_code VARCHAR(3) NOT NULL,
                    offer_type VARCHAR(50) NOT NULL,
                    discount_value DECIMAL(10,2) NOT NULL,
                    min_quantity INTEGER NOT NULL,
                    FOREIGN KEY (product_code) REFERENCES products(code)
                )
            ");

            // Insert product data
            $this->pdo->exec("
                INSERT OR IGNORE INTO products (code, name, price) VALUES
                ('R01', 'Red Widget', 32.95),
                ('G01', 'Green Widget', 24.95),
                ('B01', 'Blue Widget', 7.95)
            ");

            // Insert delivery rules
            $this->pdo->exec("
                INSERT OR IGNORE INTO delivery_rules (min_amount, max_amount, delivery_cost) VALUES
                (0, 49.99, 4.95),
                (50, 89.99, 2.95),
                (90, NULL, 0)
            ");

            // Insert special offer for Red Widget
            $this->pdo->exec("
                INSERT OR IGNORE INTO special_offers (product_code, offer_type, discount_value, min_quantity) VALUES
                ('R01', 'HALF_PRICE_SECOND', 0.5, 2)
            ");

            $this->pdo->commit();

        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new \RuntimeException("Database initialization failed: " . $e->getMessage(), 0, $e);
        }
    }
}
