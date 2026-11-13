<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'admin',
            'rut' => '9-1',
            'apellido_paterno' => 'apellidoP',
            'apellido_materno' => 'apellidoM',
            'email' => 'admin@mail.com',
            'role' => 'admin',
            'password' => Hash::make('test1234'),
        ]);
        User::factory()->create([
            'name' => 'conductor',
            'rut' => '8-1',
            'apellido_paterno' => 'apellido',
            'apellido_materno' => 'apellido',
            'email' => 'conductor@mail.com',
            'role' => 'conductor',
            'password' => Hash::make('conductor'),
        ]);
    }
}
