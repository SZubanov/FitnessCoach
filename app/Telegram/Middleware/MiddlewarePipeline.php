<?php

namespace App\Telegram\Middleware;

use Closure;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * Middleware Pipeline
 *
 * Implements the Chain of Responsibility pattern for executing
 * a series of middleware before reaching the final handler.
 *
 * Inspired by Laravel's Pipeline implementation.
 */
class MiddlewarePipeline
{
    /**
     * The array of middleware to execute
     *
     * @var array<\App\Telegram\Middleware\Contracts\TelegramMiddleware>
     */
    private array $middleware;

    /**
     * Create a new middleware pipeline
     *
     * @param array<\App\Telegram\Middleware\Contracts\TelegramMiddleware> $middleware
     */
    public function __construct(array $middleware = [])
    {
        $this->middleware = $middleware;
    }

    /**
     * Execute the pipeline
     *
     * Runs through all middleware in order, then executes the destination
     *
     * @param TelegraphChat $chat The chat context to pass through
     * @param Closure $destination The final handler to execute
     * @return mixed The result from the destination or null if stopped by middleware
     */
    public function through(TelegraphChat $chat, Closure $destination): mixed
    {
        // Build the pipeline by wrapping each middleware around the next
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            $this->carry(),
            $destination
        );

        // Execute the pipeline
        return $pipeline($chat);
    }

    /**
     * Get a Closure that represents a slice of the pipeline
     *
     * @return Closure
     */
    private function carry(): Closure
    {
        return function ($next, $middleware) {
            return function ($passable) use ($next, $middleware) {
                return $middleware->handle($passable, $next);
            };
        };
    }

    /**
     * Add middleware to the pipeline
     *
     * @param \App\Telegram\Middleware\Contracts\TelegramMiddleware $middleware
     * @return self
     */
    public function pipe($middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Get all middleware in the pipeline
     *
     * @return array<\App\Telegram\Middleware\Contracts\TelegramMiddleware>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }
}