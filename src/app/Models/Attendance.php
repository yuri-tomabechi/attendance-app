<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BreakTime;
use Carbon\Carbon;
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

    protected $casts = [
        'work_date' => 'date',
        'clock_in'  => 'datetime',
        'clock_out' => 'datetime',
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

    public function getTotalBreakMinutesAttribute()
    {
        return $this->breaks->sum(function ($break) {
            if ($break->break_start && $break->break_end) {
                return $break->break_end->diffInMinutes($break->break_start);
            }
            return 0;
        });
    }

    public function getFormattedBreakTimeAttribute()
    {
        $minutes = $this->total_break_minutes;

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }

    public function getFormattedWorkTimeAttribute()
    {
        if (!$this->clock_in || !$this->clock_out) {
            return '00:00';
        }

        $workMinutes = $this->clock_out->diffInMinutes($this->clock_in);

        $actualMinutes = $workMinutes - $this->total_break_minutes;

        $hours = floor($actualMinutes / 60);
        $minutes = $actualMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
