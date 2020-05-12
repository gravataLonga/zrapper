<?php

declare(strict_types=1);

namespace Tests;

use Gravatalonga\Container\Aware;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

/**
 * @internal
 * @coversDefaultClass
 */
final class ContainerTest extends TestCase
{
    public function testCanBindDependency()
    {
        $rand = mt_rand(0, 10);
        $class = $this->newClass($rand);
        $container = new Aware();

        $container->factory('random', static function () use ($rand) {
            return $rand;
        });
        $container->factory('random1', [$class, 'get']);

        self::assertEquals($rand, $container->get('random'));
        self::assertEquals($rand, $container->get('random1'));
    }

    public function testCanCheckIfEntryExistOnContainer()
    {
        $container = new Aware(['db' => 'my-db']);
        self::assertTrue($container->has('db'));
        self::assertFalse($container->has('key-not-exists'));
    }

    public function testCanCreateContainer()
    {
        $container = new Aware();
        self::assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testCanGetContainerFromFactory()
    {
        $rand = mt_rand(0, 1000);
        $container = new Aware();
        $container->factory('random', static function () use ($rand) {
            return $rand;
        });

        $container->factory('random1', static function (ContainerInterface $container) {
            return $container->get('random');
        });

        self::assertEquals($container->get('random'), $container->get('random1'));
    }

    public function testCanGetDifferentValueFromContainer()
    {
        $container = new Aware();
        $container->factory('random', static function () {
            return mt_rand(0, 1000);
        });
        self::assertNotEquals($container->get('random'), $container->get('random'));
    }

    public function testCanGetInstanceFromShareBinding()
    {
        $container = new Aware();
        $container->share('random', static function () {
            return mt_rand(1, 1000);
        });
        self::assertGreaterThan(0, $container->get('random'));
    }

    public function testCanGetInstanceOfContainer()
    {
        $container = new Aware();
        $container::setInstance($container);

        self::assertInstanceOf(ContainerInterface::class, Aware::getInstance());
        self::assertSame($container, Aware::getInstance());
    }

    public function testCanGetValueFromContainer()
    {
        $container = new Aware(['config' => true]);
        self::assertTrue($container->get('config'));
    }

    public function testCanOverrideShareEntryEvenItWasResolveFirst()
    {
        $container = new Aware();
        $container->share('entry', static function () {
            return 'hello';
        });
        $entryOne = $container->get('entry');

        $container->share('entry', static function () {
            return 'world';
        });

        self::assertTrue($container->has('entry'));
        self::assertEquals('hello', $entryOne);
        self::assertEquals('world', $container->get('entry'));
    }

    public function testCanSetDirectValueRatherThanCallback()
    {
        $container = new Aware();

        $container->set('hello', 'world');
        $container->set('abc', 123);
        $container->set('object', new stdClass());

        self::assertSame('world', $container->get('hello'));
        self::assertSame(123, $container->get('abc'));
        self::assertInstanceOf(stdClass::class, $container->get('object'));
    }

    public function testCanShareSameBindingAndCanCheckIfExists()
    {
        $container = new Aware();
        $container->share('random', static function () {
            return new stdClass();
        });
        self::assertTrue($container->has('random'));
    }

    public function testIHaveSetMethodAliasForFactory()
    {
        $container = new Aware();
        $container->set('random', static function () {
            return new stdClass();
        });
        self::assertNotSame($container->get('random'), $container->get('random'));
    }

    public function testMustThrowExceptionWhenTryGetEntryDontExists()
    {
        $container = new Aware();
        $this->expectException(NotFoundExceptionInterface::class);
        $container->get('entry');
    }

    public function testShareCanOverrideSameEntry()
    {
        $container = new Aware();
        $container->share('entry', static function () {
            return 'hello';
        });

        $container->share('entry', static function () {
            return 'world';
        });

        self::assertTrue($container->has('entry'));
        self::assertEquals('world', $container->get('entry'));
    }

    public function testWhenResolveFromShareBindingItReturnSameValue()
    {
        $container = new Aware();
        $container->share('random', static function () {
            return mt_rand(1, 1000);
        });
        self::assertEquals($container->get('random'), $container->get('random'));
    }

    private function newClass($rand)
    {
        return new class($rand) {
            /**
             * @var int
             */
            private static $rand;

            public function __construct(int $rand)
            {
                self::$rand = $rand;
            }

            public function get(): int
            {
                return self::$rand;
            }
        };
    }
}
