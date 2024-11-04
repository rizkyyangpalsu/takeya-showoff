<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Fleet.
 *
 * @property int $id
 * @property int $base_id
 * @property int $layout_id
 * @property string $manufacturer
 * @property string $license_plate
 * @property \Carbon\Carbon $license_expired_at
 * @property string $hull_number
 * @property string $engine_number
 * @property string $model
 * @property int $mileage
 * @property \Carbon\Carbon $last_maintenance
 * @property \Carbon\Carbon $purchased_at
 * @property bool $is_operable
 * @property string $police_number
 * @property int $capacity
 * @property array $specs
 *
 * @property-read Fleet\Layout $layout
 * @property-read \App\Models\Office $base
 */
class Fleet extends Model
{
    use HasFactory, HashableId;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fleets';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'manufacturer',
        'license_plate',
        'license_expired_at',
        'model',
        'hull_number',
        'engine_number',
        'chassis_number',
        'mileage',
        'capacity',
        'specs',
        'last_maintenance',
        'is_operable',
        'purchased_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'license_expired_at' => 'datetime',
        'last_maintenance' => 'datetime',
        'purchased_at' => 'datetime',
        'is_operable' => 'bool',
        'specs' => 'array',
        'capacity' => 'int',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'hash',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'deleted_at',
        'base_id',
        'layout_id',
    ];

    /**
     * Define `belongsTo` relationship with Office model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function base()
    {
        return $this->belongsTo(Office::class, 'base_id', 'id');
    }

    /**
     * Define `belongsTo` relationship with layout model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function layout()
    {
        return $this->belongsTo(Fleet\Layout::class);
    }
}
