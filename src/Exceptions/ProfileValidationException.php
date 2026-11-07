<?php

declare(strict_types=1);

namespace App\Exceptions;

use InvalidArgumentException;

class ProfileValidationException extends InvalidArgumentException
{
    public function __construct(private array $errors)
    {
        parent::__construct(implode(' ', $errors));
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
