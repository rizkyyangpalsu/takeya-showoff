<?php

namespace App\Models\Fleet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Collection;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Layout.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $capacity
 * @property-read string $hash
 * @property-read Collection<Layout\Seat> $seats
 */
class Layout extends Model
{
    use HasFactory, HashableId;

    protected $fillable = [
        'name',
        'description',
    ];

    protected $hidden = [
        'id',
    ];

    protected $appends = [
        'hash',
    ];

    public function seats(): Relations\HasMany
    {
        return $this->hasMany(Layout\Seat::class, 'layout_id', 'id');
    }

    /**
     * Scope for search.
     *
     * @param Builder $builder
     * @param $keyword
     * @return Builder
     */
    public function scopeSearch(Builder $builder, $keyword)
    {
        return $builder->where('name', 'like', '%'.$keyword.'%');
    }
}
