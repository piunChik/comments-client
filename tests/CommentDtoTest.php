<?php

declare(strict_types=1);

namespace App\Tests;

use App\CommentDto;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CommentDto::class)]
final class CommentDtoTest extends TestCase
{
    private const DEFAULT_ID = 123;
    private const DEFAULT_NAME = 'Comment Name';
    private const DEFAULT_TEXT = 'Comment Text';

    public function testFromArrayCreatesComment(): void
    {
        $comment = CommentDto::fromArray([
            'id' => self::DEFAULT_ID,
            'name' => self::DEFAULT_NAME,
            'text' => self::DEFAULT_TEXT,
        ]);

        self::assertSame(self::DEFAULT_ID, $comment->getId());
        self::assertSame(self::DEFAULT_NAME, $comment->getName());
        self::assertSame(self::DEFAULT_TEXT, $comment->getText());
    }

    public function testFromArrayRejectsMissingId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CommentDto::fromArray(['name' => self::DEFAULT_NAME, 'text' => self::DEFAULT_TEXT]);
    }

    public function testFromArrayRejectsNonIntId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CommentDto::fromArray([
            'id' => (string) self::DEFAULT_ID,
            'name' => self::DEFAULT_NAME,
            'text' => self::DEFAULT_TEXT,
        ]);
    }

    public function testFromArrayRejectsMissingName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CommentDto::fromArray(['id' => self::DEFAULT_ID, 'text' => self::DEFAULT_TEXT]);
    }

    public function testFromArrayRejectsMissingText(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CommentDto::fromArray(['id' => self::DEFAULT_ID, 'name' => self::DEFAULT_NAME]);
    }

    public function testFromArrayRejectsNonStringName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CommentDto::fromArray([
            'id' => self::DEFAULT_ID,
            'name' => 123,
            'text' => self::DEFAULT_TEXT,
        ]);
    }

    public function testFromArrayRejectsNonStringText(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CommentDto::fromArray([
            'id' => self::DEFAULT_ID,
            'name' => self::DEFAULT_NAME,
            'text' => 456,
        ]);
    }

    public function testToArrayReturnsAllFields(): void
    {
        $comment = new CommentDto(self::DEFAULT_ID, self::DEFAULT_NAME, self::DEFAULT_TEXT);

        self::assertSame(
            [
                'id' => self::DEFAULT_ID,
                'name' => self::DEFAULT_NAME,
                'text' => self::DEFAULT_TEXT,
            ],
            $comment->toArray(),
        );
    }
}
