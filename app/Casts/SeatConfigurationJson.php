<?php

namespace App\Casts;

use App\Support\SeatConfigurator;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class SeatConfigurationJson implements CastsAttributes
{
    /** {@inheritdoc} */
    public function get($model, string $key, $value, array $attributes)
    {
        $json = json_decode($value, true);

        /** @var \App\Contracts\HasLayout $model */
        return is_null($json)
            ? new SeatConfigurator($model->getLayout())
            : new SeatConfigurator($model->getLayout(), $json);
    }

    /** {@inheritdoc} */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value instanceof SeatConfigurator) {
            return $value->toJson();
        }

        return null;
    }
}
