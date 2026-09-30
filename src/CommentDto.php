<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;

final class CommentDto
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $text,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['id']) || !is_int($data['id'])) {
            throw new InvalidArgumentException('ID must be an integer');
        }
        if (!isset($data['name']) || !is_string($data['name'])) {
            throw new InvalidArgumentException('Name must be a string');
        }
        if (!isset($data['text']) || !is_string($data['text'])) {
            throw new InvalidArgumentException('Text must be a string');
        }

        return new self($data['id'], $data['name'], $data['text']);
    }

    /**
     * @return array{id: int, name: string, text: string}
     */
    public function toArray(): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'text' => $this->text,
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
