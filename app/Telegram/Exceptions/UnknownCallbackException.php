<?php

namespace App\Telegram\Exceptions;

/**
 * Unknown Callback Exception
 *
 * Thrown when a callback action is requested but no handler is registered for it
 */
class UnknownCallbackException extends AbstractTelegramBotException
{
    /**
     * @var string
     */
    protected $message = 'Unknown callback action';

    /**
     * Create exception for unknown callback action
     *
     * @param string $callbackName The unknown callback action name
     * @return self
     */
    public static function forCallback(string $callbackName): self
    {
        $exception = new self("Unknown callback action: {$callbackName}");
        return $exception;
    }
}