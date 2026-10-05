<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
    'user_id',
    'room_id',
    'check_in_date',
    'check_out_date',
    'number_of_guests',
    'total_amount',
    'status',
    'special_request',
];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(BookingStatusLog::class);
    }
}
