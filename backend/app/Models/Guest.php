<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'identity_reference',
        'notes',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
