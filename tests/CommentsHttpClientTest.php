<?php

declare(strict_types=1);

namespace App\Tests;

use App\CommentsHttpClient;
use App\Exceptions\ApiException;
use App\Exceptions\CommentsClientException;
use App\Exceptions\InvalidResponseException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(CommentsHttpClient::class)]
final class CommentsHttpClientTest extends TestCase
{
    private const BASE_URL = 'http://example.com';

    private ClientInterface&MockObject $guzzle;
    private CommentsHttpClient $client;

    protected function setUp(): void
    {
        $this->guzzle = $this->createMock(ClientInterface::class);
        $this->client = new CommentsHttpClient(self::BASE_URL, $this->guzzle);
    }

    public function testRequestReturnsResponse(): void
    {
        $expected = new Response(200, [], '{"ok":true}');

        $this->guzzle
            ->expects(self::once())
            ->method('request')
            ->with(
                'GET',
                '/comments',
                self::callback(
                    static fn(array $options): bool => ($options['headers']['Accept'] ?? null) === 'application/json'
                ),
            )
            ->willReturn($expected);

        self::assertSame($expected, $this->client->request('GET', '/comments'));
    }

    public function testRequestAddsAcceptHeader(): void
    {
        $this->guzzle
            ->expects(self::once())
            ->method('request')
            ->with(
                'POST',
                '/comment',
                self::callback(
                    static fn(array $options): bool => ($options['headers']['Accept'] ?? null) === 'application/json'
                ),
            )
            ->willReturn(new Response(201, [], '{}'));

        $this->client->request('POST', '/comment');
    }

    public function testRequestWrapsGuzzleException(): void
    {
        $this->guzzle
            ->expects(self::once())
            ->method('request')
            ->willThrowException(new ConnectException(
                'Connection refused',
                new Request('GET', '/comments'),
            ));

        $this->expectException(CommentsClientException::class);
        $this->expectExceptionMessage('HTTP request failed');

        $this->client->request('GET', '/comments');
    }

    /**
     * @param array<string, mixed> $expected
     */
    #[DataProvider('successfulResponses')]
    public function testDecodeReturnsParsedBody(int $status, string $body, array $expected): void
    {
        self::assertSame($expected, $this->client->decode(new Response($status, [], $body)));
    }

    #[DataProvider('errorStatuses')]
    public function testDecodeThrowsApiExceptionOnErrorStatus(int $status): void
    {
        $response = new Response($status, [], 'error body');

        try {
            $this->client->decode($response);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame($status, $e->getCode());
            self::assertSame($status, $e->getResponse()->getStatusCode());
        }
    }

    #[DataProvider('invalidJsonBodies')]
    public function testDecodeThrowsInvalidResponseOnInvalidJson(string $body, string $expectedMessagePart): void
    {
        $response = new Response(200, [], $body);

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage($expectedMessagePart);

        $this->client->decode($response);
    }

    /**
     * @return array<string, array{int, string, array<string, mixed>}>
     */
    public static function successfulResponses(): array
    {
        return [
            'json object'    => [200, '{"id":1,"name":"Comment Name","text":"Comment Text"}', ['id' => 1, 'name' => 'Comment Name', 'text' => 'Comment Text']],
            'json list'      => [200, '[{"id":1},{"id":2}]', [['id' => 1], ['id' => 2]]],
            'empty body'     => [204, '', []],
            'created 201'    => [201, '{"id":123}', ['id' => 123]],
            'empty object'   => [200, '{}', []],
            'empty list'     => [200, '[]', []],
        ];
    }

    /**
     * @return array<string, array{int}>
     */
    public static function errorStatuses(): array
    {
        return [
            '400' => [400],
            '401' => [401],
            '403' => [403],
            '404' => [404],
            '422' => [422],
            '500' => [500],
            '502' => [502],
            '503' => [503],
        ];
    }


    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidJsonBodies(): array
    {
        return [
            'broken json'    => ['not-json', 'Response is not valid JSON'],
            'scalar string'  => ['"string"', 'Expected JSON object or array'],
            'null'           => ['null', 'Expected JSON object or array'],
            'number'         => ['123', 'Expected JSON object or array'],
            'boolean'        => ['true', 'Expected JSON object or array'],
        ];
    }
}
