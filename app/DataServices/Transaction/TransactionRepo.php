<?php

namespace App\DataServices\Transaction;

use App\DataServices\EloquentRepo;
use App\Models\Transaction;

class TransactionRepo extends EloquentRepo implements TransactionRepoInterface
{
    /**
     * Get model
     * @return string
     */
    public function getModel()
    {
        return Transaction::class;
    }
}
