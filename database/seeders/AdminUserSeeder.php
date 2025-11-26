<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create or update admin user
        $user = User::firstOrCreate(
            ['email' => 'admin@aniyume.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin', // Ensure this matches your CheckRole middleware logic
            ]
        );

        // Ensure role is set if user already existed
        if ($user->role !== 'admin') {
            $user->role = 'admin';
            $user->save();
        }

        // Create token
        $token = $user->createToken('admin-token')->plainTextToken;

        $this->command->info('Admin User Created/Updated');
        $this->command->info('Email: admin@aniyume.com');
        $this->command->info('Password: password');
        $this->command->info('--------------------------------------------------');
        $this->command->info('ACCESS TOKEN: ' . $token);
        $this->command->info('--------------------------------------------------');
    }
}
