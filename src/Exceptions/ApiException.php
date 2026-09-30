<?php

declare(strict_types=1);

namespace App\Exceptions;

use Psr\Http\Message\ResponseInterface;

final class ApiException extends CommentsClientException
{
    public function __construct(
        private readonly ResponseInterface $response,
        string $message = '',
    ) {
        $status = $response->getStatusCode();
        $reason = $response->getReasonPhrase();
        $message = $message !== '' ? $message : "API returned HTTP $status $reason";

        parent::__construct($message, $status);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
