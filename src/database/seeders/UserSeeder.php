<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create(
            [
                'name' => '管理者',
                'email' => 'admin@test.com',
                'password' => Hash::make('12341234'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);

        User::create([
            'name' => '山田太郎',
            'email' => 'user@test.com',
            'password' => Hash::make('12341234'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);


        User::factory()->count(2)->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::factory()->count(5)->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
    }

}
