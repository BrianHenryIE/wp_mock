<?php

namespace WP_Mock\Tests;

use Exception;
use Mockery;
use Patchwork\CallRerouting\Handle;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use WP_Mock\Functions\Handler;
use WP_Mock\Tools\Constraints\ExpectationsMet;

/**
 * Base test case for all tests.
 */
class WP_MockTestCase extends TestCase
{
    /**
     * Sets up the tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Mockery::close();
    }

    /**
     * Runs after tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    /**
     * Asserts that the test conditions have been met.
     *
     * @return void
     * @throws ExpectationFailedException|Exception
     */
    protected function assertConditionsMet(): void
    {
        $this->assertThat(null, new ExpectationsMet());
    }

    /**
     * PHPUnit's `TestCase::isInIsolation()` method was available in PHPUnit 9 but removed in PHPUnit 10. The instance
     * variable still exists as a private property. The method was marked `@internal`.
     *
     * @see https://github.com/sebastianbergmann/phpunit/blob/945d0b7f346a084ce5549e95289962972c4272e5/src/Framework/TestCase.php#L1422-L1428
     * @see https://github.com/sebastianbergmann/phpunit/blob/f2e26f52f80ef77832e359205f216eeac00e320c/src/Framework/TestCase.php#L137
     */
    public function isInIsolation(): bool
    {
        if(method_exists(TestCase::class, 'isInisolation')) {
            return parent::isInisolation();
        }

        $property = new ReflectionProperty(TestCase::class, 'inIsolation');
        if(!version_compare(PHP_VERSION, '8.5', '>=')){
            $property->setAccessible(true);
        }

        return $property->getValue($this);
    }
}
