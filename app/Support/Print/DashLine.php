<?php

namespace App\Support\Print;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class DashLine implements Arrayable, Jsonable
{
    public function toArray()
    {
        return [
            'type' => 'dash_line',
            'text' => '--------------------------------',
            'size' => 1,
            'align' => 0,
        ];
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | $options);
    }
}
