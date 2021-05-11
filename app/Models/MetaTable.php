<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaTable extends Model
{
    protected $table = 'meta_tables';

    protected $fillable = [
        'name',
        'row',
    ];

    protected $casts = [
        'row' => 'array',
    ];
}
