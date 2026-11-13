<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The game views reference the Vite bundle; tests should not need a built manifest.
        $this->withoutVite();
    }
}
