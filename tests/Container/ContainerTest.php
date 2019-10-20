<?php namespace Container;

use Shea\Container\Container;

class ContainerTest extends \Codeception\Test\Unit
{

    protected function _before()
    {
    }

    protected function _after()
    {
        Container::setInstance(null);
    }

    public function testContainerSingleton()
    {
        $container = Container::setInstance(new Container());

        $this->assertSame($container,Container::getInstance());

        Container::setInstance(null);

        $container2 = Container::getInstance();

        $this->assertInstanceOf(Container::class,$container2);
        $this->assertNotSame($container,$container2);
    }

    public function testClosureResolution()
    {
        $container = new Container();
        $container->bind('name',function () {
            return 'Taylor';
        });

        $this->assertSame('Taylor',$container->make('name'));
    }

    public function testSharedClosureResolution()
    {
        $container = new Container();
        $class = new \stdClass();

        $container->singleton('class',function () use ($class) {
            return $class;
        });

        $this->assertSame($class,$container->make('class'));
    }

    public function testAutoConcreteResolution()
    {
        $container = new Container();
        $this->assertInstanceOf(ContainerConcreteStub::class,$container->make(ContainerConcreteStub::class));
    }

    public function testSharedConcreteResolution()
    {
        $container = new Container();
        $container->singleton(ContainerConcreteStub::class);
        $var1 = $container->make(ContainerConcreteStub::class);
        $var2 = $container->make(ContainerConcreteStub::class);

        $this->assertSame($var1,$var2);
    }

    public function testAbstractToConcreteResolution()
    {
        $container = new Container();
        $container->bind(IContainerContractStub::class,ContainerImplementationStub::class);

        $class = $container->make(ContainerDependentStub::class);
        $this->assertInstanceOf(ContainerImplementationStub::class,$class->impl);
    }

    public function testNestedDependencyResolution()
    {
        $container = new Container();
        $container->bind(IContainerContractStub::class,ContainerImplementationStub::class);
        $class = $container->make(ContainerNestedDependentStub::class);

        $this->assertInstanceOf(ContainerDependentStub::class,$class->inner);
        $this->assertInstanceOf(ContainerImplementationStub::class,$class->inner->impl);
    }

    public function testContainerIsPassedToResolvers()
    {
        $container = new Container();
        $container->bind('something',function ($c) {
            return $c;
        });
        $c = $container->make('something');

        $this->assertSame($c,$container);
    }

    public function testArrayAccess()
    {
        $container = new Container();
        $container['something'] = function () {
            return 'foo';
        };

        $this->assertTrue(isset($container['something']));
        $this->assertSame('foo',$container['something']);
        unset($container['something']);
        $this->assertFalse(isset($container['something']));
    }

    public function testAliases()
    {
        $container = new Container();
        $container['foo'] = 'bar';
        $container->alias('foo','baz');
        $container->alias('baz','bat');
        $this->assertSame('bar',$container->make('foo'));
        $this->assertSame('bar',$container->make('baz'));
        $this->assertSame('bar',$container->make('bat'));
    }
}

class ContainerConcreteStub {}

interface IContainerContractStub {}

class ContainerImplementationStub implements IContainerContractStub {}

class ContainerDependentStub
{
    public $impl;

    public function __construct(IContainerContractStub $impl)
    {
        $this->impl = $impl;
    }
}

class ContainerNestedDependentStub
{
    public $inner;

    public function __construct(ContainerDependentStub $inner)
    {
        $this->inner = $inner;
    }
}