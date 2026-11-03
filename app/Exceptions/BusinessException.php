<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Нарушение бизнес-правила (лимит, запрещённое действие).
 * Обрабатывается в ApiExceptionHandler: ответ 409 (или другой код) с понятным сообщением.
 */
class BusinessException extends RuntimeException
{
    public function __construct(string $message, private int $status = 409)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
