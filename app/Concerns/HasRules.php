<?php

namespace App\Concerns;

use App\Models\Rule;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Collection;

/**
 * Trait HasRules.
 *
 * @property-read  Collection  rules
 */
trait HasRules
{
    public function rules(): Relations\MorphMany
    {
        return $this->morphMany(Rule::class, 'applicable');
    }
}
