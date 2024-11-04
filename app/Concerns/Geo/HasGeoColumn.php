<?php

namespace App\Concerns\Geo;

use App\Models\Geo;
use Illuminate\Database\Eloquent\Relations;

trait HasGeoColumn
{
    public static function bootHasGeoColumn(): void
    {
        self::saving(function (self $model) {
            if ($model->isDirty('regency_id') && isset($model->regency_id) && $model->isFillable('province_id')) {
                /** @var \App\Models\Geo\Regency $regency */
                $regency = Geo\Regency::query()->find($model->regency_id);
                $model->province()->associate($regency->province_id);
            }
        });
    }

    public function province(): Relations\BelongsTo
    {
        return $this->belongsTo(Geo\Province::class, 'province_id');
    }

    public function regency(): Relations\BelongsTo
    {
        return $this->belongsTo(Geo\Regency::class, 'regency_id');
    }
}
