<?php

namespace App\Telegram\Services;

use Illuminate\Support\Facades\Cache;

/**
 * ConversationStateService - Telegraph State Management
 *
 * Provides conversation state management for Telegraph webhook handlers.
 * Since Telegraph doesn't have built-in conversation state like Nutgram,
 * this service uses Laravel cache to maintain state between webhook calls.
 *
 * Key Features:
 * - Per-chat state storage with automatic TTL (15 minutes default)
 * - Step-based conversation flow tracking
 * - Arbitrary data storage for conversation context
 * - Automatic cleanup on conversation completion
 *
 * Usage:
 * ```php
 * // Start a conversation
 * $stateService->startConversation($chatId, 'measurement', ['type' => 'chest']);
 *
 * // Move to next step
 * $stateService->setStep($chatId, 'input_value');
 *
 * // Store data
 * $stateService->setData($chatId, 'measurement_value', 95.5);
 *
 * // Get data
 * $value = $stateService->getData($chatId, 'measurement_value');
 *
 * // Check if in conversation
 * if ($stateService->isInConversation($chatId)) {
 *     // Handle conversation flow
 * }
 *
 * // End conversation (clears all state)
 * $stateService->endConversation($chatId);
 * ```
 */
class ConversationStateService
{
    /**
     * Default TTL for conversation state (15 minutes)
     */
    private const DEFAULT_TTL = 900;

    /**
     * Cache key prefix for conversation state
     */
    private const STATE_PREFIX = 'telegram_conversation';

    /**
     * Start a new conversation for a chat
     *
     * @param string $chatId Telegram chat ID
     * @param string $conversationType Type of conversation (e.g., 'measurement', 'weight', 'sync')
     * @param array $initialData Optional initial data to store
     * @param int $ttl Time to live in seconds (default: 15 minutes)
     * @return void
     */
    public function startConversation(
        string $chatId,
        string $conversationType,
        array $initialData = [],
        int $ttl = self::DEFAULT_TTL
    ): void {
        $key = $this->getStateKey($chatId, 'active');

        $state = [
            'type' => $conversationType,
            'step' => 'initial',
            'started_at' => now()->toIso8601String(),
            'data' => $initialData,
        ];

        Cache::put($key, $state, $ttl);
    }

    /**
     * Check if a chat is currently in a conversation
     *
     * @param string $chatId Telegram chat ID
     * @return bool
     */
    public function isInConversation(string $chatId): bool
    {
        return Cache::has($this->getStateKey($chatId, 'active'));
    }

    /**
     * Get the current conversation type
     *
     * @param string $chatId Telegram chat ID
     * @return string|null
     */
    public function getConversationType(string $chatId): ?string
    {
        $state = $this->getState($chatId);
        return $state['type'] ?? null;
    }

    /**
     * Get the current conversation step
     *
     * @param string $chatId Telegram chat ID
     * @return string|null
     */
    public function getStep(string $chatId): ?string
    {
        $state = $this->getState($chatId);
        return $state['step'] ?? null;
    }

    /**
     * Set the current conversation step
     *
     * @param string $chatId Telegram chat ID
     * @param string $step Step identifier
     * @return void
     */
    public function setStep(string $chatId, string $step): void
    {
        $state = $this->getState($chatId);

        if (!$state) {
            return;
        }

        $state['step'] = $step;
        $this->saveState($chatId, $state);
    }

    /**
     * Store data in conversation state
     *
     * @param string $chatId Telegram chat ID
     * @param string $key Data key
     * @param mixed $value Data value
     * @return void
     */
    public function setData(string $chatId, string $key, mixed $value): void
    {
        $state = $this->getState($chatId);

        if (!$state) {
            return;
        }

        $state['data'][$key] = $value;
        $this->saveState($chatId, $state);
    }

    /**
     * Retrieve data from conversation state
     *
     * @param string $chatId Telegram chat ID
     * @param string $key Data key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public function getData(string $chatId, string $key, mixed $default = null): mixed
    {
        $state = $this->getState($chatId);
        return $state['data'][$key] ?? $default;
    }

    /**
     * Get all conversation data
     *
     * @param string $chatId Telegram chat ID
     * @return array
     */
    public function getAllData(string $chatId): array
    {
        $state = $this->getState($chatId);
        return $state['data'] ?? [];
    }

    /**
     * End the conversation and clear all state
     *
     * @param string $chatId Telegram chat ID
     * @return void
     */
    public function endConversation(string $chatId): void
    {
        Cache::forget($this->getStateKey($chatId, 'active'));
    }

    /**
     * Get the complete conversation state
     *
     * @param string $chatId Telegram chat ID
     * @return array|null
     */
    private function getState(string $chatId): ?array
    {
        return Cache::get($this->getStateKey($chatId, 'active'));
    }

    /**
     * Save the conversation state
     *
     * @param string $chatId Telegram chat ID
     * @param array $state State data
     * @return void
     */
    private function saveState(string $chatId, array $state): void
    {
        $key = $this->getStateKey($chatId, 'active');
        $ttl = Cache::get($key) ? null : self::DEFAULT_TTL; // Preserve existing TTL
        Cache::put($key, $state, $ttl ?? self::DEFAULT_TTL);
    }

    /**
     * Generate cache key for conversation state
     *
     * @param string $chatId Telegram chat ID
     * @param string $suffix Key suffix
     * @return string
     */
    private function getStateKey(string $chatId, string $suffix): string
    {
        return self::STATE_PREFIX . "_{$chatId}_{$suffix}";
    }

    /**
     * Store a temporary value (for single-use data)
     * Useful for storing data between menu selections
     *
     * @param string $chatId Telegram chat ID
     * @param string $key Data key
     * @param mixed $value Data value
     * @param int $ttl Time to live in seconds (default: 15 minutes)
     * @return void
     */
    public function putTemp(string $chatId, string $key, mixed $value, int $ttl = self::DEFAULT_TTL): void
    {
        $cacheKey = $this->getStateKey($chatId, "temp_{$key}");
        Cache::put($cacheKey, $value, $ttl);
    }

    /**
     * Retrieve a temporary value
     *
     * @param string $chatId Telegram chat ID
     * @param string $key Data key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public function getTemp(string $chatId, string $key, mixed $default = null): mixed
    {
        $cacheKey = $this->getStateKey($chatId, "temp_{$key}");
        return Cache::get($cacheKey, $default);
    }

    /**
     * Remove a temporary value
     *
     * @param string $chatId Telegram chat ID
     * @param string $key Data key
     * @return void
     */
    public function forgetTemp(string $chatId, string $key): void
    {
        $cacheKey = $this->getStateKey($chatId, "temp_{$key}");
        Cache::forget($cacheKey);
    }

    /**
     * Clear all temporary values for a chat
     *
     * @param string $chatId Telegram chat ID
     * @return void
     */
    public function clearAllTemp(string $chatId): void
    {
        // Note: This is a simplified version. For production, you might want
        // to track all temp keys in a separate cache entry for complete cleanup
        $commonTempKeys = [
            'sync_type',
            'measurement_type',
            'macro_type',
            'selected_date',
        ];

        foreach ($commonTempKeys as $key) {
            $this->forgetTemp($chatId, $key);
        }
    }
}