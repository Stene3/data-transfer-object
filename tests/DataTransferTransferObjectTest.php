<?php

declare(strict_types=1);

namespace Larapie\DataTransferObject\Tests;

use Larapie\DataTransferObject\Contracts\AdditionalProperties;
use Larapie\DataTransferObject\Contracts\WithAdditionalProperties;
use Larapie\DataTransferObject\DataTransferObject;
use Larapie\DataTransferObject\Exceptions\TypeDoesNotExistException;
use Larapie\DataTransferObject\Traits\MakeImmutable;
use Larapie\DataTransferObject\Annotations\Immutable;
use Larapie\DataTransferObject\Tests\TestClasses\DummyClass;
use Larapie\DataTransferObject\Tests\TestClasses\EmptyChild;
use Larapie\DataTransferObject\Tests\TestClasses\OtherClass;
use Larapie\DataTransferObject\Exceptions\ValidatorException;
use Larapie\DataTransferObject\Tests\TestClasses\NestedChild;
use Larapie\DataTransferObject\Tests\TestClasses\NestedParent;
use Larapie\DataTransferObject\Exceptions\ImmutableDtoException;
use Larapie\DataTransferObject\Tests\TestClasses\NestedParentOfMany;
use Larapie\DataTransferObject\Violations\PropertyRequiredViolation;
use Larapie\DataTransferObject\Exceptions\PropertyNotFoundDtoException;
use Larapie\DataTransferObject\Violations\InvalidPropertyTypeViolation;
use Larapie\DataTransferObject\Exceptions\ImmutablePropertyDtoException;
use Larapie\DataTransferObject\Exceptions\UnknownPropertiesDtoException;
use Larapie\DataTransferObject\Exceptions\PropertyAlreadyExistsException;

class DataTransferObjectTest extends TestCase
{
    /** @test */
    public function only_the_type_hinted_type_may_be_passed()
    {
        $dto = new class(['foo' => 'value']) extends DataTransferObject
        {
            /** @var string */
            public $foo;
        };

        $dto->validate();

        $this->markTestSucceeded();

        $dto = new class(['foo' => false]) extends DataTransferObject
        {
            /** @var string */
            public $foo;
        };

        $this->assertThrows(ValidatorException::class,
            function () use ($dto) {
                $dto->validate();
            },
            function (ValidatorException $exception) {
                $this->assertTrue($exception->propertyViolationExists('foo', InvalidPropertyTypeViolation::class));
            });
    }

    /** @test */
    public function union_types_are_supported()
    {
        new class(['foo' => 'value']) extends DataTransferObject
        {
            /** @var string|bool */
            public $foo;
        };

        new class(['foo' => false]) extends DataTransferObject
        {
            /** @var string|bool */
            public $foo;
        };

        $this->markTestSucceeded();
    }

    /** @test */
    public function invalid_property_type_throws_error()
    {
        $this->expectException(TypeDoesNotExistException::class);
        new class(['foo' => 'value']) extends DataTransferObject
        {
            /** @var string|InvalidType */
            public $foo;
        };
    }

    /** @test */
    public function nullable_types_are_supported()
    {
        new class(['foo' => null]) extends DataTransferObject
        {
            /** @var string|null */
            public $foo;
        };

        $this->markTestSucceeded();
    }

    /** @test */
    public function default_values_are_supported()
    {
        $valueObject = new class(['bar' => true]) extends DataTransferObject
        {
            /** @var string */
            public $foo = 'abc';

            /** @var bool */
            public $bar;
        };

        $this->assertEquals(['foo' => 'abc', 'bar' => true], $valueObject->all());
    }

    /** @test */
    public function null_is_allowed_only_if_explicitly_specified()
    {
        $dto = new class(['foo' => null]) extends DataTransferObject
        {
            /** @var string */
            public $foo;
        };

        $this->assertThrows(ValidatorException::class,
            function () use ($dto) {
                $dto->validate();
            },
            function (ValidatorException $exception) {
                $this->assertTrue($exception->propertyViolationExists('foo', InvalidPropertyTypeViolation::class));
            });
    }

    /** @test */
    public function setting_unknown_property_throws_error()
    {
        $dto = new class([]) extends DataTransferObject
        {
        };

        $this->expectException(PropertyNotFoundDtoException::class);

        $dto->property = 'blabla';
    }

    /** @test */
    public function unknown_properties_throw_an_error()
    {
        $this->expectException(UnknownPropertiesDtoException::class);

        new class(['bar' => null]) extends DataTransferObject
        {
        };
    }

    /** @test */
    public function unknown_properties_show_a_comprehensive_error_message()
    {
        try {
            new class(['foo' => null, 'bar' => null]) extends DataTransferObject
            {
            };
        } catch (UnknownPropertiesDtoException $error) {
            $this->assertContains('foo', $error->getMessage());
            $this->assertContains('bar', $error->getMessage());
        }
    }

    /** @test */
    public function only_returns_filtered_properties()
    {
        $valueObject = new class(['foo' => 1, 'bar' => 2]) extends DataTransferObject
        {
            /** @var int */
            public $foo;

            /** @var int */
            public $bar;
        };

        $this->assertEquals(['foo' => 1], $valueObject->only('foo')->toArray());
    }

    /** @test */
    public function except_returns_filtered_properties()
    {
        $valueObject = new class(['foo' => 1, 'bar' => 2]) extends DataTransferObject
        {
            /** @var int */
            public $foo;

            /** @var int */
            public $bar;
        };

        $this->assertEquals(['foo' => 1], $valueObject->except('bar')->toArray());
    }

    /** @test */
    public function all_returns_all_properties()
    {
        $valueObject = new class(['foo' => 1, 'bar' => 2]) extends DataTransferObject
        {
            /** @var int */
            public $foo;

            /** @var int */
            public $bar;
        };

        $this->assertEquals(['foo' => 1, 'bar' => 2], $valueObject->all());
    }

    /** @test */
    public function mixed_is_supported()
    {
        new class(['foo' => 'abc']) extends DataTransferObject
        {
            /** @var mixed */
            public $foo;
        };

        new class(['foo' => 1]) extends DataTransferObject
        {
            /** @var mixed */
            public $foo;
        };

        $this->markTestSucceeded();
    }

    /** @test */
    public function float_is_supported()
    {
        new class(['foo' => 5.1]) extends DataTransferObject
        {
            /** @var float */
            public $foo;
        };

        $this->markTestSucceeded();
    }

    /** @test */
    public function classes_are_supported()
    {
        new class(['foo' => new DummyClass()]) extends DataTransferObject
        {
            /** @var DummyClass */
            public $foo;
        };

        $this->markTestSucceeded();

        $dto = new class(['foo' => new class()
        {
        },
        ]) extends DataTransferObject
        {
            /** @var DummyClass */
            public $foo;
        };

        $this->assertThrows(ValidatorException::class,
            function () use ($dto) {
                $dto->validate();
            },
            function (ValidatorException $exception) {
                $this->assertTrue($exception->propertyViolationExists('foo', InvalidPropertyTypeViolation::class));
            });
    }

    /** @test */
    public function generic_collections_are_supported()
    {
        new class(['foo' => [new DummyClass()]]) extends DataTransferObject
        {
            /** @var DummyClass[] */
            public $foo;
        };

        $this->markTestSucceeded();

        $dto = new class(['foo' => [new OtherClass()]]) extends DataTransferObject
        {
            /** @var \Larapie\DataTransferObject\Tests\TestClasses\DummyClass[] */
            public $foo;
        };

        $this->assertThrows(ValidatorException::class,
            function () use ($dto) {
                $dto->validate();
            },
            function (ValidatorException $exception) {
                $this->assertTrue($exception->propertyViolationExists('foo', InvalidPropertyTypeViolation::class));
            });
    }

    /** @test */
    public function an_exception_is_thrown_for_a_generic_collection_of_null()
    {
        $dto = new class(['foo' => [null]]) extends DataTransferObject
        {
            /** @var string[] */
            public $foo;
        };

        $this->assertThrows(ValidatorException::class,
            function () use ($dto) {
                $dto->validate();
            },
            function (ValidatorException $exception) {
                $this->assertTrue($exception->propertyViolationExists('foo', InvalidPropertyTypeViolation::class));
            });
    }

    /** @test */
    public function an_exception_is_thrown_when_property_was_not_initialised()
    {
        $dto = new class([]) extends DataTransferObject
        {
            /** @var string */
            public $foo;
        };

        $this->assertThrows(ValidatorException::class,
            function () use ($dto) {
                $dto->validate();
            },
            function (ValidatorException $exception) {
                $this->assertTrue($exception->propertyViolationExists('foo', PropertyRequiredViolation::class));
            });
    }

    /** @test */
    public function empty_type_declaration_allows_everything()
    {
        new class(['foo' => new DummyClass()]) extends DataTransferObject
        {
            public $foo;
        };

        new class(['foo' => null]) extends DataTransferObject
        {
            public $foo;
        };

        new class(['foo' => null]) extends DataTransferObject
        {
            /** This is a variable without type declaration */
            public $foo;
        };

        new class(['foo' => 1]) extends DataTransferObject
        {
            public $foo;
        };

        $this->markTestSucceeded();
    }

    /** @test */
    public function nested_dtos_are_automatically_cast_from_arrays_to_objects()
    {
        $data = [
            'name' => 'parent',
            'child' => [
                'name' => 'child',
            ],
        ];

        $object = new NestedParent($data);
        $object->validate();

        $this->assertInstanceOf(NestedChild::class, $object->child);
        $this->assertEquals('parent', $object->name);
        $this->assertEquals('child', $object->child->name);
    }

    /** @test */
    public function nested_dtos_are_recursive_cast_from_object_to_array_when_to_array()
    {
        $data = [
            'name' => 'parent',
            'child' => [
                'name' => 'child',
            ],
        ];

        $object = new NestedParent($data);

        $this->assertEquals(['name' => 'child'], $object->toArray()['child']);

        $valueObject = new class(['childs' => [new NestedChild(['name' => 'child'])]]) extends DataTransferObject
        {
            /** @var NestedChild[] */
            public $childs;
        };

        $this->assertEquals(['name' => 'child'], $valueObject->toArray()['childs'][0]);
    }

    /** @test */
    public function nested_array_dtos_are_automatically_cast_to_arrays_of_dtos()
    {
        $data = [
            'name' => 'parent',
            'children' => [
                ['name' => 'child'],
            ],
        ];

        $object = new NestedParentOfMany($data);

        $this->assertNotEmpty($object->children);
        $this->assertInstanceOf(NestedChild::class, $object->children[0]);
        $this->assertEquals('parent', $object->name);
        $this->assertEquals('child', $object->children[0]->name);
    }

    /** @test */
    public function nested_array_dtos_are_recursive_cast_to_arrays_of_dtos()
    {
        $data = [
            'children' => [
                [
                    'name' => 'child',
                    'children' => [
                        ['name' => 'grandchild'],
                    ],
                ],
            ],
        ];

        $object = new class($data) extends DataTransferObject
        {
            /** @var NestedParentOfMany[] */
            public $children;
        };

        $this->assertEquals(['name' => 'grandchild'], $object->toArray()['children'][0]['children'][0]);
    }

    /** @test */
    public function dto_attribute_is_overrided_by_with_parameter()
    {
        $data = [
            'name' => 'test',
        ];

        $object = new class($data) extends DataTransferObject
        {
            /** @var string $name */
            public $name;
        };

        $this->assertEquals(['name' => 'test'], $object->toArray());

        $object->override('name', 'test2');
        $this->assertEquals(['name' => 'test2'], $object->toArray());

        $this->expectException(PropertyAlreadyExistsException::class);
        $object->with('name', 'test2');
    }

    /** @test */
    public function immutable_dto_attribute_overriding_throws_exception()
    {
        $data = [
            'name' => 'test',
        ];
        $object = new class($data) extends DataTransferObject
        {
            /**
             * @Immutable
             * @var string
             */
            public $name;
        };

        $this->assertEquals(['name' => 'test'], $object->toArray());

        $this->expectException(ImmutablePropertyDtoException::class);
        $object->override('name', 'test2');
    }

    /** @test */
    public function immutable_dto_overriding_throws_exception()
    {
        $data = [
            'name' => 'test',
        ];
        $object = new class($data) extends DataTransferObject
        {
            use MakeImmutable;

            /**
             * @var string
             */
            public $name;
        };

        $this->assertEquals(['name' => 'test'], $object->toArray());

        $this->expectException(ImmutableDtoException::class);
        $object->override('name', 'test2');
    }

    /** @test */
    public function nested_array_dtos_cannot_cast_with_null()
    {
        $dto = new NestedParentOfMany([
            'children' => null,
            'name' => 'parent',
        ]);

        $this->assertThrows(ValidatorException::class,
            function () use ($dto) {
                $dto->validate();
            },
            function (ValidatorException $exception) {
                $this->assertTrue($exception->propertyViolationExists('children', InvalidPropertyTypeViolation::class));
            });
    }

    /** @test */
    public function nested_array_dtos_can_be_nullable()
    {
        $object = new class(['children' => null]) extends DataTransferObject
        {
            /** @var NestedChild[]|null */
            public $children;
        };

        $this->assertNull($object->children);
    }

    /** @test */
    public function empty_dto_objects_can_be_cast_using_arrays()
    {
        $object = new class(['child' => []]) extends DataTransferObject
        {
            /** @var EmptyChild */
            public $child;
        };

        $this->assertInstanceOf(EmptyChild::class, $object->child);
    }

    /** @test */
    public function a_mutable_array_property_can_be_canged()
    {
        $dto = new class([]) extends DataTransferObject
        {
            /** @var array */
            public $array = [];
        };

        $dto->array[] = 'abc';

        $this->assertEquals($dto->array, ['abc']);
    }

    /** @test */
    public function additional_properties_throws_exception()
    {
        $this->expectException(UnknownPropertiesDtoException::class);
        $dto = new class(["name" => "foo"]) extends DataTransferObject
        {
        };

        $this->assertEmpty($dto->toArray());
    }

    /** @test */
    public function additional_property_dto_does_not_throw_exception()
    {
        $dto = new class(["name" => "foo"]) extends DataTransferObject implements AdditionalProperties
        {
        };

        $this->assertEmpty($dto->toArray());
    }

    /** @test */
    public function with_additional_property_dto_works()
    {
        $dto = new class(["name" => "foo"]) extends DataTransferObject implements WithAdditionalProperties
        {
        };

        $this->assertEquals("foo", $dto->name);

        $this->assertEquals($dto->toArray(), ["name" => "foo"]);
    }
}
