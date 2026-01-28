<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AttendanceRequest;

class AttendanceRequestItem extends Model
{
    protected $fillable = [
        'attendance_request_id',
        'type',
        'target_id',
        'before_time',
        'after_time',
    ];

    public function request()
    {
        return $this->belongsTo(AttendanceRequest::class);
    }
}
