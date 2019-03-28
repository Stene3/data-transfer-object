<?php

namespace Spatie\DataTransferObject\Tests;

use Spatie\DataTransferObject\DataTransferObjectError;
use Spatie\DataTransferObject\Tests\TestClasses\TestDataTransferObject;
use Spatie\DataTransferObject\Tests\TestClasses\ImmutablePropertyDto;

class ImmutableTest extends TestCase
{
    /** @test */
    public function immutable_values_cannot_be_overwritten()
    {
        $dto = (new TestDataTransferObject([
            'testProperty' => 1,
        ]))->immutable();

        $this->assertEquals(1, $dto->testProperty);

        $this->expectException(DataTransferObjectError::class);

        $dto->testProperty = 2;
    }

    /** @test */
    public function mutable_values_can_be_overwritten()
    {
        $dto = (new TestDataTransferObject([
            'testProperty' => 1,
        ]))->mutable();

        $this->assertEquals(1, $dto->testProperty);

        $dto->testProperty = 2;

        $this->assertEquals(2, $dto->testProperty);
    }

    /** @test */
    public function method_calls_are_proxied()
    {
        $dto = (new TestDataTransferObject([
            'testProperty' => 1,
        ]))->immutable();

        $this->assertEquals(['testProperty' => 1], $dto->toArray());
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
    public function property_is_immutable()
    {
        $dto = new ImmutablePropertyDto([
            'testProperty' => 'astring',
        ]);

        $this->assertEquals('astring', $dto->testProperty);

        $this->expectException(DataTransferObjectError::class);

        $dto->testProperty = 'otherstring';
    }
}
