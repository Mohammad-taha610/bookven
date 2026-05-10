<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndoorType extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'icon_key',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class, 'indoor_facility_kind', 'slug');
    }
}
