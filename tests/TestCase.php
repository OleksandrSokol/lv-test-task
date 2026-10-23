<?php

namespace Tests;

use App\Modules\Notification\database\seeders\LetterTemplateSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Letter templates, needed by any test that moves an order to a status with a letter.
     */
    protected function seedLetterTemplates(): void
    {
        $this->seed(LetterTemplateSeeder::class);
    }
}
