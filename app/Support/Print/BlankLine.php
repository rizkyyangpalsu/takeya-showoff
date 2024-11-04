<?php

namespace App\Support\Print;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class BlankLine implements Arrayable, Jsonable
{
    public function toArray()
    {
        return [
            'type' => 'blank_line',
        ];
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | $options);
    }
}
