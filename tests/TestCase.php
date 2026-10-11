<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Spotlight registers its commands into a static array on every app boot; across a long test run
        // that list (inlined in each page) would grow and distort page-size assertions. A real request boots once.
        \LivewireUI\Spotlight\Spotlight::$commands = [];

        parent::setUp();
    }
}
