<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Veelasky\LaravelHashId\Eloquent\HashableId;

class BankAccount extends Model
{
    use HasFactory, HashableId;

    protected $fillable = ['name', 'account', 'bank_code'];

    protected $appends = ['hash'];

    protected $hidden = ['id'];
}
