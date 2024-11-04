<?php

namespace App\Actions\Logistic\Delivery;

use Carbon\Carbon;

class ActionAfterArrived
{
    public function paid($delivery)
    {
        $delivery->paid = Carbon::now();
        $delivery->save();

        return $delivery;
    }

    public function taken($delivery)
    {
        $delivery->taken = Carbon::now();
        $delivery->save();

        return $delivery;
    }
}
