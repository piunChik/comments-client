<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\ApiException;
use App\Exceptions\CommentsClientException;
use App\Exceptions\InvalidResponseException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

final class CommentsHttpClient
{
    private readonly string $baseUrl;
    private readonly ClientInterface $guzzleClient;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(?string $baseUrl = null, ?ClientInterface $guzzleClient = null)
    {
        $baseUrl ??= (string) getenv('comments_client_base_url');

        $this->baseUrl = BaseUrlNormalizer::normalizeBaseUrl($baseUrl);
        $this->guzzleClient = $guzzleClient ?? new Client([
            'base_uri'    => $this->baseUrl,
            'http_errors' => false,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     * 
     * @throws CommentsClientException
     */
    public function request(string $method, string $path, array $options = []): ResponseInterface
    {
        $options['headers']['Accept'] = 'application/json';

        try {
            return $this->guzzleClient->request($method, $path, $options);
        } catch (GuzzleException $e) {
            throw new CommentsClientException('HTTP request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array<string, mixed>
     * 
     * @throws ApiException
     * @throws InvalidResponseException
     */
    public function decode(ResponseInterface $response): array
    {
        $status = $response->getStatusCode();

        if ($status < 200 || $status >= 300) {
            throw new ApiException($response);
        }

        $body = (string) $response->getBody();
        if ($body === '') {
            return [];
        }

        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidResponseException('Response is not valid JSON: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($data)) {
            throw new InvalidResponseException('Expected JSON object or array');
        }

        return $data;
    }
}
