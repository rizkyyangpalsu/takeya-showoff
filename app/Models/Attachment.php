<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;

/**
 * @property  string path
 */
class Attachment extends Model
{
    use HasFactory, HashableId;

    protected $fillable = ['transaction_id', 'title', 'size', 'path', 'mime'];

    protected $hidden = ['id', 'path', 'transaction_id'];

    protected $appends = ['hash', 'url'];

    public function getUrlAttribute()
    {
        return route('attachment', $this->hash);
    }
}
