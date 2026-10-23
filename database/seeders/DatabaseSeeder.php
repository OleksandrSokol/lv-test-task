<?php

namespace Database\Seeders;

use App\Modules\Notification\database\seeders\LetterTemplateSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(LetterTemplateSeeder::class);
    }
}
