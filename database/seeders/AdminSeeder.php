<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@swipeszter.hu'],
            [
                'name'     => 'Admin',
                'email'    => 'admin@swipeszter.hu',
                'password' => Hash::make('swipeszter2026'),
            ]
        );

        $this->command->info('✅ Admin létrehozva: admin@swipeszter.hu / swipeszter2026');
    }
}
