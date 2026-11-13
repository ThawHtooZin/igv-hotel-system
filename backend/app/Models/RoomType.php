<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'max_occupancy',
        'base_rate',
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
