<?php

namespace App\Support\Print;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class PaperRow implements Arrayable, Jsonable
{
    public function __construct(
        public string|null $text = null,
        public int $size = 0,
        public int $align = 0,
    ) {
    }

    public function toArray()
    {
        return [
            'type' => 'row',
            'text' => $this->text,
            'size' => $this->size,
            'align' => $this->align,
        ];
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | $options);
    }
}
