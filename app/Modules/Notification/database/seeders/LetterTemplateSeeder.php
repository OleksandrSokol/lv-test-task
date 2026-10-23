<?php

namespace App\Modules\Notification\database\seeders;

use App\Modules\Notification\Enums\LetterEnum;
use App\Modules\Notification\Models\LetterTemplate;
use Illuminate\Database\Seeder;

/**
 * Creates the missing templates from lang/{locale}/letter_templates.php; edited ones are kept.
 */
class LetterTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (LetterEnum::cases() as $key) {
            LetterTemplate::query()->firstOrCreate(['key' => $key], [
                'subject' => __("letter_templates.{$key->value}.subject"),
                'body'    => __("letter_templates.{$key->value}.body"),
            ]);
        }
    }
}
