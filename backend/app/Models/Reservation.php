<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_id',
        'confirmation_code',
        'arrival_date',
        'departure_date',
        'adult_count',
        'child_count',
        'status',
        'source',
        'notes',
    ];

    protected $casts = [
        'arrival_date' => 'data',
        'departure_date' => 'date',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function reservationRooms(): HasMany
    {
        return $this->hasMany(ReservationRoom::class);
    }

    public function rooms(): HasManyThrough
    {
        return $this->hasManyThrough(
            Room::class,
            ReservationRoom::class,
            'reservation_id',
            'id',
            'id',
            'room_id'
        );
    }
}
