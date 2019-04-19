<?php

declare(strict_types=1);

namespace Larapie\DataTransferObject\Tests\TestClasses;

use Larapie\DataTransferObject\DataTransferObject;
use Larapie\DataTransferObject\Annotations\Optional;
use Symfony\Component\Validator\Constraints as Assert;

class ValidateablePropertyDto extends DataTransferObject
{
    /**
     * @var string
     * @Optional()
     * @Assert\NotBlank()
     * @Assert\Length(min = 5, max = 10)
     */
    public $name;
}
