<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Reference: testing_spec.md §7.2 — "Testing SHALL use a dedicated
 * testing database isolated from development data." (enforced via
 * phpunit.xml setting DB_CONNECTION=mysql_testing).
 */
abstract class TestCase extends BaseTestCase
{
    //
}
