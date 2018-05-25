<?php declare(strict_types=1);

namespace DaveRandom\Jom\Exceptions;

abstract class Exception extends \Exception
{
    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        if ($message instanceof \Throwable) {
            $previous = $message;
            $message = $previous->getMessage();
            $code = $previous->getCode();
        } else if ($code instanceof \Throwable) {
            $previous = $code;
            $code = $previous->getCode();
        }

        parent::__construct((string)$message, (int)$code, $previous);
    }
}
