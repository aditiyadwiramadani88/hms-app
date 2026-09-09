<?php

namespace App\Exceptions;

use App\Models\TenantProduct;
use Exception;

class InsufficientStockException extends Exception
{
    public TenantProduct $product;
    public int $requestedQuantity;
    public ?int $availableStock;

    public function __construct(TenantProduct $product, int $requestedQuantity, ?int $availableStock = null)
    {
        $this->product = $product;
        $this->requestedQuantity = $requestedQuantity;
        $this->availableStock = $availableStock;

        $message = "Insufficient stock for {$product->name}. ";
        $message .= "Requested: {$requestedQuantity}, Available: " . ($availableStock ?? 'unlimited');

        parent::__construct($message);
    }

    public function getAvailableStock(): ?int
    {
        return $this->availableStock;
    }

    public function getRemainingStock(): ?int
    {
        return $this->availableStock;
    }
}
