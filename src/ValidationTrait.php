<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;

trait ValidationTrait
{
    /**
     * @throws InvalidArgumentException
     */
    private function assertNonEmpty(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("Field $field must not be empty");
        }
    }
}
