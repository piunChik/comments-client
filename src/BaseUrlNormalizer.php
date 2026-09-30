<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;

final class BaseUrlNormalizer
{
    /**
     * @throws InvalidArgumentException
     */
    public static function normalizeBaseUrl(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException("Base URL must not be empty");
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException("Base URL is not a valid URL: $value");
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException(
                "Base URL must use http or https scheme, got: " . var_export($scheme, true)
            );
        }

        $host = parse_url($value, PHP_URL_HOST);
        if ($host === null || $host === '') {
            throw new InvalidArgumentException("Base URL has no host: $value");
        }

        return rtrim($value, '/');
    }
}
