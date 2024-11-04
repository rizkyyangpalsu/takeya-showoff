<?php

namespace App\Actions\Logistic\Price;

class DeleteExistingPrice
{
    public function delete($price)
    {
        $price->delete();

        $price->load(['originCity', 'destinationCity']);

        return $price;
    }
}
