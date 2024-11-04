<?php

namespace App\Actions\Logistic\Delivery;

class DeleteExistingDelivery
{
    public function delete($delivery)
    {
        $delivery->delete();

        return $delivery;
    }
}
