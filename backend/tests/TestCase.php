<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Setup the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Additional setup can be added here
        $this->withoutVite();
    }
}
