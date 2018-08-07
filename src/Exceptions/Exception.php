<?php declare(strict_types=1);

namespace DaveRandom\Jom\Exceptions;

abstract class Exception extends \Exception
{
    /**
     * @param string $format
     * @param mixed ...$args
     * @return static
     */
    public static function withMessage(string $format, ...$args): self
    {
        return new static(\vsprintf($format, $args));
    }

    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        if ($message instanceof \Throwable) {
            parent::__construct($message->getMessage(), $message->getCode(), $message);
            return;
        }

        if ($code instanceof \Throwable) {
            parent::__construct((string)$message, $code->getCode(), $code);
            return;
        }

        parent::__construct((string)$message, (int)$code, $previous);
    }
}
