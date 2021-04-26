<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchIndexData extends Model
{
    protected $table = 'search_index_data';

    protected $fillable = [
        'link',
        'title',
        'text'
    ];
}
