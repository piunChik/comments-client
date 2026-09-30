<?php

declare(strict_types=1);

namespace App\Tests;

use App\CommentDto;
use App\CommentsController;
use App\CommentsHttpClient;
use App\Exceptions\ApiException;
use App\Exceptions\InvalidResponseException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Builder\InvocationMocker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(CommentsController::class)]
final class CommentsControllerTest extends TestCase
{
    private const BASE_URL = 'http://example.com';

    private ClientInterface&MockObject $guzzle;
    private CommentsController $controller;

    protected function setUp(): void
    {
        $this->guzzle = $this->createMock(ClientInterface::class);

        $httpClient = new CommentsHttpClient(self::BASE_URL, $this->guzzle);
        $this->controller = new CommentsController($httpClient);
    }

    #[DataProvider('comments')]
    public function testGetCommentsReturnsListOfDtos(array $payload): void
    {
        $this->expectRequest('GET', '/comments')->willReturn($this->jsonResponse(200, $payload));

        $comments = $this->controller->getComments();

        self::assertCount(count($payload), $comments);
        self::assertContainsOnlyInstancesOf(CommentDto::class, $comments);

        foreach ($payload as $i => $expectedComment) {
            self::assertSame($expectedComment['id'], $comments[$i]->getId());
            self::assertSame($expectedComment['name'], $comments[$i]->getName());
            self::assertSame($expectedComment['text'], $comments[$i]->getText());
        }
    }

    public function testGetCommentsReturnsEmptyArray(): void
    {
        $this->expectRequest('GET', '/comments')->willReturn($this->jsonResponse(200, []));

        self::assertSame([], $this->controller->getComments());
    }

    public function testGetCommentsThrowsOnNonListPayload(): void
    {
        $this->expectRequest('GET', '/comments')->willReturn($this->jsonResponse(200, ['id' => 1, 'name' => 'Comment Name', 'text' => 'Comment Text']));

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Expected a list of comments');

        $this->controller->getComments();
    }

    public function testGetCommentsPropagatesApiException(): void
    {
        $this->expectRequest('GET', '/comments')->willReturn(new Response(500, [], 'Server error'));

        $this->expectException(ApiException::class);

        $this->controller->getComments();
    }

    public function testAddCommentSendsPostWithJsonBody(): void
    {
        $payload = ['id' => 123, 'name' => 'Comment Name', 'text' => 'Comment Text'];

        $this->expectRequest('POST', '/comment')
            ->willReturnCallback(function (string $method, string $uri, array $options) use ($payload): ResponseInterface {
                self::assertSame(['name' => 'Comment Name', 'text' => 'Comment Text'], $options['json']);

                return $this->jsonResponse(201, $payload);
            });

        $comment = $this->controller->addComment('Comment Name', 'Comment Text');

        self::assertSame(123, $comment->getId());
        self::assertSame('Comment Name', $comment->getName());
        self::assertSame('Comment Text', $comment->getText());
    }

    public function testAddCommentRejectsEmptyName(): void
    {
        $this->guzzle->expects(self::never())->method('request');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field name must not be empty');

        $this->controller->addComment('   ', 'Text');
    }

    public function testAddCommentRejectsEmptyText(): void
    {
        $this->guzzle->expects(self::never())->method('request');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field text must not be empty');

        $this->controller->addComment('Name', '');
    }

    public function testUpdateCommentSendsPutWithOnlyName(): void
    {
        $payload = ['id' => 3, 'name' => 'New Name', 'text' => 'Text'];

        $this->expectRequest('PUT', '/comment/3')
            ->willReturnCallback(function (string $method, string $uri, array $options) use ($payload): ResponseInterface {
                self::assertSame(['name' => 'New Name'], $options['json']);
                self::assertArrayNotHasKey('text', $options['json']);

                return $this->jsonResponse(200, $payload);
            });

        $comment = $this->controller->updateComment(3, name: 'New Name');

        self::assertSame(3, $comment->getId());
        self::assertSame('New Name', $comment->getName());
    }

    public function testUpdateCommentSendsPutWithOnlyText(): void
    {
        $payload = ['id' => 7, 'name' => 'Alice', 'text' => 'Updated'];

        $this->expectRequest('PUT', '/comment/7')
            ->willReturnCallback(function (string $method, string $uri, array $options) use ($payload): ResponseInterface {
                self::assertSame(['text' => 'Updated'], $options['json']);

                return $this->jsonResponse(200, $payload);
            });

        $comment = $this->controller->updateComment(7, text: 'Updated');

        self::assertSame('Updated', $comment->getText());
    }

    public function testUpdateCommentSendsBothFields(): void
    {
        $payload = ['id' => 5, 'name' => 'Carol', 'text' => 'New'];

        $this->expectRequest('PUT', '/comment/5')
            ->willReturnCallback(function (string $method, string $uri, array $options) use ($payload): ResponseInterface {
                self::assertSame(['name' => 'Carol', 'text' => 'New'], $options['json']);

                return $this->jsonResponse(200, $payload);
            });

        $this->controller->updateComment(5, name: 'Carol', text: 'New');
    }

    public function testUpdateCommentRejectsNonPositiveId(): void
    {
        $this->guzzle->expects(self::never())->method('request');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Comment id must be positive');

        $this->controller->updateComment(0, name: 'Alice');
    }

    public function testUpdateCommentRejectsWhenNoFieldsProvided(): void
    {
        $this->guzzle->expects(self::never())->method('request');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one of name or text must be provided');

        $this->controller->updateComment(1);
    }

    public function testUpdateCommentRejectsEmptyProvidedField(): void
    {
        $this->guzzle->expects(self::never())->method('request');

        $this->expectException(InvalidArgumentException::class);

        $this->controller->updateComment(1, text: '   ');
    }

    private function expectRequest(string $method, string $path): InvocationMocker
    {
        return $this->guzzle->expects(self::once())->method('request')->with(
            $method,
            $path,
            self::callback(
                static fn(array $options): bool => ($options['headers']['Accept'] ?? null) === 'application/json'
            ),
        );
    }

    /**
     * @param array<mixed> $payload
     */
    private function jsonResponse(int $status, array $payload): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, array>
     */
    public static function comments(): array
    {
        return [
            'list' => [[
                ['id' => 1, 'name' => 'First Comment', 'text' => 'First'],
                ['id' => 2, 'name' => 'Second Comment', 'text' => 'Second'],
            ]],
            'single' => [[
                ['id' => 1, 'name' => 'Name', 'text' => 'Text']
            ]],
        ];
    }
}
