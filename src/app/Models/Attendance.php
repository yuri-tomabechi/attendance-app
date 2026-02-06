<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BreakTime;
use App\Models\AttendanceRequest;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'work_date',
        'status',
        'clock_in',
        'clock_out',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function requests()
    {
        return $this->hasMany(AttendanceRequest::class);
    }
}
