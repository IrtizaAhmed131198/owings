<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        // Array to hold the dummy data
        $users = [];

        // First user with admin role_id = 1
        $users[] = [
            'name' => "Admin User",
            'email' => "admin@example.com",
            'email_verified_at' => now(),
            'password' => Hash::make('123456789'), // You can use bcrypt('password') as well
            'remember_token' => Str::random(10),
            'role_id' => 1, // Admin role
            'country' => 'USA',
            'city' => 'New York',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Loop to create 9 more dummy users with role_id 2 or 3
        for ($i = 2; $i <= 10; $i++) {
            $users[] = [
                'name' => "User $i",
                'email' => "user$i@example.com",
                'email_verified_at' => now(),
                'password' => Hash::make('123456789'), // You can use bcrypt('password') as well
                'remember_token' => Str::random(10),
                'role_id' => rand(2, 3), // Random role_id 2 or 3
                'country' => 'Country ' . $i,
                'city' => 'City ' . $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert the users into the database
        DB::table('users')->insert($users);
    }
}
