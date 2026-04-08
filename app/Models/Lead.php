<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'index_scores_json' => 'array',
        'answers_json' => 'array',
        'converted_to_client' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'converted_to_client');
    }
}
