<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Настройка перед каждым тестом
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Настройки для тестов
        config(['app.debug' => true]);
    }
}
