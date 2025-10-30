# Telegram Bot Developer Guide

**Version:** 2.0
**Last Updated:** October 30, 2025
**Status:** Phase 5 Complete (Refactored Architecture)

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Getting Started](#getting-started)
3. [How to Add a New Command](#how-to-add-a-new-command)
4. [How to Add a New Callback](#how-to-add-a-new-callback)
5. [How to Add a New Conversation](#how-to-add-a-new-conversation)
6. [How to Add Middleware](#how-to-add-middleware)
7. [Testing Guidelines](#testing-guidelines)
8. [Common Patterns & Best Practices](#common-patterns--best-practices)
9. [Troubleshooting](#troubleshooting)
10. [Migration Notes](#migration-notes)

---

## Architecture Overview

### High-Level Architecture

The FitnessCoach Telegram bot uses a **clean, pattern-based architecture** built on the Telegraph framework. The bot was refactored from a 1,406-line monolithic handler to a modular system with **85% code reduction** (211 lines).

```
┌─────────────────────────────────────────────────────────────┐
│              FitnessCoachWebhookHandler                     │
│              (Main Entry Point - 211 lines)                 │
└────────────┬────────────────────────┬─────────────────┬─────┘
             │                        │                 │
    ┌────────▼─────────┐   ┌──────────▼────────┐   ┌──▼─────────────┐
    │ TelegramCommand  │   │  CallbackRegistry │   │ Conversation   │
    │    Registry      │   │   (Phase 3)       │   │   Manager      │
    │   (Phase 2)      │   └──────────┬────────┘   │   (Phase 4)    │
    └────────┬─────────┘              │            └──┬─────────────┘
             │                        │               │
    ┌────────▼─────────┐   ┌──────────▼────────┐   ┌──▼─────────────┐
    │   5 Command      │   │  25 Callback      │   │ 4 Conversation │
    │   Handlers       │   │  Handlers         │   │   Handlers     │
    └──────────────────┘   └───────────────────┘   └────────────────┘
```

### Design Patterns Used

1. **Command Pattern**: Commands are encapsulated in handler classes
2. **Strategy Pattern**: Conversation types have dedicated handlers
3. **Registry Pattern**: O(1) lookup for commands and callbacks
4. **Factory Pattern**: Centralized keyboard creation
5. **Builder Pattern**: Fluent API for messages and keyboards
6. **Chain of Responsibility**: Middleware pipeline
7. **Data Transfer Objects**: Immutable context and result objects

### Directory Structure

```
app/Telegram/
├── Callbacks/                  # Callback handlers (Phase 3)
│   ├── Contracts/
│   │   └── CallbackHandler.php
│   ├── CallbackRegistry.php
│   ├── MainMenu/              # 6 handlers
│   ├── Account/               # 4 handlers
│   ├── FatSecret/             # 4 handlers
│   ├── Sync/                  # 4 handlers
│   ├── Measurements/          # 2 handlers
│   ├── Weight/                # 2 handlers
│   └── Macros/                # 5 handlers
│
├── Commands/                   # Command handlers (Phase 2)
│   ├── Contracts/
│   │   └── TelegramCommandHandler.php
│   ├── StartCommandHandler.php
│   ├── HelpCommandHandler.php
│   ├── AccountCommandHandler.php
│   ├── FatSecretCommandHandler.php
│   └── SyncCommandHandler.php
│
├── Conversations/              # Conversation handlers (Phase 4)
│   ├── Contracts/
│   │   ├── ConversationHandler.php
│   │   ├── ConversationContext.php
│   │   ├── ConversationResult.php
│   │   └── ConversationStatus.php
│   ├── ConversationManager.php
│   ├── MeasurementConversationHandler.php
│   ├── WeightConversationHandler.php
│   ├── MacroConversationHandler.php
│   └── SyncConversationHandler.php
│
├── Exceptions/                 # Custom exceptions
│   ├── UnknownCommandException.php
│   ├── NoActiveConversationException.php
│   ├── InvalidConversationStepException.php
│   └── NoHandlerForConversationTypeException.php
│
├── Handlers/
│   └── FitnessCoachWebhookHandler.php  # Main entry point
│
├── Keyboards/                  # Keyboard builders (Phase 1)
│   ├── KeyboardFactory.php     # 13 predefined keyboards
│   └── KeyboardBuilder.php     # Dynamic keyboard builder
│
├── Middleware/                 # Authorization middleware (Phase 1)
│   ├── Contracts/
│   │   └── TelegramMiddleware.php
│   ├── MiddlewarePipeline.php
│   ├── RequireAccountLinkMiddleware.php
│   └── RequireFatSecretAuthMiddleware.php
│
└── Services/                   # Core services
    ├── TelegramCommandRegistry.php
    ├── MessageResponseBuilder.php
    ├── ConversationStateService.php
    ├── DateValidationService.php
    ├── TelegramUserService.php
    ├── TelegramAccountService.php
    └── TelegramFatSecretService.php
```

### Key Components

#### 1. TelegramCommandRegistry
- Registers command handlers
- O(1) command lookup
- Returns handler instance or throws exception

#### 2. CallbackRegistry
- Registers callback handlers
- O(1) callback action lookup
- Direct delegation to handlers

#### 3. ConversationManager
- Orchestrates conversation flows
- Routes messages to appropriate handlers
- Manages conversation lifecycle (start, continue, complete)

#### 4. KeyboardFactory
- Provides 13 predefined keyboards
- Consistent keyboard layouts
- Eliminates code duplication

#### 5. MessageResponseBuilder
- Fluent API for message formatting
- 30+ methods for consistent messages
- Supports icons, titles, fields, examples, instructions

---

## Getting Started

### Prerequisites

- PHP 8.2+
- Laravel 10+
- Telegraph package (`defstudio/telegraph`)
- Docker environment (coach_fpm container)

### Environment Setup

```bash
# Start Docker containers
docker-compose -p coach up -d

# Enter PHP container
docker exec -it coach_fpm bash

# Set up webhook
php artisan telegram:webhook --url=https://your-domain.com/api/telegram/webhook

# Check webhook status
php artisan telegram:info
```

### Configuration

Telegram bot configuration is in `config/telegraph.php`:

```php
'webhook_handler' => \App\Telegram\Handlers\FitnessCoachWebhookHandler::class,
```

Service provider registration in `app/Providers/TelegramBotServiceProvider.php`:

```php
public function register(): void
{
    // Register CommandRegistry with handlers
    $this->app->singleton(TelegramCommandRegistry::class, function ($app) {
        // ... handler registration
    });

    // Register CallbackRegistry with handlers
    $this->app->singleton(CallbackRegistry::class, function ($app) {
        // ... handler registration
    });

    // Register ConversationManager with handlers
    $this->app->singleton(ConversationManager::class, function ($app) {
        // ... handler registration
    });
}
```

---

## How to Add a New Command

Commands are bot commands that start with `/` (e.g., `/start`, `/help`).

### Step 1: Create Command Handler Class

Create a new file in `app/Telegram/Commands/`:

```php
<?php

namespace App\Telegram\Commands;

use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\MessageResponseBuilder;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * StatsCommandHandler - Shows user statistics
 */
class StatsCommandHandler implements TelegramCommandHandler
{
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
        private readonly MessageResponseBuilder $messageBuilder,
    ) {}

    /**
     * Handle the /stats command
     */
    public function handle(TelegraphChat $chat): void
    {
        // Build message using MessageResponseBuilder
        $message = $this->messageBuilder
            ->create()
            ->icon('📊')
            ->title('Статистика')
            ->addField('Записей веса', '45')
            ->addField('Записей замеров', '12')
            ->addField('Записей КБЖУ', '120')
            ->text('За последние 30 дней')
            ->build();

        // Send message with keyboard
        $chat->html($message)
            ->keyboard($this->keyboardFactory->mainMenu())
            ->send();
    }

    /**
     * Get the command name (without /)
     */
    public function getCommandName(): string
    {
        return 'stats';
    }
}
```

### Step 2: Register in Service Provider

Update `app/Providers/TelegramBotServiceProvider.php`:

```php
use App\Telegram\Commands\StatsCommandHandler;

public function register(): void
{
    $this->app->singleton(TelegramCommandRegistry::class, function ($app) {
        $keyboardFactory = $app->make(KeyboardFactory::class);
        $messageBuilder = $app->make(MessageResponseBuilder::class);

        $registry = new TelegramCommandRegistry();

        // ... existing handlers

        // Register new stats command
        $registry->register(new StatsCommandHandler(
            $keyboardFactory,
            $messageBuilder
        ));

        return $registry;
    });
}
```

### Step 3: Add Delegation Method to Webhook Handler

Update `app/Telegram/Handlers/FitnessCoachWebhookHandler.php`:

```php
/**
 * Handle /stats command
 * Delegates to StatsCommandHandler via registry
 */
public function stats(): void
{
    $this->commandRegistry->handle('stats', $this->chat);
}
```

### Step 4: Testing

```bash
# Test in Telegram
/stats

# Expected: Shows statistics message with main menu keyboard
```

### Command Handler Checklist

- [ ] Implements `TelegramCommandHandler` interface
- [ ] Has `handle()` method accepting `TelegraphChat`
- [ ] Has `getCommandName()` method returning command name
- [ ] Uses `MessageResponseBuilder` for messages
- [ ] Uses `KeyboardFactory` for keyboards
- [ ] Registered in `TelegramBotServiceProvider`
- [ ] Added delegation method to `FitnessCoachWebhookHandler`
- [ ] Tested with actual bot

---

## How to Add a New Callback

Callbacks are triggered by button presses in inline keyboards.

### Step 1: Create Callback Handler Class

Create a new file in appropriate subdirectory of `app/Telegram/Callbacks/`:

```php
<?php

namespace App\Telegram\Callbacks\MainMenu;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\MessageResponseBuilder;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * ShowStatsCallback - Shows statistics menu
 */
class ShowStatsCallback implements CallbackHandler
{
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
        private readonly MessageResponseBuilder $messageBuilder,
    ) {}

    /**
     * Handle the callback
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param int|null $messageId Message ID to edit (if editing existing message)
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        $message = $this->messageBuilder
            ->create()
            ->icon('📊')
            ->title('Статистика')
            ->text('Выберите период для просмотра статистики:')
            ->build();

        $keyboard = $this->keyboardFactory->statsMenu(); // Or build custom

        // Edit existing message if messageId provided, otherwise send new
        if ($messageId) {
            $chat->edit($messageId)
                ->html($message)
                ->keyboard($keyboard)
                ->send();
        } else {
            $chat->html($message)
                ->keyboard($keyboard)
                ->send();
        }
    }

    /**
     * Get the callback action name
     */
    public function getCallbackName(): string
    {
        return 'showStats';
    }
}
```

### Step 2: Create Custom Keyboard (if needed)

If you need a new keyboard, add it to `app/Telegram/Keyboards/KeyboardFactory.php`:

```php
/**
 * Build stats menu keyboard
 */
public function statsMenu(): Keyboard
{
    return Keyboard::make()->buttons([
        Button::make('📅 Неделя')->action('statsWeek'),
        Button::make('📅 Месяц')->action('statsMonth'),
        Button::make('📅 Год')->action('statsYear'),
        Button::make('🏠 Главное меню')->action('mainMenu'),
    ])->chunk(2);
}
```

### Step 3: Register in Service Provider

Update `app/Providers/TelegramBotServiceProvider.php`:

```php
use App\Telegram\Callbacks\MainMenu\ShowStatsCallback;

$this->app->singleton(CallbackRegistry::class, function ($app) {
    $keyboardFactory = $app->make(KeyboardFactory::class);
    $messageBuilder = $app->make(MessageResponseBuilder::class);

    $registry = new CallbackRegistry();

    // ... existing handlers

    // Register new stats callback
    $registry->register(new ShowStatsCallback(
        $keyboardFactory,
        $messageBuilder
    ));

    return $registry;
});
```

### Step 4: Add Button to Parent Keyboard

Update the keyboard that should contain the new button:

```php
// In KeyboardFactory.php mainMenu() method
public function mainMenu(): Keyboard
{
    return Keyboard::make()->buttons([
        Button::make('⚙️ Настройки')->action('showSettings'),
        Button::make('📊 Статистика')->action('showStats'), // NEW
        Button::make('📏 Замеры')->action('showMeasurements'),
        // ... other buttons
    ])->chunk(2);
}
```

### Step 5: Testing

```bash
# Test in Telegram
1. Send /start
2. Click "📊 Статистика" button
3. Verify stats menu appears
```

### Callback Handler Checklist

- [ ] Implements `CallbackHandler` interface
- [ ] Has `handle()` method accepting `TelegraphChat` and `?int $messageId`
- [ ] Has `getCallbackName()` method returning action name
- [ ] Uses `MessageResponseBuilder` for messages
- [ ] Uses `KeyboardFactory` for keyboards
- [ ] Handles both message editing and new messages
- [ ] Registered in `CallbackRegistry` (service provider)
- [ ] Button added to parent keyboard
- [ ] Tested with actual bot

---

## How to Add a New Conversation

Conversations are multi-step interactions (e.g., collecting date then value).

### Step 1: Create Conversation Handler Class

Create a new file in `app/Telegram/Conversations/`:

```php
<?php

namespace App\Telegram\Conversations;

use App\Telegram\Conversations\Contracts\ConversationContext;
use App\Telegram\Conversations\Contracts\ConversationHandler;
use App\Telegram\Conversations\Contracts\ConversationResult;
use App\Telegram\Exceptions\InvalidConversationStepException;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\DateValidationService;
use App\Telegram\Services\MessageResponseBuilder;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Stringable;

/**
 * ExerciseConversationHandler - Handles exercise logging
 *
 * Flow:
 * 1. input_date - User enters date
 * 2. input_exercise - User enters exercise name
 * 3. input_reps - User enters number of reps
 */
class ExerciseConversationHandler implements ConversationHandler
{
    public function __construct(
        private readonly DateValidationService $dateValidation,
        private readonly KeyboardFactory $keyboardFactory,
        private readonly MessageResponseBuilder $messageBuilder,
    ) {}

    /**
     * Handle a conversation step
     */
    public function handle(
        TelegraphChat $chat,
        string $step,
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        return match ($step) {
            'input_date' => $this->handleDateInput($message, $context),
            'input_exercise' => $this->handleExerciseInput($message, $context),
            'input_reps' => $this->handleRepsInput($message, $context),
            default => throw new InvalidConversationStepException($step),
        };
    }

    /**
     * Handle date input step
     */
    private function handleDateInput(
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        $dateResult = $this->dateValidation->validateAndParseDate((string) $message);

        if (!$dateResult['valid']) {
            return ConversationResult::error($dateResult['error']);
        }

        return ConversationResult::continue(
            nextStep: 'input_exercise',
            message: $this->messageBuilder
                ->create()
                ->icon('💪')
                ->title('Запись упражнения')
                ->text('Введите название упражнения:')
                ->example('Например: Приседания')
                ->build(),
            data: [
                'date' => $dateResult['formatted'],
                'date_object' => $dateResult['date'],
            ]
        );
    }

    /**
     * Handle exercise name input step
     */
    private function handleExerciseInput(
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        $exercise = trim((string) $message);

        if (empty($exercise)) {
            return ConversationResult::error(
                $this->messageBuilder
                    ->create()
                    ->icon('❌')
                    ->title('Ошибка')
                    ->text('Название упражнения не может быть пустым')
                    ->build()
            );
        }

        return ConversationResult::continue(
            nextStep: 'input_reps',
            message: $this->messageBuilder
                ->create()
                ->icon('🔢')
                ->title('Количество повторений')
                ->text('Введите количество повторений:')
                ->example('Например: 20')
                ->build(),
            data: [
                'exercise' => $exercise,
            ]
        );
    }

    /**
     * Handle reps input step (final step)
     */
    private function handleRepsInput(
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        $repsInput = trim((string) $message);

        if (!is_numeric($repsInput)) {
            return ConversationResult::error(
                $this->messageBuilder
                    ->create()
                    ->icon('❌')
                    ->title('Неверное значение')
                    ->text('Введите число (количество повторений):')
                    ->example('Например: 20')
                    ->build()
            );
        }

        $reps = (int) $repsInput;

        if ($reps < 1 || $reps > 1000) {
            return ConversationResult::error(
                $this->messageBuilder
                    ->create()
                    ->icon('❌')
                    ->title('Значение вне допустимого диапазона')
                    ->text('Введите значение от 1 до 1000:')
                    ->build()
            );
        }

        // TODO: Phase 6 - Save to database via Action
        // $exercise = $this->saveExerciseAction->handle([...]);

        $date = $context->data['date'];
        $exercise = $context->data['exercise'];

        return ConversationResult::complete(
            message: $this->messageBuilder
                ->create()
                ->icon('✅')
                ->title('Упражнение сохранено')
                ->addField('Упражнение', $exercise)
                ->addField('Повторений', (string) $reps)
                ->addField('Дата', $date)
                ->build(),
            keyboard: $this->keyboardFactory->mainMenu()
        );
    }

    /**
     * Get the conversation type identifier
     */
    public function getType(): string
    {
        return 'exercise';
    }

    /**
     * Get the initial step for this conversation
     */
    public function getInitialStep(): string
    {
        return 'input_date';
    }

    /**
     * Check if this handler can handle the given conversation type
     */
    public function canHandle(string $type): bool
    {
        return $type === $this->getType();
    }
}
```

### Step 2: Register in Service Provider

Update `app/Providers/TelegramBotServiceProvider.php`:

```php
use App\Telegram\Conversations\ExerciseConversationHandler;

$this->app->singleton(ConversationManager::class, function ($app) {
    $dateValidation = $app->make(DateValidationService::class);
    $keyboardFactory = $app->make(KeyboardFactory::class);
    $messageBuilder = $app->make(MessageResponseBuilder::class);

    return new ConversationManager(
        $app->make(ConversationStateService::class),
        $app->make(TelegramUserService::class),
        [
            // ... existing handlers
            new ExerciseConversationHandler(
                $dateValidation,
                $keyboardFactory,
                $messageBuilder
            ),
        ]
    );
});
```

### Step 3: Create Conversation Initiator Callback

Create a callback handler that starts the conversation:

```php
<?php

namespace App\Telegram\Callbacks\Exercise;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Services\ConversationStateService;
use App\Telegram\Services\MessageResponseBuilder;
use DefStudio\Telegraph\Models\TelegraphChat;

class StartExerciseCallback implements CallbackHandler
{
    public function __construct(
        private readonly ConversationStateService $conversationState,
        private readonly MessageResponseBuilder $messageBuilder,
    ) {}

    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        $chatId = (string) $chat->chat_id;

        // Start conversation
        $this->conversationState->startConversation(
            $chatId,
            'exercise',
            'input_date'
        );

        // Send initial prompt
        $message = $this->messageBuilder
            ->create()
            ->icon('💪')
            ->title('Запись упражнения')
            ->text('Введите дату тренировки:')
            ->example('Например: сегодня, вчера, или 2025-01-15')
            ->build();

        $chat->html($message)->send();
    }

    public function getCallbackName(): string
    {
        return 'startExercise';
    }
}
```

Register this callback in the service provider and add a button to trigger it.

### Step 4: Testing

```bash
# Test in Telegram
1. Click button that triggers 'startExercise' callback
2. Enter date: "сегодня"
3. Enter exercise: "Приседания"
4. Enter reps: "20"
5. Verify success message appears
```

### Conversation Handler Checklist

- [ ] Implements `ConversationHandler` interface
- [ ] Has `handle()` method routing to step handlers
- [ ] Implements all step handler methods
- [ ] Uses `ConversationResult::continue()` for intermediate steps
- [ ] Uses `ConversationResult::complete()` for final step
- [ ] Uses `ConversationResult::error()` for validation errors
- [ ] Validates all user inputs properly
- [ ] Has `getType()`, `getInitialStep()`, `canHandle()` methods
- [ ] Registered in `ConversationManager` (service provider)
- [ ] Has initiator callback registered
- [ ] Tested full flow with actual bot

---

## How to Add Middleware

Middleware provides reusable authorization guards for commands and callbacks.

### Step 1: Create Middleware Class

Create a new file in `app/Telegram/Middleware/`:

```php
<?php

namespace App\Telegram\Middleware;

use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Middleware\Contracts\TelegramMiddleware;
use App\Telegram\Services\TelegramUserService;
use DefStudio\Telegraph\Models\TelegraphChat;

/**
 * RequirePremiumMiddleware - Ensures user has premium subscription
 */
class RequirePremiumMiddleware implements TelegramMiddleware
{
    public function __construct(
        private readonly TelegramUserService $userService,
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    /**
     * Handle the middleware check
     *
     * @param TelegraphChat $chat Telegram chat instance
     * @param callable $next Next middleware or final action
     * @return mixed Result from next middleware or false if blocked
     */
    public function handle(TelegraphChat $chat, callable $next): mixed
    {
        $user = $this->userService->getCurrentUser($chat->chat_id);

        // Check if user has premium subscription
        if (!$user || !$user->has_premium) {
            $chat->html(
                "⭐ <b>Премиум функция</b>\n\n" .
                "Эта функция доступна только для премиум пользователей.\n\n" .
                "Используйте /premium для подключения."
            )
                ->keyboard($this->keyboardFactory->mainMenu())
                ->send();

            return false; // Block execution
        }

        // User has premium, continue to next middleware or action
        return $next($user);
    }
}
```

### Step 2: Use Middleware in Handler

Commands and callbacks can use middleware through `MiddlewarePipeline`:

```php
<?php

namespace App\Telegram\Commands;

use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Middleware\MiddlewarePipeline;
use App\Telegram\Middleware\RequireAccountLinkMiddleware;
use App\Telegram\Middleware\RequirePremiumMiddleware;
use App\Telegram\Services\MessageResponseBuilder;
use DefStudio\Telegraph\Models\TelegraphChat;

class AdvancedStatsCommandHandler implements TelegramCommandHandler
{
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
        private readonly MessageResponseBuilder $messageBuilder,
        private readonly MiddlewarePipeline $middlewarePipeline,
        private readonly RequireAccountLinkMiddleware $accountMiddleware,
        private readonly RequirePremiumMiddleware $premiumMiddleware,
    ) {}

    public function handle(TelegraphChat $chat): void
    {
        // Build middleware pipeline
        $pipeline = new MiddlewarePipeline([
            $this->accountMiddleware,
            $this->premiumMiddleware,
        ]);

        // Execute through pipeline
        $pipeline->through($chat, function ($user) use ($chat) {
            // Only executed if all middleware passes
            $message = $this->messageBuilder
                ->create()
                ->icon('📊')
                ->title('Расширенная статистика')
                ->text('Премиум статистика для пользователя: ' . $user->name)
                ->build();

            $chat->html($message)
                ->keyboard($this->keyboardFactory->mainMenu())
                ->send();
        });
    }

    public function getCommandName(): string
    {
        return 'advstats';
    }
}
```

### Middleware Checklist

- [ ] Implements `TelegramMiddleware` interface
- [ ] Has `handle()` method accepting `TelegraphChat` and `callable`
- [ ] Returns result from `$next($param)` if authorized
- [ ] Returns `false` and sends error message if blocked
- [ ] Sends user-friendly Russian error messages
- [ ] Includes appropriate keyboard with error message
- [ ] Tested with handlers that use it

---

## Testing Guidelines

### Manual Testing Checklist

```bash
# 1. Commands
/start     # Should show welcome message
/help      # Should show help text
/account   # Should show account menu
/fatsecret # Should show FatSecret menu
/sync      # Should show sync menu (with auth) or error (without)

# 2. Main Menu Callbacks
Settings → Should show settings menu
Measurements → Should show measurements menu
Sync → Should show sync menu
Macros → Should show macros menu
Weight → Should show weight menu
Help → Should show help text

# 3. Account Linking
Account → Generate Code → Should show 6-digit code
Account → Check Status → Should show current status
Account → Unlink → Should confirm and unlink

# 4. FatSecret Integration
FatSecret → Check Connection → Should show status
FatSecret → Connect → Should start OAuth flow
FatSecret → Logout → Should confirm and logout

# 5. Conversations - Measurement
Measurements → New Measurement → Enter "сегодня"
→ Enter "95" → Should save and show success

# 6. Conversations - Weight
Weight → Add Weight → Enter "сегодня"
→ Enter "75.5" → Should save and show success

# 7. Conversations - Macros
Macros → Calories → Enter "сегодня"
→ Enter "2000" → Should save and show success

# 8. Error Handling
Try invalid date format → Should show error and stay in same step
Try value out of range → Should show error with valid range
Try non-numeric value → Should show error with example
```

### Unit Testing (Future - Phase 7)

Example test structure:

```php
<?php

namespace Tests\Unit\Telegram\Commands;

use App\Telegram\Commands\StartCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\MessageResponseBuilder;
use DefStudio\Telegraph\Models\TelegraphChat;
use Mockery;
use Tests\TestCase;

class StartCommandHandlerTest extends TestCase
{
    public function test_handle_sends_welcome_message(): void
    {
        // Arrange
        $chat = Mockery::mock(TelegraphChat::class);
        $keyboardFactory = Mockery::mock(KeyboardFactory::class);
        $messageBuilder = new MessageResponseBuilder();

        $handler = new StartCommandHandler($keyboardFactory, $messageBuilder);

        // Expect HTML message with keyboard
        $chat->shouldReceive('html')->once()->andReturnSelf();
        $chat->shouldReceive('keyboard')->once()->andReturnSelf();
        $chat->shouldReceive('send')->once();

        // Act
        $handler->handle($chat);

        // Assert - implicit via Mockery expectations
    }
}
```

---

## Common Patterns & Best Practices

### 1. Always Use MessageResponseBuilder

**✅ Correct:**
```php
$message = $this->messageBuilder
    ->create()
    ->icon('✅')
    ->title('Success')
    ->text('Operation completed')
    ->build();

$chat->html($message)->send();
```

**❌ Incorrect:**
```php
$chat->message("✅ Success\n\nOperation completed")->send();
```

### 2. Always Use KeyboardFactory

**✅ Correct:**
```php
$chat->html($message)
    ->keyboard($this->keyboardFactory->mainMenu())
    ->send();
```

**❌ Incorrect:**
```php
$keyboard = Keyboard::make()->buttons([
    Button::make('🏠 Main')->action('mainMenu'),
])->chunk(1);
```

### 3. Proper Error Handling in Conversations

**✅ Correct:**
```php
if (!is_numeric($input)) {
    return ConversationResult::error(
        $this->messageBuilder
            ->create()
            ->icon('❌')
            ->title('Invalid Input')
            ->text('Please enter a number')
            ->example('Example: 75.5')
            ->build()
    );
}
```

**❌ Incorrect:**
```php
if (!is_numeric($input)) {
    $chat->message("Error: Invalid input")->send();
    return null; // Wrong!
}
```

### 4. Dependency Injection

**✅ Correct:**
```php
public function __construct(
    private readonly KeyboardFactory $keyboardFactory,
    private readonly MessageResponseBuilder $messageBuilder,
) {}
```

**❌ Incorrect:**
```php
public function handle(TelegraphChat $chat): void
{
    $keyboardFactory = new KeyboardFactory(); // Don't instantiate!
}
```

### 5. Type Hints Everywhere

**✅ Correct:**
```php
public function handle(TelegraphChat $chat): void
{
    // Implementation
}
```

**❌ Incorrect:**
```php
public function handle($chat)  // No type hints!
{
    // Implementation
}
```

### 6. Russian User-Facing Messages

**✅ Correct:**
```php
->text('Введите ваш вес в килограммах')
->example('Например: 75.5')
```

**❌ Incorrect:**
```php
->text('Enter your weight in kilograms')  // English!
->example('Example: 75.5')
```

### 7. PHPDoc Comments

**✅ Correct:**
```php
/**
 * Handle the /stats command
 *
 * Shows user statistics for the last 30 days including
 * weight entries, measurements, and macro tracking.
 *
 * @param TelegraphChat $chat Telegram chat instance
 * @return void
 */
public function handle(TelegraphChat $chat): void
```

### 8. Consistent Naming

- Commands: `{Action}CommandHandler` (e.g., `StatsCommandHandler`)
- Callbacks: `{Action}Callback` (e.g., `ShowStatsCallback`)
- Conversations: `{Type}ConversationHandler` (e.g., `ExerciseConversationHandler`)
- Middleware: `Require{Condition}Middleware` (e.g., `RequirePremiumMiddleware`)

---

## Troubleshooting

### Issue: Command Not Working

**Symptoms:** Typing `/mycommand` does nothing or shows "unknown command"

**Solutions:**
1. Check if handler is registered in `TelegramBotServiceProvider`
2. Check if delegation method exists in `FitnessCoachWebhookHandler`
3. Check command name matches: `getCommandName()` returns correct string
4. Clear config cache: `php artisan config:clear`
5. Check logs: `docker exec coach_fpm tail -f storage/logs/laravel.log`

### Issue: Callback Not Responding

**Symptoms:** Clicking button does nothing or shows error

**Solutions:**
1. Check if callback is registered in `CallbackRegistry`
2. Check callback name matches button action: `Button::make('Text')->action('actionName')`
3. Check `getCallbackName()` returns correct action name
4. Verify no typos in action names (case-sensitive!)
5. Check Telegraph package logs

### Issue: Conversation Not Starting

**Symptoms:** Clicking button doesn't start conversation flow

**Solutions:**
1. Check if conversation handler is registered in `ConversationManager`
2. Check initiator callback calls `conversationState->startConversation()`
3. Verify conversation type matches: `getType()` === type in `startConversation()`
4. Check initial step matches: `getInitialStep()`
5. Check conversation state in Redis: `redis-cli KEYS telegram_user_state_*`

### Issue: Conversation Gets Stuck

**Symptoms:** User enters data but conversation doesn't progress

**Solutions:**
1. Check all steps are handled in `handle()` match expression
2. Verify `ConversationResult::continue()` specifies correct `nextStep`
3. Check validation doesn't have infinite error loops
4. Verify step names match exactly (case-sensitive!)
5. Clear stuck conversation: Delete Redis key `telegram_user_state_{chatId}`

### Issue: Middleware Blocks Everything

**Symptoms:** All commands/callbacks show authorization error

**Solutions:**
1. Check middleware logic returns `$next($param)` when authorized
2. Verify user retrieval works: `getCurrentUser()` returns user
3. Check authorization condition is correct
4. Test without middleware first, then add back
5. Check middleware order in pipeline (order matters!)

### Issue: Message Formatting Broken

**Symptoms:** Messages show HTML tags or formatting issues

**Solutions:**
1. Always use `$chat->html()` not `$chat->message()`
2. Use `MessageResponseBuilder` for all messages
3. Escape special HTML characters if needed
4. Check Telegraph documentation for supported HTML tags
5. Test with simpler message first

### Common Error Messages

```bash
# "No handler found for conversation type: X"
→ Handler not registered in ConversationManager

# "Unknown command: X"
→ Command not registered in TelegramCommandRegistry

# "Unknown callback action: X"
→ Callback not registered in CallbackRegistry

# "Call to undefined method"
→ Check method name spelling, check interface implementation

# "Too few arguments to function"
→ Check constructor dependencies, check service provider bindings
```

### Debug Commands

```bash
# Check Laravel about
docker exec coach_fpm php artisan about

# Check webhook status
docker exec coach_fpm php artisan telegram:info

# Clear all caches
docker exec coach_fpm php artisan cache:clear
docker exec coach_fpm php artisan config:clear
docker exec coach_fpm php artisan route:clear

# View logs
docker exec coach_fpm tail -f storage/logs/laravel.log

# Check Redis conversations
docker exec coach_redis redis-cli KEYS "telegram_user_state_*"
docker exec coach_redis redis-cli GET "telegram_user_state_123456"

# Generate IDE helpers
docker exec coach_fpm php artisan ide-helper:generate
```

---

## Migration Notes

### From Old Nutgram Architecture

The bot was migrated from Nutgram framework to Telegraph framework across 5 phases:

**Phase 1 (Foundation)**: Infrastructure, interfaces, services (20 files, 1,796 lines)
**Phase 2 (Commands)**: Command handlers and registry (5 handlers, 310 lines)
**Phase 3 (Callbacks)**: Callback handlers and registry (25 handlers, 1,896 lines)
**Phase 4 (Conversations)**: Conversation handlers and manager (4 handlers, 1,137 lines)
**Phase 5 (Cleanup)**: Removed deprecated code (31 files deleted, 447→211 lines handler)

### Key Differences

| Aspect | Old (Nutgram) | New (Telegraph) |
|--------|---------------|-----------------|
| Framework | Nutgram | Telegraph (Laravel-native) |
| Handler Size | 1,406 lines (monolithic) | 211 lines (orchestration) |
| Command Lookup | Linear if-else | O(1) registry |
| Callback Lookup | Magic methods | O(1) registry |
| Conversation Flow | Inline methods | Strategy handlers |
| Authorization | Guard methods | Middleware pipeline |
| Keyboards | Inline builders | Factory pattern |
| Messages | String concatenation | Builder pattern |
| Testing | Difficult | Easy (mockable interfaces) |

### Breaking Changes

1. **No more Nutgram classes** - All `SergiX44\Nutgram` imports removed
2. **No more magic methods** - Callbacks use direct delegation
3. **No more inline keyboards** - Use `KeyboardFactory`
4. **No more guard methods** - Use middleware pipeline
5. **No more string messages** - Use `MessageResponseBuilder`

### References

- **Architecture Proposal**: `TELEGRAM_BOT_ARCHITECTURE_PROPOSAL.md`
- **Implementation Plan**: `TELEGRAM_REFACTORING_IMPLEMENTATION_PLAN.md`
- **Phase Changelogs**:
  - `TELEGRAM_REFACTORING_PHASE2_CHANGELOG.md`
  - `TELEGRAM_REFACTORING_PHASE3_CHANGELOG.md`
  - `TELEGRAM_REFACTORING_PHASE4_CHANGELOG.md`
  - `TELEGRAM_REFACTORING_PHASE5_CHANGELOG.md`
- **Technical Docs**: `claude/TELEGRAM_REFACTORING_TECHNICAL_DOCS.md`
- **Project Guide**: `CLAUDE.md` (Section 5: Telegram Bot Architecture)

---

## Support & Resources

### Documentation

- **Telegraph Package**: https://github.com/defstudio/telegraph
- **Laravel Documentation**: https://laravel.com/docs/10.x
- **Telegram Bot API**: https://core.telegram.org/bots/api

### Getting Help

1. Check this guide for examples
2. Review existing handler implementations
3. Check `CLAUDE.md` for project rules
4. Review phase changelogs for implementation details
5. Check Telegraph package documentation

### Code Examples Location

- **Commands**: `app/Telegram/Commands/`
- **Callbacks**: `app/Telegram/Callbacks/`
- **Conversations**: `app/Telegram/Conversations/`
- **Middleware**: `app/Telegram/Middleware/`

---

**End of Developer Guide**

*Last Updated: October 30, 2025*
*Version: 2.0 (Phase 5 Complete)*