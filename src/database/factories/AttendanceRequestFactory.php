<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AttendanceRequest;
use App\Models\User;
use App\Models\Attendance;

class AttendanceRequestFactory extends Factory
{
    protected $model = AttendanceRequest::class;
    
    public function definition()
    {
        return [
            'attendance_id' => Attendance::factory(),
            'user_id'       => User::factory(),
            'reason'        => $this->faker->sentence(),
            'status'        => 'pending',
        ];
    }
}
