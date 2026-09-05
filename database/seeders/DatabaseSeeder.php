<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatalogSeeder::class);

        // Conta de desenvolvimento. Nunca em produção: uma password
        // conhecida num servidor a sério é uma porta aberta.
        if (app()->environment('local', 'testing')) {
            User::firstOrCreate(
                ['email' => 'dev@decorarte.test'],
                [
                    'name' => 'Programador',
                    'password' => Hash::make('password'),
                    'locale' => 'es',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            $this->command?->info('Utilizador local: dev@decorarte.test / password');
        }
    }
}
