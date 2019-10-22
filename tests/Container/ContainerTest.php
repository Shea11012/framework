<?php namespace Container;

use Psr\Container\ContainerExceptionInterface;
use Shea\Container\Container;
use Shea\Container\EntryNotFoundException;
use Shea\Contracts\Container\BindingResolutionException;

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

    public function testAliasesWithArrayOfParameters()
    {
        $container = new Container();
        $container->bind('foo',function ($app,$config) {
            return $config;
        });

        $container->alias('foo','baz');

        $this->assertEquals([1,2,3],$container->make('baz',[1,2,3]));
    }

    public function testBingingCanBeOverridden()
    {
        $container = new Container();
        $container['foo'] = 'bar';
        $container['foo'] = 'baz';
        $this->assertSame('baz',$container['foo']);
    }

    public function test_binding_an_instance_returns_the_instance()
    {
        $container = new Container();
        $bound = new \stdClass();
        $resolved = $container->instance('foo',$bound);
        $this->assertSame($bound,$resolved);
    }

    public function test_resolution_of_default_parameters()
    {
        $container = new Container();
        $instance = $container->make(ContainerDefaultValueStub::class);
        $this->assertInstanceOf(ContainerConcreteStub::class,$instance->stub);
        $this->assertSame('taylor',$instance->default);
    }

    public function test_unset_remove_bound_instances()
    {
        $container = new Container();
        $container->instance('object',new \stdClass());
        unset($container['object']);
        $this->assertFalse($container->bound('object'));
    }

    public function test_bound_instance_and_alias_check_via_array_access()
    {
        $container = new Container();
        $container->instance('object',new \stdClass());
        $container->alias('object','alias');
        $this->assertTrue(isset($container['object']));
        $this->assertTrue(isset($container['alias']));
    }

    public function test_internal_class_with_default_parameters()
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Unresolvable dependency resolving [Parameter #0 [ <required> $first ]] in class Container\ContainerMixedPrimitiveStub');
        $container = new Container();
        $container->make(ContainerMixedPrimitiveStub::class,[]);
    }

    public function test_binding_resolution_exception_message()
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Target [Container\IContainerContractStub] is not instantiable');

        $container = new Container();
        $container->make(IContainerContractStub::class,[]);
    }

    public function test_binding_resolution_exception_message_when_class_does_not_exist()
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Target class [Foo\Bar\Baz\DummyClass] does not exist');

        $container = new Container();
        $container->build('Foo\Bar\Baz\DummyClass');
    }

    public function test_get_alias()
    {
        $container = new Container();
        $container->alias('ConcreteStub','foo');
        $this->assertEquals($container->getAlias('foo'),'ConcreteStub');
    }

    public function test_it_throws_exception_when_abstract_is_same_as_alias()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('[name] is aliased to itself');
        $container = new Container();
        $container->alias('name','name');
    }

    public function test_container_get_factory()
    {
        $container = new Container();
        $container->bind('name',function () {
            return 'Taylor';
        });

        $factory = $container->factory('name');
        $this->assertEquals($container->make('name'),$factory());
    }

    public function test_resolving_with_array_of_parameters()
    {
        $container = new Container();
        $instance = $container->make(ContainerDefaultValueStub::class,['default' => 'adam']);
        $this->assertSame('adam',$instance->default);

        $instance = $container->make(ContainerDefaultValueStub::class);
        $this->assertSame('taylor',$instance->default);

        $container->bind('foo',function ($app,$config) {
            return $config;
        });

        $this->assertEquals([1,2,3],$container->make('foo',[1,2,3]));
    }

    public function test_resolving_with_using_an_interface()
    {
        $container = new Container();
        $container->bind(IContainerContractStub::class,ContainerInjectVariableStubWithInterfaceImplementation::class);
        $instance = $container->make(IContainerContractStub::class,['something' => 'laurence']);
        $this->assertSame('laurence',$instance->something);
    }

    public function test_nested_parameter_override()
    {
        $container = new Container;
        $container->bind('foo',function (Container $app,$config) {
            return $app->make('bar',['name'=>'Taylor']);
        });

        $container->bind('bar',function (Container $app,$config) {
           return $config;
        });

        $this->assertEquals(['name' => 'Taylor'],$container->make('foo',['something']));
    }

    public function test_singleton_bindings_not_respected_with_make_parameters()
    {
        $container = new Container();
        $container->singleton('foo',function (Container $app,$config) {
            return $config;
        });

        $this->assertEquals(['name' => 'taylor'],$container->make('foo',['name' => 'taylor']));
        $this->assertEquals(['name' => 'abigail'],$container->make('foo',['name' => 'abigail']));
    }

    public function test_container_knows_entry()
    {
        $container = new Container();
        $container->bind(IContainerContractStub::class,ContainerImplementationStub::class);
        $this->assertTrue($container->has(IContainerContractStub::class));
    }

    public function test_container_can_bind_any_word()
    {
        $container = new Container();
        $container->bind('Taylor',\stdClass::class);
        $this->assertInstanceOf(\stdClass::class,$container->get('Taylor'));
    }

    public function test_container_can_dynamically_set_service()
    {
        $container = new Container();
        $this->assertFalse(isset($container['name']));
        $container['name'] = 'Taylor';
        $this->assertTrue(isset($container['name']));
        $this->assertSame('Taylor',$container['name']);
    }

    public function test_unknown_entry_throws_exception()
    {
        $this->expectException(EntryNotFoundException::class);
        $container = new Container();
        $container->get('Taylor');
    }

    public function test_bound_entries_throws_container_exception_when_not_resolvable()
    {
        $this->expectException(ContainerExceptionInterface::class);
        $container = new Container();
        $container->bind('Taylor',IContainerContractStub::class);
        $container->get('Taylor');
    }

    public function test_container_can_resolve_classes()
    {
        $container = new Container();
        $class = $container->get(ContainerConcreteStub::class);
        $this->assertInstanceOf(ContainerConcreteStub::class,$class);
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

class ContainerDefaultValueStub
{
    public $stub;
    public $default;

    public function __construct(ContainerConcreteStub $stub,$default = 'taylor')
    {
        $this->stub = $stub;
        $this->default = $default;
    }
}

class ContainerMixedPrimitiveStub
{
    public $first;
    public $last;
    public $stub;

    public function __construct($first,ContainerConcreteStub $stub,$last)
    {
        $this->stub = $stub;
        $this->last = $last;
        $this->first = $first;
    }
}

class ContainerInjectVariableStubWithInterfaceImplementation implements IContainerContractStub
{
    public $something;

    public function __construct(ContainerConcreteStub $concrete, $something)
    {
        $this->something = $something;
    }
}