<?php

namespace App\Jobs\Points;

use App\Models\Geo\Regency;
use Illuminate\Bus\Queueable;
use App\Models\Route\Track\Point;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Bus\Dispatchable;
use Veelasky\LaravelHashId\Rules\ExistsByHash;

class CreateNewPoint
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $attributes;

    /**
     * @var \App\Models\Route\Track\Point
     */
    public Point $point;

    /**
     * Create a new job instance.
     *
     * @param array $attributes
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __construct(array $attributes)
    {
        $this->attributes = Validator::make($attributes, [
            'code' => 'required',
            'name' => 'required',
            'terminal' => 'required',
            'regency_hash' => ['nullable', new ExistsByHash(Regency::class)],
        ])->validate();

        if (array_key_exists('regency_hash', $this->attributes)) {
            $this->attributes['regency_id'] = Regency::hashToId($this->attributes['regency_hash']);
            unset($this->attributes['regency_hash']);
        }

        $this->point = new Point();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->point->fill($this->attributes);

        $this->point->save();
    }
}
