<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'id' => Str::uuid(),
                'name' => 'Admin Officer',
                'password' => Hash::make('admin123'),
                'rank' => 'พันเอก',
                'unit' => 'กองบัญชาการ',
                'role' => 'admin',
                'is_active' => true,
                'email_verified' => true,
            ]
        );
    }
}
