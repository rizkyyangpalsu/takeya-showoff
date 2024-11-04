<?php

namespace App\Jobs\LayoutSeat;

use App\Models\Fleet\Layout;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class UpdateExistingLayoutSeat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Models\Fleet\Layout
     */
    public Layout $layout;
    private array $attributes;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\Fleet\Layout $layout
     * @param array $attributes
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(Layout $layout, array $attributes)
    {
        $this->layout = $layout;
        $this->attributes = Validator::make($attributes, [
            'name' => 'required',
            'description' => 'nullable',
            'seats' => 'nullable|array',
            'seats.*.hash' => ['nullable', new ExistsByHash(Layout\Seat::class)],
            'seats.*.name' => 'required',
            'seats.*.label' => 'nullable',
            'seats.*.selectable' => 'required|boolean',
            'seats.*.plot' => 'required|array',
            'seats.*.plot.x' => 'required|int',
            'seats.*.plot.y' => 'required|int',
            'seats.*.plot.w' => 'required|int',
            'seats.*.plot.h' => 'required|int',
        ])->validate();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->layout->fill($this->attributes);

        $this->layout->save();

        $usesSeats = collect($this->attributes['seats'])
            ->map(fn (array $attribute) => array_key_exists('hash', $attribute) ? array_merge($attribute, ['id' => Layout\Seat::hashToId($attribute['hash'])]) : $attribute)
            ->map(fn (array $attribute) => $this->layout->seats()->updateOrCreate(['id' => array_key_exists('id', $attribute) ? $attribute['id'] : null], $attribute));

        $this->layout->seats()->whereNotIn('id', $usesSeats->map->id)->delete();
    }
}
