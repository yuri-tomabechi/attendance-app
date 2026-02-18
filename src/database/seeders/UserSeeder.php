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
            ]);
    }

}
