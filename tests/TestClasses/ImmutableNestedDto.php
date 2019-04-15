<?php

declare(strict_types=1);

namespace Larapie\DataTransferObject\Tests\TestClasses;

use Larapie\DataTransferObject\DataTransferObject;
use Larapie\DataTransferObject\Traits\Immutable;

class ImmutableNestedDto extends DataTransferObject
{
    use Immutable;

    /** @var string */
    public $name;

    /** @var \Larapie\DataTransferObject\Tests\TestClasses\NestedChild[]|array $child */
    public $children;
}
