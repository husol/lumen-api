<?php

namespace App\DataServices\Order;

use App\DataServices\EloquentRepo;
use App\Models\Order;

class OrderRepo extends EloquentRepo implements OrderRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return Order::class;
    }
}
