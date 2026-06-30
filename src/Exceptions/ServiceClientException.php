<?php

namespace Kroderdev\LaravelMicroserviceCore\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ServiceClientException extends HttpException
{
    protected array $responseData;

    public function __construct(int $statusCode, array $responseData = [], string $message = '', \Throwable $previous = null, array $headers = [], int $code = 0)
    {
        $this->responseData = $responseData;

        parent::__construct($statusCode, $message ?: 'Service request failed', $previous, $headers, $code);
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }

    public function getStatusCode(): int
    {
        return parent::getStatusCode();
    }
}
