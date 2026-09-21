<?php

namespace WP_Mock\Tests\Unit\WP_Mock;

use Closure;
use Generator;
use Exception;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use stdClass;
use WP_Mock\Functions;
use WP_Mock\Hook;
use WP_Mock\Tests\Mocks\SampleClass;
use WP_Mock\Tests\Mocks\SampleSubClass;
use WP_Mock\Traits\AccessInaccessibleClassMembersTrait;

/**
 * @covers \WP_Mock\Hook
 */
final class HookTest extends TestCase
{
    use AccessInaccessibleClassMembersTrait;

    /**
     * This test case does not extend {@see \WP_Mock\Tools\TestCase}, so {@see \WP_Mock::setUp()} and
     * {@see \WP_Mock::tearDown()} never run. Clear the shared Type map here so entries written by
     * {@see Functions::type()} cannot leak between tests, even when an assertion fails.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Hook::$objects = [];
    }

    protected function tearDown(): void
    {
        Hook::$objects = [];

        parent::tearDown();
    }

    /**
     * @covers \WP_Mock\Hook::safe_offset()
     * @dataProvider providerSafeOffset
     *
     * @param mixed $value
     * @param string $expected
     * @return void
     * @throws ReflectionException|Exception
     */
    public function testCanParseSafeOffSet($value, string $expected): void
    {
        $instance = $this->getMockForAbstractClass(Hook::class, [], '', false);
        $method = $this->getInaccessibleMethod($instance, 'safe_offset');

        $this->assertSame($expected, $method->invokeArgs($instance, [$value]));
    }

    /** @see testCanParseSafeOffset */
    public function providerSafeOffset(): Generator
    {
        $callbackInstance = new class () {
            public function callback(): bool
            {
                return true;
            }
        };

        $closureInstance = function () {
        };

        $objectInstance = new stdClass();

        yield 'null' => [null, 'null'];
        yield 'closure (object)' => [$closureInstance, '__CLOSURE__'];
        yield 'closure (representation)' => ['<Closure>', '__CLOSURE__'];
        yield 'closure (class name)' => [Closure::class, '__CLOSURE__'];
        yield 'closure (Mockery matcher)' => [Mockery::type(Closure::class), '__CLOSURE__'];
        yield 'scalar (string)' => ['test-string', 'test-string'];
        yield 'scalar (integer)' => [123, '123'];
        yield 'scalar (float)' => [1.23, '1.23'];
        yield 'scalar (true)' => [true, '1'];
        yield 'scalar (false)' => [false, ''];
        yield 'object' => [$objectInstance, spl_object_hash($objectInstance)];
        yield 'array (callback)' => [[$callbackInstance, 'callback'], spl_object_hash($callbackInstance).'callback'];
        yield 'type matcher (class)' => [Mockery::type(SampleClass::class), (string) Mockery::type(SampleClass::class)];
    }

    /**
     * Each Type matcher is created inline and freed as soon as `safe_offset()` returns, which is how
     * `WP_Mock::expectHookAdded()` uses them. Keys for different classes must still differ, and a real
     * instance must resolve to the same key as its (already freed) matcher.
     *
     * @covers \WP_Mock\Hook::safe_offset()
     * @covers \WP_Mock\Functions::type()
     *
     * @return void
     * @throws ReflectionException|Exception
     */
    public function testTypeSafeOffsetIsStableAcrossMultipleTypeCalls(): void
    {
        $hookInstance = $this->getMockForAbstractClass(Hook::class, [], '', false);
        $safeOffsetMethod = $this->getInaccessibleMethod($hookInstance, 'safe_offset');

        $keyCallbackClass = $safeOffsetMethod->invokeArgs($hookInstance, [[Functions::type(SampleClass::class), 'action']]);
        $keyCallbackSubClass = $safeOffsetMethod->invokeArgs($hookInstance, [[Functions::type(SampleSubClass::class), 'action']]);

        $this->assertNotSame($keyCallbackClass, $keyCallbackSubClass);

        $keyInstance = $safeOffsetMethod->invokeArgs($hookInstance, [[new SampleClass(), 'action']]);
        $keySubInstance = $safeOffsetMethod->invokeArgs($hookInstance, [[new SampleSubClass(), 'action']]);

        $this->assertSame($keyCallbackClass, $keyInstance);
        $this->assertSame($keyCallbackSubClass, $keySubInstance);
    }
}
