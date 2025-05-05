<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SetAdminUserSeeder extends Seeder
{
    /**
     * Establece un usuario existente como administrador.
     */
    public function run(): void
    {
        $username = 'daniel55t';

        $user = User::where('username', $username)->first();

        if ($user) {
            $user->role = 'admin';
            $user->save();
            
            $this->command->info("Usuario {$username} establecido como administrador correctamente.");
        } else {
            $this->command->error("No se encontró ningún usuario con el nombre de usuario {$username}.");
        }
    }
}
