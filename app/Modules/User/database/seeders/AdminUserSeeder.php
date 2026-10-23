<?php

namespace App\Modules\User\database\seeders;

use App\Modules\User\Models\User;
use Illuminate\Database\Seeder;

/**
 * The admin panel login: admin@example.com / password. A changed password is kept.
 */
class AdminUserSeeder extends Seeder
{
    public const EMAIL = 'admin@example.com';

    public function run(): void
    {
        User::query()->firstOrCreate(['email' => self::EMAIL], [
            'name'     => 'Администратор',
            'password' => 'password',
        ]);
    }
}
