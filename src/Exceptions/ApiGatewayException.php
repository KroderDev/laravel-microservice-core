<?php

namespace Kroderdev\LaravelMicroserviceCore\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiGatewayException extends HttpException
{
    protected array $data;

    public function __construct(int $statusCode, array $data = [], string $message = '', ?\Throwable $previous = null, array $headers = [], int $code = 0)
    {
        parent::__construct($statusCode, $message ?: 'API Gateway Error', $previous, $headers, $code);
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
