<?php

namespace App\Models\Fleet\Layout;

use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Seat.
 *
 * @property string $name
 * @property bool $selectable
 * @property array $plot
 * @property int id
 */
class Seat extends Model
{
    use HasFactory, HashableId;

    protected $table = 'layout_seats';

    protected $fillable = [
        'name',
        'selectable',
        'label',
        'plot',
    ];

    protected $appends = [
        'hash',
    ];

    protected $hidden = [
        'id',
        'layout_id',
    ];

    protected $casts = [
        'plot' => 'array',
        'selectable' => 'boolean',
    ];
}
