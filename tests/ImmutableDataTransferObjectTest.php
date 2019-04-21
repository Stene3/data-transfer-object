<?php

namespace Larapie\DataTransferObject\Tests;

use Larapie\DataTransferObject\Traits\MakeImmutable;
use Larapie\DataTransferObject\Tests\TestClasses\NestedChild;
use Larapie\DataTransferObject\Tests\TestClasses\ImmutableDto;
use Larapie\DataTransferObject\Exceptions\ImmutableDtoException;
use Larapie\DataTransferObject\Tests\TestClasses\ImmutableNestedDto;
use Larapie\DataTransferObject\Tests\TestClasses\ImmutablePropertyDto;
use Larapie\DataTransferObject\Exceptions\ImmutablePropertyDtoException;
use Larapie\DataTransferObject\Tests\TestClasses\TestDataTransferObject;

class ImmutableDataTransferObjectTest extends TestCase
{
    /** @test */
    public function immutable_values_cannot_be_overwritten()
    {
        $dto = new class(['testProperty' => 1]) extends TestDataTransferObject {
            use MakeImmutable;
        };

        $this->assertEquals(1, $dto->testProperty);

        $this->expectException(ImmutableDtoException::class);

        $dto->testProperty = 2;
    }

    /** @test */
    public function mutable_values_can_be_overwritten()
    {
        $dto = (new TestDataTransferObject([
            'testProperty' => 1,
        ]));

        $this->assertEquals(1, $dto->testProperty);

        $dto->testProperty = 2;

        $this->assertEquals(2, $dto->testProperty);
    }

    /** @test */
    public function mutable_is_default()
    {
        $dto = new TestDataTransferObject([
            'testProperty' => 1,
        ]);

        $this->assertEquals(1, $dto->testProperty);

        $dto->testProperty = 2;

        $this->assertEquals(2, $dto->testProperty);
    }

    /** @test */
    public function immutable_interface_makes_dto_immutable()
    {
        $dto = new ImmutableDto([
            'name' => 'immutable',
        ]);

        $this->assertEquals('immutable', $dto->name);

        $this->expectException(ImmutableDtoException::class);

        $dto->name = 'mutable';

        $this->assertEquals('immutable', $dto->name);
    }

    /** @test */
    public function property_is_immutable()
    {
        $dto = new ImmutablePropertyDto([
            'immutableProperty' => 'immutable',
            'mutableProperty' => 'immutable',
        ]);

        $this->assertEquals('immutable', $dto->immutableProperty);
        $this->assertEquals('immutable', $dto->mutableProperty);

        $this->expectException(ImmutablePropertyDtoException::class);

        $dto->immutableProperty = 'mutable';
        $dto->mutableProperty = 'mutable';

        $this->assertEquals('immutable', $dto->immutableProperty);
        $this->assertEquals('mutable', $dto->mutableProperty);
    }

    /** @test */
    public function immutable_applies_to_nested_dtos()
    {
        $data = [
            'name' => 'parent',
            'children' => [
                new NestedChild(['name' => 'arthur']),
                new NestedChild(['name' => 'brendt']),
            ],
        ];

        $dto = new ImmutableNestedDto($data);
        $array = $dto->toArray();
        $this->assertEquals($dto->name, 'parent');
        $this->assertEquals($dto->children[0]->name, 'arthur');
        $this->assertEquals($dto->children[1]->name, 'brendt');

        $this->expectException(ImmutableDtoException::class);
        $child = $dto->children[1];
        $child->name = 'another';

        $this->assertEquals($dto->children[1]->name, 'brendt');
    }
}
