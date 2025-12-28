<?php

namespace App\Exceptions;

/**
 * Исключение для ошибок API TrendAgent
 */
class TrendApiException extends TrendParserException
{
    protected ?string $apiUrl = null;
    protected ?int $httpStatusCode = null;
    protected ?string $responseBody = null;
    
    public function __construct(
        string $message = "",
        ?string $apiUrl = null,
        ?int $httpStatusCode = null,
        ?string $responseBody = null,
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->apiUrl = $apiUrl;
        $this->httpStatusCode = $httpStatusCode;
        $this->responseBody = $responseBody;
    }
    
    public function getApiUrl(): ?string
    {
        return $this->apiUrl;
    }
    
    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }
    
    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }
    
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'api_url' => $this->apiUrl,
            'http_status_code' => $this->httpStatusCode,
            'response_body' => $this->responseBody ? substr($this->responseBody, 0, 500) : null, // Ограничиваем размер
        ]);
    }
}

