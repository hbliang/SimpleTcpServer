<?php


namespace Hbliang\SimpleTcpServer\Exceptions;


use Throwable;

class BadRequestException extends \Exception
{
    public function __construct(string $message = "Bad Request", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}