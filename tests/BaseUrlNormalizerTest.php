<?php

declare(strict_types=1);

namespace App\Tests;

use App\BaseUrlNormalizer;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(BaseUrlNormalizer::class)]
final class BaseUrlNormalizerTest extends TestCase
{
    #[DataProvider('validUrls')]
    public function testNormalizesValidUrls(string $input, string $expected): void
    {
        self::assertSame($expected, BaseUrlNormalizer::normalizeBaseUrl($input));
    }

    #[DataProvider('invalidUrls')]
    public function testRejectsInvalidUrls(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        BaseUrlNormalizer::normalizeBaseUrl($input);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function validUrls(): array
    {
        return [
            'http'           => ['http://example.com', 'http://example.com'],
            'https'          => ['https://example.com', 'https://example.com'],
            'with port'      => ['http://example.com:8080', 'http://example.com:8080'],
            'with path'      => ['https://example.com/comments/1', 'https://example.com/comments/1'],
            'trailing slash' => ['http://example.com/', 'http://example.com'],
            'whitespace'     => ['  http://example.com  ', 'http://example.com'],
            'localhost'      => ['http://localhost', 'http://localhost'],
            'localhost port' => ['http://localhost:8080', 'http://localhost:8080'],
            'subdomain'      => ['https://api.staging.example.com', 'https://api.staging.example.com'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidUrls(): array
    {
        return [
            'empty'       => [''],
            'whitespace'  => ['   '],
            'no scheme'   => ['example.com'],
            'ftp scheme'  => ['ftp://example.com'],
            'file scheme' => ['file:///etc/passwd'],
            'mailto'      => ['mailto:test@example.com'],
            'no host'     => ['http://'],
            'garbage'     => ['not-a-url'],
        ];
    }
}
