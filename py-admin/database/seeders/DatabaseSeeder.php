<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminRole = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin',
        ]);

        $editorRole = Role::create([
            'name' => 'Redaktor',
            'slug' => 'editor',
        ]);

        User::create([
            'name' => 'Admin Pyxis',
            'email' => 'admin@pyxis.pl',
            'password' => Hash::make('secret123'),
            'role_id' => $adminRole->id,
        ]);

        $this->command->info('Sukces: Role stworzone i konto admina (admin@pyxis.pl) gotowe!');
    }
}
