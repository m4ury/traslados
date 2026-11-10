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
            'apellido_paterno' => 'apellidoM',
            'email' => 'admin@mail.com',
            'password' => Hash::make('test1234')
        ]);
    }
}
