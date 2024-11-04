<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;

class Template extends Model
{
    use HasFactory;
    use HashableId;

    protected $table = 'templates';

    protected $fillable = [
        'body',
        'type',
        'office_id'
    ];

    protected $appends = [
        'hash',
    ];

    protected $hidden = [
        'id',
        'office_id'
    ];


    public function office()
    {
        return $this->belongsTo(Office::class, 'office_id');
    }
}
