<?php

namespace App\Jobs\LayoutSeat;

use App\Models\Fleet\Layout;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateNewLayoutSeat
{
    use Dispatchable;
    /**
     * @var \App\Models\Fleet\Layout
     */
    public Layout $layout;

    private array $attributes;

    /**
     * Create a new job instance.
     *
     * @param array $attributes
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(array $attributes)
    {
        $this->attributes = Validator::make($attributes, [
            'name' => 'required',
            'description' => 'nullable',
            'seats' => 'required|array',
            'seats.*.name' => 'required',
            'seats.*.selectable' => 'required|boolean',
            'seats.*.label' => 'nullable',
            'seats.*.plot' => 'required|array',
            'seats.*.plot.x' => 'required|int',
            'seats.*.plot.y' => 'required|int',
            'seats.*.plot.w' => 'required|int',
            'seats.*.plot.h' => 'required|int',
        ])->validate();

        $this->layout = new Layout($this->attributes);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->layout->save();

        $this->layout->seats()->createMany($this->attributes['seats']);
    }
}
