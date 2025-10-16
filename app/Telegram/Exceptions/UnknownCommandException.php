<?php

namespace App\Telegram\Exceptions;

/**
 * Unknown Command Exception
 *
 * Thrown when a command is requested but no handler is registered for it
 */
class UnknownCommandException extends AbstractTelegramBotException
{
    /**
     * @var string
     */
    protected $message = 'Unknown command';

    /**
     * Create exception with custom message
     *
     * @param string $command The unknown command name
     * @return self
     */
    public static function forCommand(string $command): self
    {
        $exception = new self("Unknown command: /{$command}");
        return $exception;
    }
}