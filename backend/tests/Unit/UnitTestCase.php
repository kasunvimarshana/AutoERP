<?php

namespace Tests\Unit;

use Mockery;
use Tests\TestCase;

/**
 * Base Unit Test Case
 *
 * Provides common functionality for unit tests including
 * mocking helpers and assertions.
 */
abstract class UnitTestCase extends TestCase
{
    /**
     * Tear down the test case.
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mockery mock for a class.
     */
    protected function mockInstance(string $class, ?\Closure $callback = null)
    {
        $mock = Mockery::mock($class);

        if ($callback) {
            $callback($mock);
        }

        $this->app->instance($class, $mock);

        return $mock;
    }

    /**
     * Create a partial mock for a class.
     */
    protected function partialMockInstance(string $class, ?\Closure $callback = null)
    {
        $mock = Mockery::mock($class)->makePartial();

        if ($callback) {
            $callback($mock);
        }

        $this->app->instance($class, $mock);

        return $mock;
    }

    /**
     * Create a spy for a class.
     */
    protected function spyInstance(string $class, ?\Closure $callback = null)
    {
        $spy = Mockery::spy($class);

        if ($callback) {
            $callback($spy);
        }

        $this->app->instance($class, $spy);

        return $spy;
    }

    /**
     * Assert that a method was called on a mock.
     */
    protected function assertMethodCalled($mock, string $method, ...$arguments)
    {
        $mock->shouldHaveReceived($method)->with(...$arguments);
    }

    /**
     * Assert that a method was not called on a mock.
     */
    protected function assertMethodNotCalled($mock, string $method)
    {
        $mock->shouldNotHaveReceived($method);
    }
}
