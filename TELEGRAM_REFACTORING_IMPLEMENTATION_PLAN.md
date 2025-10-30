# Telegram Bot Refactoring Implementation Plan

**Project**: FitnessCoach Telegram Bot Architecture Refactoring
**Based on**: TELEGRAM_BOT_ARCHITECTURE_PROPOSAL.md
**Execution**: Claude Code (claude.ai/code)
**Date**: October 15, 2025

---

## Overview

This plan provides step-by-step instructions for refactoring the 1,406-line `FitnessCoachWebhookHandler.php` into a SOLID-compliant, maintainable architecture using design patterns.

**Target**: Reduce handler to ~150 lines of orchestration code while improving testability, maintainability, and scalability.

**Strategy**: Strangler Fig Pattern - Gradually replace monolith functionality while keeping system operational.

---

## Phase 1: Foundation & Infrastructure

**Duration**: 2-3 days
**Goal**: Create all interfaces, base classes, and supporting services without breaking existing functionality.

### Task 1.1: Create Interface Contracts

**Priority**: HIGH
**Estimated Time**: 1 hour

**Files to Create**:
```
app/Telegram/Commands/Contracts/TelegramCommandHandler.php
app/Telegram/Conversations/Contracts/ConversationHandler.php
app/Telegram/Conversations/Contracts/ConversationContext.php
app/Telegram/Conversations/Contracts/ConversationResult.php
app/Telegram/Conversations/Contracts/ConversationStatus.php (enum)
app/Telegram/Callbacks/Contracts/CallbackHandler.php
app/Telegram/Middleware/Contracts/TelegramMiddleware.php
```

**Implementation Details**:

1. **TelegramCommandHandler Interface**:
```php
<?php

namespace App\Telegram\Commands\Contracts;

use DefStudio\Telegraph\Models\TelegraphChat;

interface TelegramCommandHandler
{
    /**
     * Handle the command execution
     */
    public function handle(TelegraphChat $chat): void;

    /**
     * Get the command name (e.g., 'start', 'help')
     */
    public function getCommandName(): string;
}
```

2. **ConversationHandler Interface**:
```php
<?php

namespace App\Telegram\Conversations\Contracts;

use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Stringable;

interface ConversationHandler
{
    /**
     * Handle a conversation step
     */
    public function handle(
        TelegraphChat $chat,
        string $step,
        Stringable $message,
        ConversationContext $context
    ): ConversationResult;

    /**
     * Get the conversation type identifier
     */
    public function getType(): string;

    /**
     * Get the initial step for this conversation
     */
    public function getInitialStep(): string;

    /**
     * Check if this handler can handle the given type
     */
    public function canHandle(string $type): bool;
}
```

3. **ConversationContext DTO**:
```php
<?php

namespace App\Telegram\Conversations\Contracts;

use App\Models\User;

class ConversationContext
{
    public function __construct(
        public readonly string $chatId,
        public readonly User $user,
        public readonly array $data = [],
    ) {}
}
```

4. **ConversationResult DTO**:
```php
<?php

namespace App\Telegram\Conversations\Contracts;

use DefStudio\Telegraph\Keyboard\Keyboard;

class ConversationResult
{
    public function __construct(
        public readonly ConversationStatus $status,
        public readonly ?string $nextStep = null,
        public readonly ?string $message = null,
        public readonly ?Keyboard $keyboard = null,
        public readonly array $data = [],
    ) {}

    public static function continue(
        string $nextStep,
        string $message,
        array $data = []
    ): self {
        return new self(
            status: ConversationStatus::Continue,
            nextStep: $nextStep,
            message: $message,
            data: $data
        );
    }

    public static function complete(
        string $message,
        ?Keyboard $keyboard = null
    ): self {
        return new self(
            status: ConversationStatus::Complete,
            message: $message,
            keyboard: $keyboard
        );
    }

    public static function error(string $message): self
    {
        return new self(
            status: ConversationStatus::Continue, // Stay in same step
            message: $message
        );
    }
}
```

5. **ConversationStatus Enum**:
```php
<?php

namespace App\Telegram\Conversations\Contracts;

enum ConversationStatus
{
    case Continue;   // Continue conversation to next step
    case Complete;   // Conversation finished successfully
    case Error;      // Error occurred, stay in current step
}
```

6. **CallbackHandler Interface**:
```php
<?php

namespace App\Telegram\Callbacks\Contracts;

use DefStudio\Telegraph\Models\TelegraphChat;

interface CallbackHandler
{
    /**
     * Handle the callback execution
     */
    public function handle(TelegraphChat $chat, ?int $messageId = null): void;

    /**
     * Get the callback action name
     */
    public function getCallbackName(): string;
}
```

7. **TelegramMiddleware Interface**:
```php
<?php

namespace App\Telegram\Middleware\Contracts;

use Closure;
use DefStudio\Telegraph\Models\TelegraphChat;

interface TelegramMiddleware
{
    /**
     * Handle the middleware logic
     *
     * @param TelegraphChat $chat
     * @param Closure $next
     * @return mixed
     */
    public function handle(TelegraphChat $chat, Closure $next): mixed;
}
```

**Validation**:
- Run `php artisan about` - should pass without errors
- No existing functionality affected

---

### Task 1.2: Create KeyboardFactory Service

**Priority**: HIGH
**Estimated Time**: 1 hour

**File**: `app/Telegram/Keyboards/KeyboardFactory.php`

**Implementation**:
```php
<?php

namespace App\Telegram\Keyboards;

use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;

class KeyboardFactory
{
    /**
     * Main menu keyboard
     */
    public function mainMenu(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('⚙️ Настройки')->action('showSettings'),
            Button::make('📏 Замеры')->action('showMeasurements'),
            Button::make('🔄 Синхронизация')->action('showSync'),
            Button::make('🍎 КБЖУ')->action('showMacros'),
            Button::make('⚖️ Вес')->action('showWeight'),
            Button::make('❓ Помощь')->action('showHelp'),
        ]);
    }

    /**
     * Help menu keyboard
     */
    public function help(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Account linking keyboard
     */
    public function accountLinking(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('🔗 Привязать аккаунт')->action('accountLinking'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Account menu keyboard
     */
    public function accountMenu(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('📝 Получить код для привязки')->action('generateLinkCode'),
            Button::make('✅ Проверить привязку')->action('checkLinkStatus'),
            Button::make('❌ Отвязать аккаунт')->action('removeLinkAccount'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Account back navigation keyboard
     */
    public function accountBack(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('↩️ Назад')->action('accountLinking'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * FatSecret connection menu keyboard
     */
    public function fatSecretMenu(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('✅ Проверить привязку')->action('checkFatSecretConnection'),
            Button::make('🔗 Подключить FatSecret')->action('connectFatSecret'),
            Button::make('🚪 Выйти из FatSecret')->action('logoutFromFatSecret'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * FatSecret back navigation keyboard
     */
    public function fatSecretBack(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('↩️ Назад')->action('fatSecretConnect'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Sync menu keyboard
     */
    public function syncMenu(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('🔄 Полная синхронизация')->action('syncFull'),
            Button::make('⚖️ Синхронизация веса')->action('syncWeight'),
            Button::make('🍎 Дневник питания')->action('syncFood'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Sync back navigation keyboard
     */
    public function syncBack(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('↩️ Назад')->action('showSync'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Settings keyboard
     */
    public function settings(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('🔗 Привязка аккаунта')->action('accountLinking'),
            Button::make('🔐 FatSecret')->action('fatSecretConnect'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Measurements menu keyboard
     */
    public function measurements(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('📐 Новый замер')->action('startNewMeasurement'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Macros (КБЖУ) menu keyboard
     */
    public function macros(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('🔥 Калории')->action('selectMacroCalories'),
            Button::make('🥩 Белки')->action('selectMacroProteins'),
            Button::make('🧈 Жиры')->action('selectMacroFats'),
            Button::make('🍞 Углеводы')->action('selectMacroCarbs'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Weight tracking menu keyboard
     */
    public function weight(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('⚖️ Добавить вес')->action('startNewWeight'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Fluent keyboard builder for dynamic keyboards
     */
    public function builder(): KeyboardBuilder
    {
        return new KeyboardBuilder();
    }
}
```

**Also Create**: `app/Telegram/Keyboards/KeyboardBuilder.php`

```php
<?php

namespace App\Telegram\Keyboards;

use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;

class KeyboardBuilder
{
    private array $buttons = [];

    public function addButton(string $text, string $action): self
    {
        $this->buttons[] = Button::make($text)->action($action);
        return $this;
    }

    public function addMainMenuButton(): self
    {
        return $this->addButton('🏠 Главное меню', 'mainMenu');
    }

    public function addBackButton(string $action): self
    {
        return $this->addButton('↩️ Назад', $action);
    }

    public function build(): Keyboard
    {
        return Keyboard::make()->buttons($this->buttons);
    }
}
```

**Validation**:
- Create simple test to ensure keyboards build correctly
- No syntax errors

---

### Task 1.3: Create MessageResponseBuilder Service

**Priority**: MEDIUM
**Estimated Time**: 30 minutes

**File**: `app/Telegram/Services/MessageResponseBuilder.php`

**Implementation**:
```php
<?php

namespace App\Telegram\Services;

class MessageResponseBuilder
{
    private array $parts = [];

    public function icon(string $icon): self
    {
        $this->parts[] = $icon;
        return $this;
    }

    public function title(string $title): self
    {
        $this->parts[] = "**{$title}**";
        return $this;
    }

    public function text(string $text): self
    {
        $this->parts[] = $text;
        return $this;
    }

    public function instruction(string $instruction): self
    {
        $this->parts[] = $instruction;
        return $this;
    }

    public function example(string $example): self
    {
        $this->parts[] = $example;
        return $this;
    }

    public function addField(string $label, string $value): self
    {
        $this->parts[] = "{$label}: {$value}";
        return $this;
    }

    public function newLine(): self
    {
        $this->parts[] = '';
        return $this;
    }

    public function greeting(string $appName): self
    {
        $this->parts[] = "🎯 **Добро пожаловать в {$appName}!**";
        return $this;
    }

    public function addFeatures(array $features): self
    {
        $this->parts[] = 'Я помогу вам отслеживать:';

        $featureIcons = [
            'weight' => '⚖️ Вес и измерения тела',
            'measurements' => '📏 Замеры тела',
            'macros' => '🍎 Макронутриенты (КБЖУ)',
            'sync' => '🔄 Синхронизацию с FatSecret',
        ];

        foreach ($features as $feature) {
            if (isset($featureIcons[$feature])) {
                $this->parts[] = $featureIcons[$feature];
            }
        }

        return $this;
    }

    public function build(): string
    {
        return implode("\n", $this->parts);
    }

    public function reset(): self
    {
        $this->parts = [];
        return $this;
    }
}
```

**Validation**:
- Test building various messages
- Ensure HTML formatting works with Telegraph

---

### Task 1.4: Create TelegramCommandRegistry

**Priority**: HIGH
**Estimated Time**: 45 minutes

**File**: `app/Telegram/Services/TelegramCommandRegistry.php`

**Implementation**:
```php
<?php

namespace App\Telegram\Services;

use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Exceptions\UnknownCommandException;
use DefStudio\Telegraph\Models\TelegraphChat;

class TelegramCommandRegistry
{
    /** @var array<string, TelegramCommandHandler> */
    private array $handlers = [];

    /**
     * Register a command handler
     */
    public function register(TelegramCommandHandler $handler): void
    {
        $this->handlers[$handler->getCommandName()] = $handler;
    }

    /**
     * Register multiple command handlers
     *
     * @param iterable<TelegramCommandHandler> $handlers
     */
    public function registerMany(iterable $handlers): void
    {
        foreach ($handlers as $handler) {
            $this->register($handler);
        }
    }

    /**
     * Handle a command
     *
     * @throws UnknownCommandException
     */
    public function handle(string $command, TelegraphChat $chat): void
    {
        if (!isset($this->handlers[$command])) {
            throw new UnknownCommandException("Unknown command: /{$command}");
        }

        $this->handlers[$command]->handle($chat);
    }

    /**
     * Check if a command is registered
     */
    public function has(string $command): bool
    {
        return isset($this->handlers[$command]);
    }

    /**
     * Get all registered command names
     *
     * @return array<string>
     */
    public function getRegisteredCommands(): array
    {
        return array_keys($this->handlers);
    }
}
```

**Also Create Exception**: `app/Telegram/Exceptions/UnknownCommandException.php`

```php
<?php

namespace App\Telegram\Exceptions;

class UnknownCommandException extends AbstractTelegramBotException
{
    protected $message = 'Unknown command';
}
```

**Validation**:
- Test registration and retrieval
- Test exception throwing

---

### Task 1.5: Create Middleware Infrastructure

**Priority**: MEDIUM
**Estimated Time**: 45 minutes

**Files to Create**:
```
app/Telegram/Middleware/MiddlewarePipeline.php
app/Telegram/Middleware/RequireAccountLinkMiddleware.php
app/Telegram/Middleware/RequireFatSecretAuthMiddleware.php
```

**Implementation**:

1. **MiddlewarePipeline.php**:
```php
<?php

namespace App\Telegram\Middleware;

use Closure;
use DefStudio\Telegraph\Models\TelegraphChat;

class MiddlewarePipeline
{
    /**
     * @param array<\App\Telegram\Middleware\Contracts\TelegramMiddleware> $middleware
     */
    public function __construct(
        private readonly array $middleware = []
    ) {}

    /**
     * Execute the middleware pipeline
     */
    public function through(TelegraphChat $chat, Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            fn($next, $middleware) => fn($passable) => $middleware->handle($passable, $next),
            $destination
        );

        return $pipeline($chat);
    }
}
```

2. **RequireAccountLinkMiddleware.php**:
```php
<?php

namespace App\Telegram\Middleware;

use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Middleware\Contracts\TelegramMiddleware;
use App\Telegram\Services\TelegramUserService;
use Closure;
use DefStudio\Telegraph\Models\TelegraphChat;

class RequireAccountLinkMiddleware implements TelegramMiddleware
{
    public function __construct(
        private readonly TelegramUserService $userService,
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    public function handle(TelegraphChat $chat, Closure $next): mixed
    {
        $user = $this->userService->getCurrentUser($chat->chat_id);

        if (!$user) {
            $chat->message('❌ Аккаунт не привязан.\n\nИспользуйте /account для привязки аккаунта.')
                ->keyboard($this->keyboardFactory->accountLinking())
                ->send();

            return null;
        }

        // Pass user to next middleware/handler
        return $next($user);
    }
}
```

3. **RequireFatSecretAuthMiddleware.php**:
```php
<?php

namespace App\Telegram\Middleware;

use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Middleware\Contracts\TelegramMiddleware;
use Closure;
use DefStudio\Telegraph\Models\TelegraphChat;

class RequireFatSecretAuthMiddleware implements TelegramMiddleware
{
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    public function handle(TelegraphChat $chat, Closure $next): mixed
    {
        // Get user from previous middleware
        $user = $next->bindTo($this)();

        if (!$user || !$user->isFatSecretAuthorized()) {
            $chat->message('❌ FatSecret не подключен.\n\nИспользуйте /fatsecret для подключения.')
                ->keyboard($this->keyboardFactory->fatSecretMenu())
                ->send();

            return null;
        }

        return $next($user);
    }
}
```

**Validation**:
- Test middleware pipeline execution
- Test guard behavior

---

### Task 1.6: Create Additional Exceptions

**Priority**: LOW
**Estimated Time**: 15 minutes

**Files to Create**:
```
app/Telegram/Exceptions/NoActiveConversationException.php
app/Telegram/Exceptions/InvalidConversationStepException.php
app/Telegram/Exceptions/NoHandlerForConversationTypeException.php
```

**Implementation**:

```php
<?php

namespace App\Telegram\Exceptions;

class NoActiveConversationException extends AbstractTelegramBotException
{
    protected $message = 'No active conversation found';
}
```

```php
<?php

namespace App\Telegram\Exceptions;

class InvalidConversationStepException extends AbstractTelegramBotException
{
    protected $message = 'Invalid conversation step';
}
```

```php
<?php

namespace App\Telegram\Exceptions;

class NoHandlerForConversationTypeException extends AbstractTelegramBotException
{
    protected $message = 'No handler found for conversation type';
}
```

---

## Phase 2: Command Handlers Migration

**Duration**: 1-2 days
**Goal**: Extract all command methods from handler into separate command handler classes.

### Task 2.1: Create StartCommandHandler

**Priority**: HIGH
**Estimated Time**: 30 minutes

**File**: `app/Telegram/Commands/StartCommandHandler.php`

**Implementation**:
```php
<?php

namespace App\Telegram\Commands;

use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\MessageResponseBuilder;
use DefStudio\Telegraph\Models\TelegraphChat;

class StartCommandHandler implements TelegramCommandHandler
{
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
        private readonly MessageResponseBuilder $messageBuilder,
    ) {}

    public function handle(TelegraphChat $chat): void
    {
        $message = $this->messageBuilder
            ->greeting('FitnessCoach')
            ->newLine()
            ->addFeatures(['weight', 'measurements', 'macros', 'sync'])
            ->newLine()
            ->text('Используйте меню ниже для начала работы:')
            ->build();

        $chat->html($message)->send();

        // Show main menu
        $chat->html('🏠 Главное меню FitnessCoach')
            ->keyboard($this->keyboardFactory->mainMenu())
            ->send();
    }

    public function getCommandName(): string
    {
        return 'start';
    }
}
```

**Validation**:
- Test /start command sends correct message
- Test keyboard displays correctly

---

### Task 2.2: Create HelpCommandHandler

**Priority**: HIGH
**Estimated Time**: 30 minutes

**File**: `app/Telegram/Commands/HelpCommandHandler.php`

**Implementation**:
```php
<?php

namespace App\Telegram\Commands;

use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use DefStudio\Telegraph\Models\TelegraphChat;

class HelpCommandHandler implements TelegramCommandHandler
{
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    public function handle(TelegraphChat $chat): void
    {
        $helpText = $this->buildHelpText();

        $chat->html($helpText)
            ->keyboard($this->keyboardFactory->help())
            ->send();
    }

    public function getCommandName(): string
    {
        return 'help';
    }

    private function buildHelpText(): string
    {
        return "🆘 **Помощь по командам FitnessCoach**\n\n" .
               $this->formatCommandsList() . "\n\n" .
               "🔗 **Команды с параметрами:**\n" .
               "• /sync полная - Полная синхронизация с FatSecret";
    }

    private function formatCommandsList(): string
    {
        $commands = [];

        $commands[] = "📋 **Основные команды:**";
        $commands[] = "/start - Главное меню и приветствие";
        $commands[] = "/help - Помощь и список команд";

        $commands[] = "\n🚀 **Быстрые команды:**";
        $commands[] = "/sync [тип] - Синхронизация с FatSecret";

        $commands[] = "\n⚙️ **Управление:**";
        $commands[] = "/account - Привязка аккаунта";
        $commands[] = "/fatsecret - Подключение к FatSecret";

        return implode("\n", $commands);
    }
}
```

---

### Task 2.3: Create AccountCommandHandler

**Priority**: HIGH
**Estimated Time**: 20 minutes

**File**: `app/Telegram/Commands/AccountCommandHandler.php`

**Implementation**:
```php
<?php

namespace App\Telegram\Commands;

use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use DefStudio\Telegraph\Models\TelegraphChat;

class AccountCommandHandler implements TelegramCommandHandler
{
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    public function handle(TelegraphChat $chat): void
    {
        $chat->html('🔗 Привязка аккаунта')
            ->keyboard($this->keyboardFactory->accountMenu())
            ->send();
    }

    public function getCommandName(): string
    {
        return 'account';
    }
}
```

---

### Task 2.4: Create FatSecretCommandHandler

**Priority**: HIGH
**Estimated Time**: 20 minutes

**File**: `app/Telegram/Commands/FatSecretCommandHandler.php`

**Implementation**:
```php
<?php

namespace App\Telegram\Commands;

use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use DefStudio\Telegraph\Models\TelegraphChat;

class FatSecretCommandHandler implements TelegramCommandHandler
{
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    public function handle(TelegraphChat $chat): void
    {
        $chat->html('🔗 Привязка FatSecret')
            ->keyboard($this->keyboardFactory->fatSecretMenu())
            ->send();
    }

    public function getCommandName(): string
    {
        return 'fatsecret';
    }
}
```

---

### Task 2.5: Create SyncCommandHandler

**Priority**: HIGH
**Estimated Time**: 30 minutes

**File**: `app/Telegram/Commands/SyncCommandHandler.php`

**Implementation**:
```php
<?php

namespace App\Telegram\Commands;

use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Middleware\MiddlewarePipeline;
use App\Telegram\Middleware\RequireAccountLinkMiddleware;
use App\Telegram\Middleware\RequireFatSecretAuthMiddleware;
use DefStudio\Telegraph\Models\TelegraphChat;

class SyncCommandHandler implements TelegramCommandHandler
{
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
        private readonly RequireAccountLinkMiddleware $accountLinkMiddleware,
        private readonly RequireFatSecretAuthMiddleware $fatSecretAuthMiddleware,
    ) {}

    public function handle(TelegraphChat $chat): void
    {
        $pipeline = new MiddlewarePipeline([
            $this->accountLinkMiddleware,
            $this->fatSecretAuthMiddleware,
        ]);

        $pipeline->through($chat, function() use ($chat) {
            $instructionsText = "🔄 **Синхронизация с FatSecret**\n\n" .
                               "Выберите тип синхронизации:\n\n" .
                               "💡 **Доступные опции:**\n" .
                               "🔄 **Полная** - Синхронизация всех данных\n" .
                               "⚖️ **Вес** - Только данные о весе\n" .
                               "🍎 **Дневник питания** - Только питание\n\n" .
                               "⚠️ **Требуется подключение к FatSecret**";

            $chat->html($instructionsText)
                ->keyboard($this->keyboardFactory->syncMenu())
                ->send();
        });
    }

    public function getCommandName(): string
    {
        return 'sync';
    }
}
```

---

### Task 2.6: Register Commands in Service Provider

**Priority**: HIGH
**Estimated Time**: 30 minutes

**File**: `app/Telegram/Providers/TelegramServiceProvider.php` (update existing or create new)

**Implementation**:
```php
<?php

namespace App\Telegram\Providers;

use App\Telegram\Commands\AccountCommandHandler;
use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Commands\FatSecretCommandHandler;
use App\Telegram\Commands\HelpCommandHandler;
use App\Telegram\Commands\StartCommandHandler;
use App\Telegram\Commands\SyncCommandHandler;
use App\Telegram\Services\TelegramCommandRegistry;
use Illuminate\Support\ServiceProvider;

class TelegramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind command registry as singleton
        $this->app->singleton(TelegramCommandRegistry::class, function ($app) {
            $registry = new TelegramCommandRegistry();

            // Register all command handlers
            $registry->registerMany([
                $app->make(StartCommandHandler::class),
                $app->make(HelpCommandHandler::class),
                $app->make(AccountCommandHandler::class),
                $app->make(FatSecretCommandHandler::class),
                $app->make(SyncCommandHandler::class),
            ]);

            return $registry;
        });

        // Tag all command handlers for auto-discovery
        $this->app->tag([
            StartCommandHandler::class,
            HelpCommandHandler::class,
            AccountCommandHandler::class,
            FatSecretCommandHandler::class,
            SyncCommandHandler::class,
        ], 'telegram.commands');
    }

    public function boot(): void
    {
        //
    }
}
```

**Don't forget**: Register provider in `config/app.php` if not auto-discovered.

---

### Task 2.7: Update FitnessCoachWebhookHandler to Delegate Commands

**Priority**: HIGH
**Estimated Time**: 30 minutes

**File**: `app/Telegram/Handlers/FitnessCoachWebhookHandler.php` (modify existing)

**Changes**:
1. Add `TelegramCommandRegistry` to constructor
2. Update command methods to delegate to registry
3. Keep old methods temporarily (comment out after testing)

**Implementation**:
```php
// In constructor, add:
public function __construct(
    private readonly TelegramUserService $telegramUserService,
    private readonly TelegramAccountService $telegramAccountService,
    private readonly TelegramFatSecretService $telegramFatSecretService,
    private readonly ConversationStateService $conversationState,
    private readonly DateValidationService $dateValidation,
    private readonly TelegramCommandRegistry $commandRegistry, // NEW
) {
    parent::__construct();
}

// Replace command methods with delegation:
public function start(): void
{
    $this->commandRegistry->handle('start', $this->chat);
}

public function help(): void
{
    $this->commandRegistry->handle('help', $this->chat);
}

public function account(): void
{
    $this->commandRegistry->handle('account', $this->chat);
}

public function fatsecret(): void
{
    $this->commandRegistry->handle('fatsecret', $this->chat);
}

public function sync(): void
{
    $this->commandRegistry->handle('sync', $this->chat);
}

// OLD METHODS - Keep commented for rollback
/*
public function start(): void
{
    // ... old implementation
}
*/
```

**Validation**:
- Test all 5 commands work identically to before
- Run `php artisan about` - should pass
- Manual testing: /start, /help, /account, /fatsecret, /sync

---

## Phase 3: Callback Handlers Migration

**Duration**: 2-3 days
**Goal**: Extract all callback methods into separate handler classes organized by feature.

### Task 3.1: Create CallbackRegistry

**Priority**: HIGH
**Estimated Time**: 45 minutes

**File**: `app/Telegram/Callbacks/CallbackRegistry.php`

**Implementation**:
```php
<?php

namespace App\Telegram\Callbacks;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Exceptions\UnknownCommandException;
use DefStudio\Telegraph\Models\TelegraphChat;

class CallbackRegistry
{
    /** @var array<string, CallbackHandler> */
    private array $handlers = [];

    /**
     * Register a callback handler
     */
    public function register(CallbackHandler $handler): void
    {
        $this->handlers[$handler->getCallbackName()] = $handler;
    }

    /**
     * Register multiple callback handlers
     *
     * @param iterable<CallbackHandler> $handlers
     */
    public function registerMany(iterable $handlers): void
    {
        foreach ($handlers as $handler) {
            $this->register($handler);
        }
    }

    /**
     * Handle a callback
     *
     * @throws UnknownCommandException
     */
    public function handle(string $callback, TelegraphChat $chat, ?int $messageId = null): void
    {
        if (!isset($this->handlers[$callback])) {
            throw new UnknownCommandException("Unknown callback: {$callback}");
        }

        $this->handlers[$callback]->handle($chat, $messageId);
    }

    /**
     * Check if a callback is registered
     */
    public function has(string $callback): bool
    {
        return isset($this->handlers[$callback]);
    }

    /**
     * Get all registered callback names
     *
     * @return array<string>
     */
    public function getRegisteredCallbacks(): array
    {
        return array_keys($this->handlers);
    }
}
```

---

### Task 3.2: Create Main Menu Callback Handlers

**Priority**: HIGH
**Estimated Time**: 2 hours

**Files to Create**:
```
app/Telegram/Callbacks/MainMenu/ShowSettingsCallback.php
app/Telegram/Callbacks/MainMenu/ShowMeasurementsCallback.php
app/Telegram/Callbacks/MainMenu/ShowMacrosCallback.php
app/Telegram/Callbacks/MainMenu/ShowWeightCallback.php
app/Telegram/Callbacks/MainMenu/ShowHelpCallback.php
app/Telegram/Callbacks/MainMenu/MainMenuCallback.php
```

**Example Implementation** (ShowSettingsCallback.php):
```php
<?php

namespace App\Telegram\Callbacks\MainMenu;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use DefStudio\Telegraph\Models\TelegraphChat;

class ShowSettingsCallback implements CallbackHandler
{
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        $message = "⚙️ **Настройки**\n\n" .
            "Управление вашим аккаунтом и подключениями:\n\n" .
            "🔗 **Привязка аккаунта** - Управление связью Telegram с FitnessCoach\n" .
            "🔐 **FatSecret** - Подключение к FatSecret API\n" .
            "👤 **Профиль** - Ваши личные данные\n\n" .
            "Выберите раздел для управления:";

        $chat->edit($messageId)
            ->html($message)
            ->keyboard($this->keyboardFactory->settings())
            ->send();
    }

    public function getCallbackName(): string
    {
        return 'showSettings';
    }
}
```

**Pattern for Others**: Follow same structure, just change message content and keyboard.

---

### Task 3.3: Create Account Callback Handlers

**Priority**: HIGH
**Estimated Time**: 1.5 hours

**Files to Create**:
```
app/Telegram/Callbacks/Account/GenerateLinkCodeCallback.php
app/Telegram/Callbacks/Account/CheckLinkStatusCallback.php
app/Telegram/Callbacks/Account/RemoveLinkAccountCallback.php
app/Telegram/Callbacks/Account/AccountLinkingCallback.php
```

**Example** (GenerateLinkCodeCallback.php):
```php
<?php

namespace App\Telegram\Callbacks\Account;

use App\Telegram\Callbacks\Contracts\CallbackHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\TelegramAccountService;
use DefStudio\Telegraph\Models\TelegraphChat;

class GenerateLinkCodeCallback implements CallbackHandler
{
    public function __construct(
        private readonly TelegramAccountService $accountService,
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        $linkCode = $this->accountService->generateLinkAccountCode($chat->chat_id);

        $message = "🔗 **Код для привязки аккаунта**\n\n" .
            "Ваш код: `{$linkCode}`\n\n" .
            "⏰ Код действителен 15 минут\n" .
            "🌐 Используйте этот код в веб-интерфейсе для привязки аккаунта\n\n" .
            "⚠️ **Внимание:** При создании нового кода, старый перестает действовать";

        $chat->edit($messageId)
            ->html($message)
            ->keyboard($this->keyboardFactory->accountBack())
            ->send();
    }

    public function getCallbackName(): string
    {
        return 'generateLinkCode';
    }
}
```

---

### Task 3.4: Create FatSecret Callback Handlers

**Priority**: HIGH
**Estimated Time**: 1.5 hours

**Files to Create**:
```
app/Telegram/Callbacks/FatSecret/CheckConnectionCallback.php
app/Telegram/Callbacks/FatSecret/ConnectFatSecretCallback.php
app/Telegram/Callbacks/FatSecret/LogoutFatSecretCallback.php
app/Telegram/Callbacks/FatSecret/FatSecretConnectCallback.php
```

---

### Task 3.5: Create Sync Callback Handlers

**Priority**: MEDIUM
**Estimated Time**: 1 hour

**Files to Create**:
```
app/Telegram/Callbacks/Sync/SyncFullCallback.php
app/Telegram/Callbacks/Sync/SyncWeightCallback.php
app/Telegram/Callbacks/Sync/SyncFoodCallback.php
app/Telegram/Callbacks/Sync/ShowSyncCallback.php
```

**Note**: These will start conversations, so they bridge to conversation handlers.

---

### Task 3.6: Create Measurement/Weight/Macro Callback Initiators

**Priority**: MEDIUM
**Estimated Time**: 1 hour

**Files to Create**:
```
app/Telegram/Callbacks/Measurements/StartNewMeasurementCallback.php
app/Telegram/Callbacks/Weight/StartNewWeightCallback.php
app/Telegram/Callbacks/Macros/SelectMacroCaloriesCallback.php
app/Telegram/Callbacks/Macros/SelectMacroProteinsCallback.php
app/Telegram/Callbacks/Macros/SelectMacroFatsCallback.php
app/Telegram/Callbacks/Macros/SelectMacroCarbsCallback.php
```

---

### Task 3.7: Register All Callbacks in Service Provider

**Priority**: HIGH
**Estimated Time**: 30 minutes

**Update**: `app/Telegram/Providers/TelegramServiceProvider.php`

**Add**:
```php
use App\Telegram\Callbacks\CallbackRegistry;
// ... import all callback handlers

public function register(): void
{
    // ... existing command registry code

    // Bind callback registry as singleton
    $this->app->singleton(CallbackRegistry::class, function ($app) {
        $registry = new CallbackRegistry();

        // Register all callback handlers
        $registry->registerMany([
            // Main Menu
            $app->make(\App\Telegram\Callbacks\MainMenu\ShowSettingsCallback::class),
            $app->make(\App\Telegram\Callbacks\MainMenu\ShowMeasurementsCallback::class),
            $app->make(\App\Telegram\Callbacks\MainMenu\ShowMacrosCallback::class),
            $app->make(\App\Telegram\Callbacks\MainMenu\ShowWeightCallback::class),
            $app->make(\App\Telegram\Callbacks\MainMenu\ShowHelpCallback::class),
            $app->make(\App\Telegram\Callbacks\MainMenu\MainMenuCallback::class),

            // Account
            $app->make(\App\Telegram\Callbacks\Account\GenerateLinkCodeCallback::class),
            $app->make(\App\Telegram\Callbacks\Account\CheckLinkStatusCallback::class),
            $app->make(\App\Telegram\Callbacks\Account\RemoveLinkAccountCallback::class),
            $app->make(\App\Telegram\Callbacks\Account\AccountLinkingCallback::class),

            // FatSecret
            // ... etc
        ]);

        return $registry;
    });
}
```

---

### Task 3.8: Update Handler to Delegate Callbacks ✅ COMPLETED

**Priority**: HIGH
**Estimated Time**: 45 minutes → **Actual: 30 minutes**
**Status**: ✅ **COMPLETED** (October 27, 2025)

**Update**: `app/Telegram/Handlers/FitnessCoachWebhookHandler.php`

**Add to constructor**:
```php
private readonly CallbackRegistry $callbackRegistry,
```

**Override handleCallbackQuery for direct delegation** (SIMPLIFIED APPROACH):
```php
protected function handleCallbackQuery(): void
{
    // Use parent's method to extract all callback data
    // This sets: $this->messageId, $this->callbackQueryId, $this->data, $this->originalKeyboard
    parent::extractCallbackQueryData();

    /** @var string $action */
    $action = $this->callbackQuery?->data()->get('action') ?? '';

    // Delegate directly to CallbackRegistry - no magic methods needed!
    $this->callbackRegistry->handle($action, $this->chat, $this->messageId);
}
```

**Key Design Decision**:
- ✅ **Direct delegation** instead of `__call()` magic method
- ✅ **No code duplication** - uses parent's `extractCallbackQueryData()`
- ✅ **No magic methods** - completely transparent call path
- ✅ **Minimal override** - only 1 method (23 lines total)
- ✅ **Telegraph-compatible** - works WITH framework instead of against it

**Benefits Achieved**:
- 76% code reduction: 98 lines → 23 lines (original complex version had 98 lines)
- Zero magic methods (no `__call()`)
- Crystal clear delegation path
- Easy to debug and maintain

**Validation**:
- ✅ Laravel syntax check passed (`php artisan about`)
- ✅ No code duplication
- ✅ All callbacks delegated to registry
- ✅ Manual testing with bot

---

## Phase 4: Conversation Handlers Migration

**Duration**: 3-4 days
**Goal**: Extract conversation logic into strategy pattern with ConversationManager.

### Task 4.1: Create ConversationManager

**Priority**: HIGH
**Estimated Time**: 1.5 hours

**File**: `app/Telegram/Conversations/ConversationManager.php`

**Implementation**:
```php
<?php

namespace App\Telegram\Conversations;

use App\Models\User;
use App\Telegram\Conversations\Contracts\ConversationContext;
use App\Telegram\Conversations\Contracts\ConversationHandler;
use App\Telegram\Conversations\Contracts\ConversationResult;
use App\Telegram\Conversations\Contracts\ConversationStatus;
use App\Telegram\Exceptions\NoActiveConversationException;
use App\Telegram\Exceptions\NoHandlerForConversationTypeException;
use App\Telegram\Services\ConversationStateService;
use App\Telegram\Services\TelegramUserService;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Stringable;

class ConversationManager
{
    /**
     * @param ConversationStateService $stateService
     * @param TelegramUserService $userService
     * @param iterable<ConversationHandler> $handlers
     */
    public function __construct(
        private readonly ConversationStateService $stateService,
        private readonly TelegramUserService $userService,
        private readonly iterable $handlers,
    ) {}

    /**
     * Route incoming message to appropriate conversation handler
     *
     * @throws NoActiveConversationException
     * @throws NoHandlerForConversationTypeException
     */
    public function route(TelegraphChat $chat, Stringable $message): void
    {
        $chatId = (string) $chat->chat_id;

        if (!$this->stateService->isInConversation($chatId)) {
            throw new NoActiveConversationException();
        }

        $type = $this->stateService->getConversationType($chatId);
        $step = $this->stateService->getStep($chatId);
        $data = $this->stateService->getAllData($chatId);

        $handler = $this->findHandler($type);
        $user = $this->getUser($chat);
        $context = new ConversationContext($chatId, $user, $data);

        $result = $handler->handle($chat, $step, $message, $context);

        $this->processResult($chat, $chatId, $result);
    }

    /**
     * Start a new conversation
     */
    public function start(
        string $chatId,
        string $type,
        array $initialData = []
    ): void {
        $handler = $this->findHandler($type);

        $this->stateService->startConversation(
            $chatId,
            $type,
            $initialData
        );

        $this->stateService->setStep($chatId, $handler->getInitialStep());
    }

    /**
     * Find handler for conversation type
     *
     * @throws NoHandlerForConversationTypeException
     */
    private function findHandler(string $type): ConversationHandler
    {
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($type)) {
                return $handler;
            }
        }

        throw new NoHandlerForConversationTypeException("No handler for type: {$type}");
    }

    /**
     * Get user from chat
     */
    private function getUser(TelegraphChat $chat): User
    {
        return $this->userService->getCurrentUser($chat->chat_id);
    }

    /**
     * Process conversation result
     */
    private function processResult(
        TelegraphChat $chat,
        string $chatId,
        ConversationResult $result
    ): void {
        // Send message if provided
        if ($result->message) {
            $messageBuilder = $chat->html($result->message);

            if ($result->keyboard) {
                $messageBuilder->keyboard($result->keyboard);
            }

            $messageBuilder->send();
        }

        // Update state based on status
        match ($result->status) {
            ConversationStatus::Continue => $this->continueConversation($chatId, $result),
            ConversationStatus::Complete => $this->completeConversation($chatId),
            ConversationStatus::Error => $this->handleError($chatId, $result),
        };
    }

    /**
     * Continue conversation to next step
     */
    private function continueConversation(string $chatId, ConversationResult $result): void
    {
        if ($result->nextStep) {
            $this->stateService->setStep($chatId, $result->nextStep);
        }

        // Store any additional data
        foreach ($result->data as $key => $value) {
            $this->stateService->setData($chatId, $key, $value);
        }
    }

    /**
     * Complete conversation and cleanup
     */
    private function completeConversation(string $chatId): void
    {
        $this->stateService->endConversation($chatId);
    }

    /**
     * Handle conversation error
     */
    private function handleError(string $chatId, ConversationResult $result): void
    {
        // Stay in current step, error message already sent
        // Could log error here if needed
    }
}
```

---

### Task 4.2: Create MeasurementConversationHandler

**Priority**: HIGH
**Estimated Time**: 2 hours

**File**: `app/Telegram/Conversations/MeasurementConversationHandler.php`

**Implementation**: See TELEGRAM_BOT_ARCHITECTURE_PROPOSAL.md Example 2 (lines 1100-1208)

**Note**: This will need a `SaveMeasurement` action interface (create in Phase 5).

---

### Task 4.3: Create WeightConversationHandler

**Priority**: HIGH
**Estimated Time**: 2 hours

**File**: `app/Telegram/Conversations/WeightConversationHandler.php`

**Similar to MeasurementConversationHandler** but:
- Handle decimal separator (comma/dot)
- Range: 20-300 kg
- No measurement type needed

---

### Task 4.4: Create MacroConversationHandler

**Priority**: HIGH
**Estimated Time**: 2.5 hours

**File**: `app/Telegram/Conversations/MacroConversationHandler.php`

**Special handling**:
- Different ranges per macro type
- Calories: 500-5000
- Proteins/Fats/Carbs: 0-1000

---

### Task 4.5: Create SyncConversationHandler

**Priority**: MEDIUM
**Estimated Time**: 1.5 hours

**File**: `app/Telegram/Conversations/SyncConversationHandler.php`

**Special handling**:
- Only date input needed
- Auto-execute sync after date validation
- Different sync types: full, weight, food

---

### Task 4.6: Register Conversation Handlers in Service Provider

**Priority**: HIGH
**Estimated Time**: 30 minutes

**Update**: `app/Telegram/Providers/TelegramServiceProvider.php`

```php
use App\Telegram\Conversations\ConversationManager;
// ... import conversation handlers

public function register(): void
{
    // ... existing code

    // Bind conversation manager
    $this->app->singleton(ConversationManager::class, function ($app) {
        return new ConversationManager(
            $app->make(ConversationStateService::class),
            $app->make(TelegramUserService::class),
            [
                $app->make(MeasurementConversationHandler::class),
                $app->make(WeightConversationHandler::class),
                $app->make(MacroConversationHandler::class),
                $app->make(SyncConversationHandler::class),
            ]
        );
    });

    // Tag conversation handlers
    $this->app->tag([
        MeasurementConversationHandler::class,
        WeightConversationHandler::class,
        MacroConversationHandler::class,
        SyncConversationHandler::class,
    ], 'telegram.conversations');
}
```

---

### Task 4.7: Update Handler to Delegate Message Routing

**Priority**: HIGH
**Estimated Time**: 30 minutes

**Update**: `app/Telegram/Handlers/FitnessCoachWebhookHandler.php`

**Add to constructor**:
```php
private readonly ConversationManager $conversationManager,
```

**Replace handleChatMessage**:
```php
protected function handleChatMessage(Stringable $text): void
{
    try {
        $this->conversationManager->route($this->chat, $text);
    } catch (NoActiveConversationException $e) {
        $this->chat->html(
            "💬 Я понимаю только команды.\n\n" .
            "Используйте /help для списка доступных команд\n" .
            "или нажмите /start для главного меню."
        )->send();
    }
}

// OLD CODE - Keep commented
/*
protected function handleChatMessage(Stringable $text): void
{
    // ... old implementation
}

private function handleMeasurementConversation(...) { ... }
private function handleWeightConversation(...) { ... }
// etc.
*/
```

**Validation**:
- Test all 4 conversation types work identically
- Manual end-to-end testing

---

## Phase 5: Business Logic Integration

**Duration**: 2-3 days
**Goal**: Create Action interfaces and implementations, connect TODOs to actual persistence.

### Task 5.1: Create Action Interfaces

**Priority**: HIGH
**Estimated Time**: 1 hour

**Files to Create**:
```
app/Contracts/Actions/Telegram/SaveMeasurement.php
app/Contracts/Actions/Telegram/SaveWeight.php
app/Contracts/Actions/Telegram/SaveMacro.php
app/Contracts/Actions/Telegram/PerformFatSecretSync.php
```

**Example** (SaveMeasurement.php):
```php
<?php

namespace App\Contracts\Actions\Telegram;

use App\Models\Measurement; // Or your actual model

interface SaveMeasurement
{
    /**
     * Save a body measurement
     *
     * @param array $data ['user_id', 'type', 'value', 'date']
     * @return Measurement
     */
    public function handle(array $data): Measurement;
}
```

---

### Task 5.2: Create Action Implementations

**Priority**: HIGH
**Estimated Time**: 3-4 hours

**Files to Create**:
```
app/Actions/Telegram/SaveMeasurementAction.php
app/Actions/Telegram/SaveWeightAction.php
app/Actions/Telegram/SaveMacroAction.php
app/Actions/Telegram/PerformFatSecretSyncAction.php
```

**Example** (SaveMeasurementAction.php):
```php
<?php

namespace App\Actions\Telegram;

use App\Contracts\Actions\Telegram\SaveMeasurement;
use App\Models\Measurement;
use App\Models\User;
use Carbon\Carbon;

class SaveMeasurementAction implements SaveMeasurement
{
    public function handle(array $data): Measurement
    {
        // Validate data
        $validated = $this->validate($data);

        // Create measurement
        return Measurement::create([
            'user_id' => $validated['user_id'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'measured_at' => $validated['date'],
        ]);
    }

    private function validate(array $data): array
    {
        // Add validation logic
        // Could use Laravel validator here

        return [
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'value' => (float) $data['value'],
            'date' => Carbon::parse($data['date']),
        ];
    }
}
```

---

### Task 5.3: Bind Actions in Service Provider

**Priority**: HIGH
**Estimated Time**: 15 minutes

**Update**: `app/Telegram/Providers/TelegramServiceProvider.php` or main `AppServiceProvider`

```php
use App\Contracts\Actions\Telegram\SaveMeasurement;
use App\Actions\Telegram\SaveMeasurementAction;
// ... other imports

public function register(): void
{
    // ... existing code

    // Bind Action interfaces
    $this->app->bind(SaveMeasurement::class, SaveMeasurementAction::class);
    $this->app->bind(SaveWeight::class, SaveWeightAction::class);
    $this->app->bind(SaveMacro::class, SaveMacroAction::class);
    $this->app->bind(PerformFatSecretSync::class, PerformFatSecretSyncAction::class);
}
```

---

### Task 5.4: Update Conversation Handlers to Use Actions

**Priority**: HIGH
**Estimated Time**: 1 hour

**Update**: All conversation handler files

**Replace TODO comments with action calls**:

```php
// OLD
// TODO: Save to database
// $this->measurementService->saveMeasurement(...);

// NEW
$measurement = $this->saveMeasurementAction->handle([
    'user_id' => $context->user->id,
    'type' => $context->data['measurement_type'],
    'value' => $value,
    'date' => $context->data['date'],
]);
```

**Validation**:
- Test that data persists to database
- Verify all conversation flows save correctly

---

## Phase 6: Cleanup & Finalization

**Duration**: 1-2 days
**Goal**: Remove old code, optimize, document, test.

### Task 6.1: Remove Old Code from Handler

**Priority**: MEDIUM
**Estimated Time**: 1 hour

**Update**: `app/Telegram/Handlers/FitnessCoachWebhookHandler.php`

**Delete**:
- All old command methods (keep commented versions temporarily)
- All old callback methods
- All old conversation handler methods
- All old keyboard builder methods
- Old guard methods (now in middleware)

**Keep**:
- `onFailure()` error handler
- `handleChatMessage()` (now delegates)
- `__call()` magic method for callback delegation
- Command delegation methods

**Target**: Reduce from 1,406 lines to ~150-200 lines

---

### Task 6.2: Remove Deprecated Nutgram Files

**Priority**: LOW
**Estimated Time**: 30 minutes

**Delete directories**:
```
app/Telegram/Commands/ (old Nutgram commands)
app/Telegram/Conversations/ (old Nutgram conversations - EXCEPT new ones!)
app/Telegram/Menus/ (old Nutgram menus)
```

**Be careful**: Don't delete new conversation handlers!

**Mark deprecated**:
- `app/Telegram/Constants/CallbackData.php` - Add @deprecated tag

---

### Task 6.3: Write Unit Tests

**Priority**: MEDIUM
**Estimated Time**: 4-6 hours

**Create test files**:
```
tests/Unit/Telegram/Commands/StartCommandHandlerTest.php
tests/Unit/Telegram/Conversations/MeasurementConversationHandlerTest.php
tests/Unit/Telegram/Services/TelegramCommandRegistryTest.php
tests/Unit/Telegram/Services/ConversationManagerTest.php
```

**Example**: See TELEGRAM_BOT_ARCHITECTURE_PROPOSAL.md lines 1216-1352

**Goal**: >80% test coverage for new code

---

### Task 6.4: Update CLAUDE.md

**Priority**: MEDIUM
**Estimated Time**: 30 minutes

**Update**: `CLAUDE.md` Section 5 (Telegram Bot Architecture)

**Changes**:
- Remove "⚡ MIGRATED TO TELEGRAPH" note
- Update architecture description
- Document new patterns (Command, Strategy, Middleware)
- Update file locations
- Remove references to deprecated code

---

### Task 6.5: Create Developer Guide

**Priority**: LOW
**Estimated Time**: 2 hours

**Create**: `TELEGRAM_BOT_DEVELOPER_GUIDE.md`

**Contents**:
- Architecture overview
- How to add new command
- How to add new callback
- How to add new conversation
- How to add new middleware
- Testing guidelines
- Troubleshooting

---

### Task 6.6: Final Validation

**Priority**: HIGH
**Estimated Time**: 2-3 hours

**Checklist**:
- [ ] All commands work (`/start`, `/help`, `/account`, `/fatsecret`, `/sync`)
- [ ] All callbacks work (main menu, account, fatsecret, sync)
- [ ] All conversations work (measurement, weight, macro, sync)
- [ ] Data persists to database correctly
- [ ] No PHP errors or warnings
- [ ] `php artisan about` passes
- [ ] Test coverage >80%
- [ ] No deprecated code warnings
- [ ] Performance is acceptable (no slowdowns)

**Testing approach**:
1. Automated tests via PHPUnit
2. Manual testing with real Telegram bot
3. Verify ngrok webhook still works
4. Test error scenarios

---

## Rollback Plan

### If Issues Occur

**During Phase 2 (Commands)**:
1. Comment out command handler delegation in `FitnessCoachWebhookHandler`
2. Uncomment old command methods
3. Remove command registry from constructor

**During Phase 3 (Callbacks)**:
1. Remove `__call()` magic method
2. Uncomment old callback methods
3. Remove callback registry from constructor

**During Phase 4 (Conversations)**:
1. Comment out conversation manager delegation
2. Uncomment old conversation handler methods
3. Remove conversation manager from constructor

**Nuclear Option**: Restore entire handler from git:
```bash
git checkout HEAD -- app/Telegram/Handlers/FitnessCoachWebhookHandler.php
```

---

## Progress Tracking

### Phase 1: Foundation ✅ COMPLETED
- [x] Task 1.1: Create Interface Contracts
- [x] Task 1.2: Create KeyboardFactory Service
- [x] Task 1.3: Create MessageResponseBuilder Service
- [x] Task 1.4: Create TelegramCommandRegistry
- [x] Task 1.5: Create Middleware Infrastructure
- [x] Task 1.6: Create Additional Exceptions

**Status**: ✅ Complete (20 files, ~1,796 lines)
**Documentation**: `TELEGRAM_REFACTORING_PHASE1_CHANGELOG.md`

### Phase 2: Commands ✅ COMPLETED
- [x] Task 2.1: Create StartCommandHandler
- [x] Task 2.2: Create HelpCommandHandler
- [x] Task 2.3: Create AccountCommandHandler
- [x] Task 2.4: Create FatSecretCommandHandler
- [x] Task 2.5: Create SyncCommandHandler
- [x] Task 2.6: Register Commands in Service Provider
- [x] Task 2.7: Update Handler to Delegate Commands

**Status**: ✅ Complete (5 command handlers + registry, ~310 lines)
**Documentation**: `TELEGRAM_REFACTORING_PHASE2_CHANGELOG.md`

### Phase 3: Callbacks ✅ COMPLETED
- [x] Task 3.1: Create CallbackRegistry
- [x] Task 3.2: Create Main Menu Callbacks (6 handlers)
- [x] Task 3.3: Create Account Callbacks (4 handlers)
- [x] Task 3.4: Create FatSecret Callbacks (4 handlers)
- [x] Task 3.5: Create Sync Callbacks (4 handlers)
- [x] Task 3.6: Create Measurement/Weight/Macro Initiators (6 handlers)
- [x] Task 3.7: Register All Callbacks
- [x] Task 3.8: Update Handler to Delegate Callbacks

**Status**: ✅ Complete (25 callback handlers + registry, ~1,896 lines)
**Handler Reduction**: 98 → 23 lines (76% reduction in callback routing)
**Documentation**: `TELEGRAM_REFACTORING_PHASE3_CHANGELOG.md`

### Phase 4: Conversations ✅ COMPLETED
- [x] Task 4.1: Create ConversationManager
- [x] Task 4.2: Create MeasurementConversationHandler
- [x] Task 4.3: Create WeightConversationHandler
- [x] Task 4.4: Create MacroConversationHandler
- [x] Task 4.5: Create SyncConversationHandler
- [x] Task 4.6: Register Conversation Handlers
- [x] Task 4.7: Update Handler to Delegate Messages

**Status**: ✅ Complete (4 conversation handlers + manager, ~1,137 lines)
**Handler Reduction**: 870 → 447 lines (48.6% reduction)
**Total Handler Reduction**: 1,406 → 447 lines (68% reduction overall)
**Documentation**: `TELEGRAM_REFACTORING_PHASE4_CHANGELOG.md`

### Phase 5: Business Logic ⏳ NEXT
- [ ] Task 5.1: Create Action Interfaces
- [ ] Task 5.2: Create Action Implementations
- [ ] Task 5.3: Bind Actions in Service Provider
- [ ] Task 5.4: Update Handlers to Use Actions

**Status**: ⏳ Pending
**Estimated Duration**: 2-3 days

### Phase 6: Cleanup ⏳ FUTURE
- [ ] Task 6.1: Remove Old Code from Handler
- [ ] Task 6.2: Remove Deprecated Nutgram Files
- [ ] Task 6.3: Write Unit Tests
- [ ] Task 6.4: Update CLAUDE.md
- [ ] Task 6.5: Create Developer Guide
- [ ] Task 6.6: Final Validation

**Status**: ⏳ Pending
**Estimated Duration**: 1-2 days

---

## Execution Commands

### Common Commands During Implementation

```bash
# Check syntax
docker exec coach_fpm php artisan about

# Clear cache after changes
docker exec coach_fpm php artisan cache:clear

# Run tests
docker exec coach_fpm php artisan test

# Check webhook status
docker exec coach_fpm php artisan telescope:install

# IDE helper (after adding new classes)
docker exec coach_fpm php artisan ide-helper:generate
```

---

## Estimated Timeline

| Phase | Duration | Cumulative |
|-------|----------|------------|
| Phase 1: Foundation | 2-3 days | 3 days |
| Phase 2: Commands | 1-2 days | 5 days |
| Phase 3: Callbacks | 2-3 days | 8 days |
| Phase 4: Conversations | 3-4 days | 12 days |
| Phase 5: Business Logic | 2-3 days | 15 days |
| Phase 6: Cleanup | 1-2 days | 17 days |

**Total**: ~3-4 weeks with testing and validation

---

## Success Criteria

✅ **Handler reduced from 1,406 to ~150 lines**
✅ **All functionality works identically to before**
✅ **Test coverage >80%**
✅ **No performance degradation**
✅ **SOLID principles followed**
✅ **Zero deprecated code**
✅ **Comprehensive documentation**

---

**Next Step**: Begin with Phase 1, Task 1.1 - Create Interface Contracts
