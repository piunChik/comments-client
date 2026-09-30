<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\InvalidResponseException;
use InvalidArgumentException;

final class CommentsController
{
    use ValidationTrait;

    private readonly CommentsHttpClient $client;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(?CommentsHttpClient $client = null)
    {
        $this->client = $client ?? new CommentsHttpClient();
    }

    /**
     * @return CommentDto[]
     */
    public function getComments(): array
    {
        $data = $this->client->decode($this->client->request('GET', '/comments'));

        if (!array_is_list($data)) {
            throw new InvalidResponseException('Expected a list of comments');
        }

        return $this->mapComments($data);
    }

    public function addComment(string $name, string $text): CommentDto
    {
        $this->assertNonEmpty($name, 'name');
        $this->assertNonEmpty($text, 'text');

        $response = $this->client->request('POST', '/comment', [
            'json' => ['name' => $name, 'text' => $text],
        ]);

        return CommentDto::fromArray($this->client->decode($response));
    }

    public function updateComment(int $id, ?string $name = null, ?string $text = null): CommentDto
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Comment id must be positive');
        }

        $body = [];

        if ($name !== null) {
            $this->assertNonEmpty($name, 'name');
            $body['name'] = $name;
        }

        if ($text !== null) {
            $this->assertNonEmpty($text, 'text');
            $body['text'] = $text;
        }

        if ($body === []) {
            throw new InvalidArgumentException('At least one of name or text must be provided');
        }

        $response = $this->client->request('PUT', '/comment/' . $id, [
            'json' => $body,
        ]);

        return CommentDto::fromArray($this->client->decode($response));
    }

    /**
     * @param array<int, mixed> $data
     * @return CommentDto[]
     * @throws InvalidResponseException
     */
    private function mapComments(array $data): array
    {
        $comments = [];

        foreach ($data as $item) {
            if (!is_array($item)) {
                throw new InvalidResponseException('Each comment must be an object');
            }

            try {
                $comments[] = CommentDto::fromArray($item);
            } catch (InvalidArgumentException $e) {
                throw new InvalidResponseException('Malformed comment in response: ' . $e->getMessage(), 0, $e);
            }
        }

        return $comments;
    }
}
