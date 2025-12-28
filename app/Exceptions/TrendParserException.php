<?php

namespace App\Exceptions;

use Exception;

/**
 * Базовое исключение для ошибок парсера TrendAgent
 */
class TrendParserException extends Exception
{
    protected array $context = [];
    
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }
    
    public function getContext(): array
    {
        return $this->context;
    }
    
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }
    
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}

