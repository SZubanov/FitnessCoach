# Telegram Bot Refactoring - Phase 1 Technical Documentation

**Version**: 1.0
**Date**: 2025-10-16
**Status**: Phase 1 Complete ✅
**Audience**: Developers working on the FitnessCoach Telegram bot

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture Foundations](#architecture-foundations)
3. [Design Patterns](#design-patterns)
4. [Component Reference](#component-reference)
5. [Usage Examples](#usage-examples)
6. [Integration Guidelines](#integration-guidelines)
7. [Best Practices](#best-practices)
8. [Troubleshooting](#troubleshooting)

---

## Overview

### Purpose

Phase 1 establishes the foundational infrastructure for refactoring the monolithic `FitnessCoachWebhookHandler.php` (1,406 lines) into a maintainable, SOLID-compliant architecture. This documentation provides comprehensive guidance on using the Phase 1 infrastructure.

### Goals Achieved

- ✅ Interface-based programming with dependency injection
- ✅ Implementation of 6 core design patterns
- ✅ Elimination of code duplication (155 lines in keyboards alone)
- ✅ Composable middleware for authorization
- ✅ Consistent message formatting
- ✅ Type-safe conversation flow management

### File Structure

```
app/Telegram/
├── Callbacks/
│   └── Contracts/
│       └── CallbackHandler.php
├── Commands/
│   └── Contracts/
│       └── TelegramCommandHandler.php
├── Conversations/
│   └── Contracts/
│       ├── ConversationContext.php
│       ├── ConversationHandler.php
│       ├── ConversationResult.php
│       └── ConversationStatus.php
├── Exceptions/
│   ├── InvalidConversationStepException.php
│   ├── NoActiveConversationException.php
│   ├── NoHandlerForConversationTypeException.php
│   └── UnknownCommandException.php
├── Keyboards/
│   ├── KeyboardBuilder.php
│   └── KeyboardFactory.php
├── Middleware/
│   ├── Contracts/
│   │   └── TelegramMiddleware.php
│   ├── MiddlewarePipeline.php
│   ├── RequireAccountLinkMiddleware.php
│   └── RequireFatSecretAuthMiddleware.php
└── Services/
    ├── MessageResponseBuilder.php
    └── TelegramCommandRegistry.php
```

---

## Architecture Foundations

### SOLID Principles Applied

#### Single Responsibility Principle (S)

Each class has exactly one reason to change:

- **KeyboardFactory**: Only builds keyboards
- **MessageResponseBuilder**: Only formats messages
- **TelegramCommandRegistry**: Only manages command registration and dispatch
- **MiddlewarePipeline**: Only orchestrates middleware execution
- **Each Middleware**: Only validates one specific requirement

**Example**:
```php
// ✅ Single Responsibility - Only validates account linking
class RequireAccountLinkMiddleware implements TelegramMiddleware
{
    public function handle(TelegraphChat $chat, Closure $next): mixed
    {
        $user = $this->userService->getCurrentUser($chat->chat_id);

        if (!$user) {
            $this->sendNotLinkedMessage($chat);
            return null;
        }

        return $next($user);
    }
}
```

#### Open/Closed Principle (O)

Components are open for extension but closed for modification:

- Add new commands without modifying `TelegramCommandRegistry`
- Add new keyboards without changing `KeyboardFactory`
- Add new middleware without altering `MiddlewarePipeline`
- Add new conversation handlers without touching existing code

**Example**:
```php
// ✅ Open/Closed - Add new command without changing registry
$registry->register(new MyNewCommandHandler()); // Extension, not modification
```

#### Liskov Substitution Principle (L)

Any implementation can replace its interface without breaking behavior:

```php
// All implementations of TelegramCommandHandler can be used interchangeably
interface TelegramCommandHandler
{
    public function handle(TelegraphChat $chat): void;
    public function getCommandName(): string;
}

// Any of these can be passed to the registry
$registry->register(new StartCommandHandler());
$registry->register(new HelpCommandHandler());
$registry->register(new CustomCommandHandler());
```

#### Interface Segregation Principle (I)

Interfaces are focused and minimal:

- `TelegramCommandHandler`: Only 2 methods
- `CallbackHandler`: Only 2 methods
- `TelegramMiddleware`: Only 1 method
- `ConversationHandler`: Only 4 methods

No interface forces implementers to depend on methods they don't use.

#### Dependency Inversion Principle (D)

All components depend on abstractions (interfaces), not concrete implementations:

```php
// ✅ Depends on interface, not concrete class
class MyCommandHandler implements TelegramCommandHandler
{
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,  // Injected dependency
        private readonly TelegramUserService $userService,  // Injected dependency
    ) {}
}
```

### Architectural Layers

```
┌─────────────────────────────────────────────────────┐
│            Handler (Entry Point)                    │
│   FitnessCoachWebhookHandler.php                    │
└────────────────┬────────────────────────────────────┘
                 │
        ┌────────┴────────┐
        │                 │
┌───────▼─────────┐  ┌───▼────────────┐
│  Command Layer  │  │ Callback Layer │
│   (Commands)    │  │  (Callbacks)   │
└───────┬─────────┘  └───┬────────────┘
        │                │
        └────────┬───────┘
                 │
        ┌────────▼────────┐
        │ Middleware Layer│
        │   (Guards)      │
        └────────┬────────┘
                 │
        ┌────────▼────────┐
        │ Business Logic  │
        │   (Services)    │
        └────────┬────────┘
                 │
        ┌────────▼────────┐
        │  Presentation   │
        │ (Keyboards/Msg) │
        └─────────────────┘
```

---

## Design Patterns

### 1. Command Pattern

**Purpose**: Encapsulate command execution as objects

**Components**:
- Interface: `TelegramCommandHandler`
- Registry: `TelegramCommandRegistry`
- Implementations: Individual command handlers (Phase 2)

**How It Works**:

```php
// 1. Define the command interface
interface TelegramCommandHandler
{
    public function handle(TelegraphChat $chat): void;
    public function getCommandName(): string;
}

// 2. Implement specific commands
class StartCommandHandler implements TelegramCommandHandler
{
    public function handle(TelegraphChat $chat): void
    {
        // Command logic here
    }

    public function getCommandName(): string
    {
        return 'start';
    }
}

// 3. Register commands
$registry = new TelegramCommandRegistry();
$registry->register(new StartCommandHandler());
$registry->register(new HelpCommandHandler());

// 4. Execute commands
if ($registry->has('start')) {
    $registry->handle('start', $chat);
}
```

**Benefits**:
- ✅ Decouples command invocation from implementation
- ✅ Easy to add new commands
- ✅ Commands are testable in isolation
- ✅ O(1) command lookup

**When to Use**:
- Adding new `/command` handlers
- Creating callback handlers with similar pattern

---

### 2. Strategy Pattern

**Purpose**: Define a family of algorithms and make them interchangeable

**Components**:
- Interface: `ConversationHandler`
- Context: `ConversationContext` (DTO)
- Result: `ConversationResult` (DTO)
- Implementations: Conversation handlers for each flow type

**How It Works**:

```php
// 1. Define the strategy interface
interface ConversationHandler
{
    public function handle(
        TelegraphChat $chat,
        string $step,
        Stringable $message,
        ConversationContext $context
    ): ConversationResult;

    public function getType(): string;
    public function getInitialStep(): string;
    public function canHandle(string $type): bool;
}

// 2. Implement specific strategies
class WeightConversationHandler implements ConversationHandler
{
    public function handle(
        TelegraphChat $chat,
        string $step,
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        return match ($step) {
            'date_input' => $this->handleDateInput($message, $context),
            'value_input' => $this->handleValueInput($message, $context),
            default => throw InvalidConversationStepException::forStep($step, $this->getType()),
        };
    }

    public function getType(): string
    {
        return 'weight';
    }

    public function getInitialStep(): string
    {
        return 'date_input';
    }

    public function canHandle(string $type): bool
    {
        return $type === $this->getType();
    }
}

// 3. Use the strategy
$handler = $conversationManager->getHandler('weight');
$result = $handler->handle($chat, $step, $message, $context);
```

**Benefits**:
- ✅ Each conversation type is isolated
- ✅ Easy to add new conversation flows
- ✅ Testable conversation logic
- ✅ Polymorphic handling

**When to Use**:
- Creating multi-step conversation flows
- Different handling logic for different conversation types

---

### 3. Chain of Responsibility Pattern

**Purpose**: Pass requests through a chain of handlers

**Components**:
- Interface: `TelegramMiddleware`
- Pipeline: `MiddlewarePipeline`
- Middlewares: `RequireAccountLinkMiddleware`, `RequireFatSecretAuthMiddleware`

**How It Works**:

```php
// 1. Define middleware interface
interface TelegramMiddleware
{
    public function handle(TelegraphChat $chat, Closure $next): mixed;
}

// 2. Implement specific middleware
class RequireAccountLinkMiddleware implements TelegramMiddleware
{
    public function handle(TelegraphChat $chat, Closure $next): mixed
    {
        $user = $this->userService->getCurrentUser($chat->chat_id);

        if (!$user) {
            $this->sendNotLinkedMessage($chat);
            return null; // Stop the chain
        }

        return $next($user); // Continue the chain
    }
}

// 3. Build and execute pipeline
$pipeline = new MiddlewarePipeline([
    new RequireAccountLinkMiddleware($userService, $keyboardFactory),
    new RequireFatSecretAuthMiddleware($keyboardFactory),
]);

$result = $pipeline->through($chat, function ($user) {
    // Only reached if all middleware passes
    return $this->syncService->synchronize($user);
});
```

**Benefits**:
- ✅ Composable authorization logic
- ✅ Reusable middleware across commands
- ✅ Clear separation of concerns
- ✅ Can short-circuit execution

**When to Use**:
- Protecting commands/callbacks with authorization
- Validating preconditions before execution
- Composing multiple validation checks

---

### 4. Factory Pattern

**Purpose**: Centralize object creation

**Components**:
- Factory: `KeyboardFactory`

**How It Works**:

```php
// Factory provides predefined keyboards
class KeyboardFactory
{
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

    public function accountMenu(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('🔗 Привязать аккаунт')->action('linkAccount'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    // 11 more keyboard methods...
}

// Usage
$keyboard = $this->keyboardFactory->mainMenu();
$chat->message('Welcome!')->keyboard($keyboard)->send();
```

**Benefits**:
- ✅ Single source of truth for keyboards
- ✅ Eliminates 155 lines of duplication
- ✅ Consistent button labels and actions
- ✅ Easy to update keyboard layouts

**When to Use**:
- Sending predefined keyboards
- Ensuring consistent UI across handlers

---

### 5. Builder Pattern

**Purpose**: Construct complex objects step-by-step with fluent API

**Components**:
- `MessageResponseBuilder` - Build formatted messages
- `KeyboardBuilder` - Build dynamic keyboards

**How It Works**:

```php
// Message Builder
$message = MessageResponseBuilder::create()
    ->greeting('FitnessCoach')
    ->blank()
    ->addFeatures(['weight', 'measurements', 'macros', 'sync'])
    ->blank()
    ->addInstructions('Начните с команды /start')
    ->addCommands([
        '/account' => 'Привязать аккаунт',
        '/fatsecret' => 'Подключить FatSecret',
        '/help' => 'Помощь',
    ])
    ->build();

// Keyboard Builder
$keyboard = KeyboardBuilder::create()
    ->addButton('📊 Статистика', 'showStats')
    ->addButton('📈 График', 'showChart')
    ->addMainMenuButton()
    ->inColumns(2)
    ->build();
```

**Benefits**:
- ✅ Expressive, readable code
- ✅ Consistent formatting
- ✅ Flexible composition
- ✅ Method chaining

**When to Use**:
- Building complex messages with multiple sections
- Creating dynamic keyboards at runtime
- Need for consistent formatting

---

### 6. Registry Pattern

**Purpose**: Central registry for component lookup

**Components**:
- `TelegramCommandRegistry` - Register and lookup commands

**How It Works**:

```php
class TelegramCommandRegistry
{
    private array $handlers = [];

    public function register(TelegramCommandHandler $handler): void
    {
        $commandName = $handler->getCommandName();
        $this->handlers[$commandName] = $handler;
    }

    public function handle(string $command, TelegraphChat $chat): void
    {
        if (!$this->has($command)) {
            throw UnknownCommandException::forCommand($command);
        }

        $this->handlers[$command]->handle($chat);
    }

    public function has(string $command): bool
    {
        return isset($this->handlers[$command]);
    }
}

// Usage
$registry = new TelegramCommandRegistry();
$registry->register(new StartCommandHandler());

if ($registry->has('start')) {
    $registry->handle('start', $chat);
}
```

**Benefits**:
- ✅ O(1) lookup time
- ✅ Decoupled registration from execution
- ✅ Easy to list available commands
- ✅ Clear error handling

**When to Use**:
- Managing collections of handlers
- Need for fast lookup
- Dynamic handler registration

---

### 7. DTO (Data Transfer Object) Pattern

**Purpose**: Immutable data containers

**Components**:
- `ConversationContext` - Conversation state
- `ConversationResult` - Conversation outcome

**How It Works**:

```php
// Immutable context
class ConversationContext
{
    public function __construct(
        public readonly string $chatId,
        public readonly User $user,
        public readonly array $data = [],
    ) {}
}

// Immutable result with static factories
class ConversationResult
{
    public function __construct(
        public readonly ConversationStatus $status,
        public readonly ?string $nextStep = null,
        public readonly ?string $message = null,
        public readonly ?Keyboard $keyboard = null,
        public readonly array $data = [],
    ) {}

    public static function continue(string $nextStep, string $message, array $data = []): self
    {
        return new self(
            status: ConversationStatus::Continue,
            nextStep: $nextStep,
            message: $message,
            data: $data
        );
    }

    public static function complete(string $message, ?Keyboard $keyboard = null): self
    {
        return new self(
            status: ConversationStatus::Complete,
            message: $message,
            keyboard: $keyboard
        );
    }
}

// Usage
$context = new ConversationContext(
    chatId: $chat->chat_id,
    user: $user,
    data: ['previous_weight' => 75.5]
);

$result = ConversationResult::continue(
    nextStep: 'value_input',
    message: 'Введите ваш вес',
    data: ['date' => '2025-10-16']
);
```

**Benefits**:
- ✅ Immutable data structures
- ✅ Type-safe data passing
- ✅ Clear data contracts
- ✅ Expressive static factories

**When to Use**:
- Passing data between layers
- Representing conversation state
- Returning complex results

---

## Component Reference

### TelegramCommandRegistry

**Location**: `app/Telegram/Services/TelegramCommandRegistry.php`
**Purpose**: Manage command registration and dispatch

#### Methods

```php
// Register a command handler
public function register(TelegramCommandHandler $handler): void

// Execute a registered command
public function handle(string $command, TelegraphChat $chat): void

// Check if command is registered
public function has(string $command): bool

// Get all registered command names
public function getRegisteredCommands(): array
```

#### Usage Example

```php
use App\Telegram\Services\TelegramCommandRegistry;
use App\Telegram\Commands\StartCommandHandler;

$registry = new TelegramCommandRegistry();

// Register commands
$registry->register(new StartCommandHandler($keyboardFactory));
$registry->register(new HelpCommandHandler($keyboardFactory));

// Check and execute
if ($registry->has('start')) {
    $registry->handle('start', $chat);
}

// List all commands
$commands = $registry->getRegisteredCommands();
// Returns: ['start', 'help']
```

#### Error Handling

```php
try {
    $registry->handle('unknown', $chat);
} catch (UnknownCommandException $e) {
    // Handle unknown command
    // Message: "Unknown command: /unknown"
}
```

---

### KeyboardFactory

**Location**: `app/Telegram/Keyboards/KeyboardFactory.php`
**Purpose**: Centralized keyboard construction

#### Available Keyboards

```php
// Main application menu
public function mainMenu(): Keyboard

// Help menu with back button
public function help(): Keyboard

// Account linking flow
public function accountLinking(): Keyboard
public function accountMenu(): Keyboard
public function accountBack(): Keyboard

// FatSecret integration
public function fatSecretMenu(): Keyboard
public function fatSecretBack(): Keyboard

// Synchronization
public function syncMenu(): Keyboard
public function syncBack(): Keyboard

// Feature-specific
public function settings(): Keyboard
public function measurements(): Keyboard
public function macros(): Keyboard
public function weight(): Keyboard
```

#### Usage Example

```php
use App\Telegram\Keyboards\KeyboardFactory;

class MyCommandHandler implements TelegramCommandHandler
{
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    public function handle(TelegraphChat $chat): void
    {
        $message = 'Выберите действие:';

        $chat->message($message)
            ->keyboard($this->keyboardFactory->mainMenu())
            ->send();
    }
}
```

#### Keyboard Structure Examples

```php
// mainMenu() generates:
⚙️ Настройки  📏 Замеры
🔄 Синхронизация  🍎 КБЖУ
⚖️ Вес  ❓ Помощь

// accountMenu() generates:
🔗 Привязать аккаунт
🏠 Главное меню
```

---

### KeyboardBuilder

**Location**: `app/Telegram/Keyboards/KeyboardBuilder.php`
**Purpose**: Build dynamic keyboards with fluent API

#### Methods

```php
// Create new builder
public static function create(): self

// Add custom button
public function addButton(string $text, string $action): self

// Add predefined buttons
public function addBackButton(string $action = 'goBack'): self
public function addMainMenuButton(): self

// Set column layout
public function inColumns(int $columns): self

// Build final keyboard
public function build(): Keyboard
```

#### Usage Example

```php
use App\Telegram\Keyboards\KeyboardBuilder;

// Build keyboard dynamically based on user data
$keyboard = KeyboardBuilder::create()
    ->addButton('📊 Статистика', 'showStats')
    ->addButton('📈 График', 'showChart')
    ->addButton('📋 История', 'showHistory');

// Conditionally add button
if ($user->isFatSecretAuthorized()) {
    $keyboard->addButton('🔄 Синхронизация', 'syncNow');
}

// Set layout and build
$keyboard = $keyboard
    ->addMainMenuButton()
    ->inColumns(2)
    ->build();

$chat->message('Выберите:')->keyboard($keyboard)->send();
```

---

### MessageResponseBuilder

**Location**: `app/Telegram/Services/MessageResponseBuilder.php`
**Purpose**: Build formatted messages with fluent API

#### Core Methods

```php
// Create builder instance
public static function create(): self

// Basic components
public function icon(string $icon): self
public function title(string $title): self
public function text(string $text): self
public function blank(): self

// Status messages
public function success(string $message): self
public function error(string $message): self
public function warning(string $message): self
public function info(string $message): self

// Structured content
public function bulletList(array $items): self
public function numberedList(array $items): self
public function addField(string $label, string $value): self
public function addSection(string $title, array $items): self

// User-facing
public function greeting(string $appName): self
public function addFeatures(array $features): self
public function addInstructions(string $instructions): self
public function addCommands(array $commands): self

// Build final message
public function build(): string
```

#### Usage Examples

##### Simple Message

```php
$message = MessageResponseBuilder::create()
    ->title('Добро пожаловать')
    ->text('Выберите действие из меню')
    ->build();

// Output:
// **Добро пожаловать**
// Выберите действие из меню
```

##### Status Message

```php
$message = MessageResponseBuilder::create()
    ->success('Данные успешно сохранены')
    ->addInstructions('Используйте /stats для просмотра статистики')
    ->build();

// Output:
// ✅ Данные успешно сохранены
//
// Используйте /stats для просмотра статистики
```

##### Complex Message

```php
$message = MessageResponseBuilder::create()
    ->greeting('FitnessCoach')
    ->blank()
    ->addFeatures(['weight', 'measurements', 'macros', 'sync'])
    ->blank()
    ->addCommands([
        '/account' => 'Привязать аккаунт',
        '/fatsecret' => 'Подключить FatSecret',
        '/help' => 'Помощь',
    ])
    ->build();

// Output:
// 🎯 **Добро пожаловать в FitnessCoach!**
//
// Я помогу вам отслеживать:
// ⚖️ Вес и измерения тела
// 📏 Замеры тела
// 🍎 Макронутриенты (КБЖУ)
// 🔄 Синхронизацию с FatSecret
//
// Доступные команды:
// /account - Привязать аккаунт
// /fatsecret - Подключить FatSecret
// /help - Помощь
```

##### Field-Based Message

```php
$message = MessageResponseBuilder::create()
    ->title('Ваши данные')
    ->addField('Вес', '75.5 кг')
    ->addField('Дата', '16.10.2025')
    ->addField('ИМТ', '23.2')
    ->build();

// Output:
// **Ваши данные**
// **Вес**: 75.5 кг
// **Дата**: 16.10.2025
// **ИМТ**: 23.2
```

---

### MiddlewarePipeline

**Location**: `app/Telegram/Middleware/MiddlewarePipeline.php`
**Purpose**: Execute middleware chain

#### Methods

```php
// Create pipeline with middleware array
public function __construct(array $middleware = [])

// Execute pipeline
public function through(TelegraphChat $chat, Closure $destination): mixed
```

#### Usage Example

```php
use App\Telegram\Middleware\MiddlewarePipeline;
use App\Telegram\Middleware\RequireAccountLinkMiddleware;
use App\Telegram\Middleware\RequireFatSecretAuthMiddleware;

// Build pipeline
$pipeline = new MiddlewarePipeline([
    new RequireAccountLinkMiddleware($userService, $keyboardFactory),
    new RequireFatSecretAuthMiddleware($keyboardFactory),
]);

// Execute with destination
$result = $pipeline->through($chat, function ($user) {
    // Only reached if:
    // 1. User is linked to account ✅
    // 2. User has FatSecret authorized ✅

    return $this->syncService->synchronize($user);
});

// If any middleware fails (returns null), $result will be null
if ($result === null) {
    // Middleware stopped execution (sent error message to user)
    return;
}

// Continue with result
$chat->message("Sync completed: {$result->status}")->send();
```

#### Execution Flow

```
┌──────────────────────────────────┐
│  Pipeline Start                  │
└────────────┬─────────────────────┘
             │
             ▼
┌──────────────────────────────────┐
│  RequireAccountLinkMiddleware    │
│  ✅ Pass → User                  │
│  ❌ Fail → null (stop)           │
└────────────┬─────────────────────┘
             │ (if passed)
             ▼
┌──────────────────────────────────┐
│  RequireFatSecretAuthMiddleware  │
│  ✅ Pass → User                  │
│  ❌ Fail → null (stop)           │
└────────────┬─────────────────────┘
             │ (if passed)
             ▼
┌──────────────────────────────────┐
│  Destination Closure             │
│  Execute business logic          │
└──────────────────────────────────┘
```

---

### RequireAccountLinkMiddleware

**Location**: `app/Telegram/Middleware/RequireAccountLinkMiddleware.php`
**Purpose**: Guard requiring account linking

#### Behavior

- ✅ **Passes**: If Telegram account is linked to FitnessCoach User
- ❌ **Fails**: If no linked account found

#### Message on Failure

```
❌ Аккаунт не привязан.

Используйте /account для привязки аккаунта.

[🔗 Привязать аккаунт] [🏠 Главное меню]
```

#### Usage

```php
// Use in pipeline for protected actions
$pipeline = new MiddlewarePipeline([
    new RequireAccountLinkMiddleware($userService, $keyboardFactory),
]);

$result = $pipeline->through($chat, function ($user) {
    // $user is guaranteed to exist here
    return $this->saveWeight($user, $weight);
});
```

---

### RequireFatSecretAuthMiddleware

**Location**: `app/Telegram/Middleware/RequireFatSecretAuthMiddleware.php`
**Purpose**: Guard requiring FatSecret OAuth authorization

#### Behavior

- ✅ **Passes**: If user has authorized FatSecret integration
- ❌ **Fails**: If FatSecret not connected

#### Message on Failure

```
❌ FatSecret не подключен.

Используйте /fatsecret для подключения.

[🔗 Подключить FatSecret] [⬅️ Назад]
```

#### Usage

```php
// Typically used after RequireAccountLinkMiddleware
$pipeline = new MiddlewarePipeline([
    new RequireAccountLinkMiddleware($userService, $keyboardFactory),
    new RequireFatSecretAuthMiddleware($keyboardFactory),  // Second guard
]);

$result = $pipeline->through($chat, function ($user) {
    // User exists AND has FatSecret authorized
    return $this->fatSecretService->synchronize($user);
});
```

---

### Exception Classes

#### UnknownCommandException

**Location**: `app/Telegram/Exceptions/UnknownCommandException.php`
**Purpose**: Thrown when command not registered

```php
// Static factory
UnknownCommandException::forCommand('unknown')
// Message: "Unknown command: /unknown"

// Usage in registry
if (!$this->has($command)) {
    throw UnknownCommandException::forCommand($command);
}
```

#### NoActiveConversationException

**Location**: `app/Telegram/Exceptions/NoActiveConversationException.php`
**Purpose**: Thrown when routing message to non-existent conversation

```php
// Static factory
NoActiveConversationException::forChat('123456789')
// Message: "No active conversation found for chat: 123456789"

// Usage in conversation router
if (!$this->hasActiveConversation($chatId)) {
    throw NoActiveConversationException::forChat($chatId);
}
```

#### InvalidConversationStepException

**Location**: `app/Telegram/Exceptions/InvalidConversationStepException.php`
**Purpose**: Thrown when conversation receives unknown step

```php
// Static factory
InvalidConversationStepException::forStep('invalid_step', 'weight')
// Message: "Invalid conversation step: 'invalid_step' in conversation type: 'weight'"

// Usage in conversation handler
return match ($step) {
    'date_input' => $this->handleDateInput($message, $context),
    'value_input' => $this->handleValueInput($message, $context),
    default => throw InvalidConversationStepException::forStep($step, $this->getType()),
};
```

#### NoHandlerForConversationTypeException

**Location**: `app/Telegram/Exceptions/NoHandlerForConversationTypeException.php`
**Purpose**: Thrown when conversation handler not found

```php
// Static factory with type only
NoHandlerForConversationTypeException::forType('unknown_type')
// Message: "No handler found for conversation type: 'unknown_type'"

// Static factory with available types
NoHandlerForConversationTypeException::withAvailableTypes('unknown', ['weight', 'measurement', 'macro'])
// Message: "No handler found for conversation type: 'unknown'. Available types: weight, measurement, macro"

// Usage in conversation manager
if (!$this->hasHandler($type)) {
    throw NoHandlerForConversationTypeException::withAvailableTypes(
        $type,
        $this->getAvailableTypes()
    );
}
```

---

## Usage Examples

### Example 1: Creating a Simple Command

```php
<?php

namespace App\Telegram\Commands;

use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\MessageResponseBuilder;
use DefStudio\Telegraph\Models\TelegraphChat;

class AboutCommandHandler implements TelegramCommandHandler
{
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    public function handle(TelegraphChat $chat): void
    {
        $message = MessageResponseBuilder::create()
            ->title('О FitnessCoach')
            ->text('Приложение для отслеживания здоровья и фитнеса.')
            ->blank()
            ->bulletList([
                'Отслеживание веса',
                'Замеры тела',
                'КБЖУ и питание',
                'Синхронизация с FatSecret',
            ])
            ->blank()
            ->info('Версия: 1.0')
            ->build();

        $chat->message($message)
            ->keyboard($this->keyboardFactory->help())
            ->send();
    }

    public function getCommandName(): string
    {
        return 'about';
    }
}
```

**Registration** (in service provider):
```php
$registry->register(new AboutCommandHandler($keyboardFactory));
```

---

### Example 2: Creating a Protected Command with Middleware

```php
<?php

namespace App\Telegram\Commands;

use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Middleware\MiddlewarePipeline;
use App\Telegram\Middleware\RequireAccountLinkMiddleware;
use App\Telegram\Services\MessageResponseBuilder;
use App\Telegram\Services\TelegramUserService;
use DefStudio\Telegraph\Models\TelegraphChat;

class ProfileCommandHandler implements TelegramCommandHandler
{
    public function __construct(
        private readonly TelegramUserService $userService,
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    public function handle(TelegraphChat $chat): void
    {
        $pipeline = new MiddlewarePipeline([
            new RequireAccountLinkMiddleware($this->userService, $this->keyboardFactory),
        ]);

        $result = $pipeline->through($chat, function ($user) use ($chat) {
            $message = MessageResponseBuilder::create()
                ->title('Ваш профиль')
                ->addField('Имя', $user->name)
                ->addField('Email', $user->email)
                ->addField('Telegram', $chat->chat_id)
                ->blank()
                ->info('FatSecret: ' . ($user->isFatSecretAuthorized() ? '✅ Подключен' : '❌ Не подключен'))
                ->build();

            $chat->message($message)
                ->keyboard($this->keyboardFactory->accountMenu())
                ->send();

            return true;
        });

        // Middleware handles sending error messages if needed
    }

    public function getCommandName(): string
    {
        return 'profile';
    }
}
```

---

### Example 3: Creating a Conversation Handler

```php
<?php

namespace App\Telegram\Conversations;

use App\Models\User;
use App\Telegram\Conversations\Contracts\ConversationContext;
use App\Telegram\Conversations\Contracts\ConversationHandler;
use App\Telegram\Conversations\Contracts\ConversationResult;
use App\Telegram\Exceptions\InvalidConversationStepException;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\DateValidationService;
use App\Telegram\Services\MessageResponseBuilder;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Stringable;

class WeightConversationHandler implements ConversationHandler
{
    public function __construct(
        private readonly DateValidationService $dateValidation,
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    public function handle(
        TelegraphChat $chat,
        string $step,
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        return match ($step) {
            'date_input' => $this->handleDateInput($message, $context),
            'value_input' => $this->handleValueInput($message, $context, $chat),
            default => throw InvalidConversationStepException::forStep($step, $this->getType()),
        };
    }

    private function handleDateInput(Stringable $message, ConversationContext $context): ConversationResult
    {
        $dateResult = $this->dateValidation->validate($message->toString());

        if (!$dateResult['valid']) {
            $errorMessage = MessageResponseBuilder::create()
                ->error($dateResult['error'])
                ->build();

            return ConversationResult::error($errorMessage);
        }

        $message = MessageResponseBuilder::create()
            ->title('Введите вес')
            ->text('Введите значение веса в кг (например: 75.5)')
            ->build();

        return ConversationResult::continue(
            nextStep: 'value_input',
            message: $message,
            data: array_merge($context->data, ['date' => $dateResult['date']])
        );
    }

    private function handleValueInput(
        Stringable $message,
        ConversationContext $context,
        TelegraphChat $chat
    ): ConversationResult {
        $weight = (float) $message->toString();

        if ($weight <= 0 || $weight > 300) {
            $errorMessage = MessageResponseBuilder::create()
                ->error('Некорректное значение веса. Введите число от 1 до 300.')
                ->build();

            return ConversationResult::error($errorMessage);
        }

        // Save weight
        $this->saveWeight($context->user, $context->data['date'], $weight);

        $successMessage = MessageResponseBuilder::create()
            ->success("Вес {$weight} кг сохранен для {$context->data['date']}")
            ->build();

        return ConversationResult::complete(
            message: $successMessage,
            keyboard: $this->keyboardFactory->mainMenu()
        );
    }

    private function saveWeight(User $user, string $date, float $weight): void
    {
        // Implementation here
    }

    public function getType(): string
    {
        return 'weight';
    }

    public function getInitialStep(): string
    {
        return 'date_input';
    }

    public function canHandle(string $type): bool
    {
        return $type === $this->getType();
    }
}
```

---

### Example 4: Dynamic Keyboard with Builder

```php
<?php

namespace App\Telegram\Commands;

use App\Models\User;
use App\Telegram\Commands\Contracts\TelegramCommandHandler;
use App\Telegram\Keyboards\KeyboardBuilder;
use App\Telegram\Services\MessageResponseBuilder;
use DefStudio\Telegraph\Models\TelegraphChat;

class StatsCommandHandler implements TelegramCommandHandler
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function handle(TelegraphChat $chat): void
    {
        // Build keyboard dynamically based on user data
        $keyboard = KeyboardBuilder::create()
            ->addButton('📊 Статистика по весу', 'statsWeight');

        // Add measurements button if user has measurements
        if ($this->user->measurements()->exists()) {
            $keyboard->addButton('📏 Статистика по замерам', 'statsMeasurements');
        }

        // Add nutrition button if user has nutrition data
        if ($this->user->nutritionEntries()->exists()) {
            $keyboard->addButton('🍎 Статистика по КБЖУ', 'statsMacros');
        }

        // Add FatSecret sync if authorized
        if ($this->user->isFatSecretAuthorized()) {
            $keyboard->addButton('🔄 Синхронизировать', 'syncNow');
        }

        // Always add main menu
        $keyboard = $keyboard
            ->addMainMenuButton()
            ->inColumns(2)
            ->build();

        $message = MessageResponseBuilder::create()
            ->title('Статистика')
            ->text('Выберите тип статистики:')
            ->build();

        $chat->message($message)
            ->keyboard($keyboard)
            ->send();
    }

    public function getCommandName(): string
    {
        return 'stats';
    }
}
```

---

## Integration Guidelines

### Service Provider Registration

Create a dedicated service provider for Telegram bot components:

```php
<?php

namespace App\Providers;

use App\Telegram\Commands\AboutCommandHandler;
use App\Telegram\Commands\HelpCommandHandler;
use App\Telegram\Commands\ProfileCommandHandler;
use App\Telegram\Commands\StartCommandHandler;
use App\Telegram\Keyboards\KeyboardFactory;
use App\Telegram\Services\TelegramCommandRegistry;
use App\Telegram\Services\TelegramUserService;
use Illuminate\Support\ServiceProvider;

class TelegramBotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind factories and services as singletons
        $this->app->singleton(KeyboardFactory::class);
        $this->app->singleton(TelegramCommandRegistry::class);
    }

    public function boot(): void
    {
        // Register all command handlers
        $registry = $this->app->make(TelegramCommandRegistry::class);
        $keyboardFactory = $this->app->make(KeyboardFactory::class);
        $userService = $this->app->make(TelegramUserService::class);

        $registry->register(new StartCommandHandler($keyboardFactory));
        $registry->register(new HelpCommandHandler($keyboardFactory));
        $registry->register(new AboutCommandHandler($keyboardFactory));
        $registry->register(new ProfileCommandHandler($userService, $keyboardFactory));
    }
}
```

Register in `config/app.php`:
```php
'providers' => [
    // ...
    App\Providers\TelegramBotServiceProvider::class,
],
```

---

### Using in Webhook Handler

Update `FitnessCoachWebhookHandler.php` to use the registry:

```php
<?php

namespace App\Telegram\Handlers;

use App\Telegram\Services\TelegramCommandRegistry;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use Illuminate\Support\Stringable;

class FitnessCoachWebhookHandler extends WebhookHandler
{
    public function __construct(
        private readonly TelegramCommandRegistry $commandRegistry,
    ) {}

    /**
     * Handle /command messages
     */
    protected function handleChatMessage(Stringable $text): void
    {
        // Extract command from message
        if (!$text->startsWith('/')) {
            // Route to conversation handler (Phase 4)
            return;
        }

        $command = $text->after('/')->before(' ')->toString();

        // Use registry to handle command
        if ($this->commandRegistry->has($command)) {
            $this->commandRegistry->handle($command, $this->chat);
            return;
        }

        // Unknown command
        $this->chat->message("Неизвестная команда: /{$command}\\n\\nИспользуйте /help для списка команд.")
            ->send();
    }
}
```

---

### Dependency Injection

All components are designed for constructor injection:

```php
class MyCommandHandler implements TelegramCommandHandler
{
    public function __construct(
        private readonly KeyboardFactory $keyboardFactory,
        private readonly TelegramUserService $userService,
        private readonly SomeOtherService $otherService,
    ) {}

    public function handle(TelegraphChat $chat): void
    {
        // All dependencies are available
        $user = $this->userService->getCurrentUser($chat->chat_id);
        $keyboard = $this->keyboardFactory->mainMenu();
        // ...
    }
}
```

Register in service provider:
```php
$registry->register(
    new MyCommandHandler(
        $this->app->make(KeyboardFactory::class),
        $this->app->make(TelegramUserService::class),
        $this->app->make(SomeOtherService::class)
    )
);
```

---

## Best Practices

### 1. Always Use Interfaces

✅ **Do**:
```php
class MyHandler implements TelegramCommandHandler
{
    // Implementation
}
```

❌ **Don't**:
```php
class MyHandler  // No interface
{
    // Implementation
}
```

### 2. Use Static Factories for DTOs

✅ **Do**:
```php
return ConversationResult::continue(
    nextStep: 'value_input',
    message: 'Enter value',
    data: ['date' => '2025-10-16']
);
```

❌ **Don't**:
```php
return new ConversationResult(
    status: ConversationStatus::Continue,
    nextStep: 'value_input',
    message: 'Enter value',
    data: ['date' => '2025-10-16']
);
```

### 3. Use MessageResponseBuilder for Consistency

✅ **Do**:
```php
$message = MessageResponseBuilder::create()
    ->success('Operation completed')
    ->build();
```

❌ **Don't**:
```php
$message = "✅ Operation completed";  // Manual formatting
```

### 4. Use KeyboardFactory for Predefined Keyboards

✅ **Do**:
```php
$keyboard = $this->keyboardFactory->mainMenu();
```

❌ **Don't**:
```php
$keyboard = Keyboard::make()->buttons([
    Button::make('⚙️ Настройки')->action('showSettings'),
    // Duplicated code
]);
```

### 5. Compose Middleware in Pipelines

✅ **Do**:
```php
$pipeline = new MiddlewarePipeline([
    new RequireAccountLinkMiddleware($userService, $keyboardFactory),
    new RequireFatSecretAuthMiddleware($keyboardFactory),
]);

$result = $pipeline->through($chat, fn($user) => $this->action($user));
```

❌ **Don't**:
```php
// Nested if-else chains
if ($this->requireAccountLink($chat)) {
    if ($this->requireFatSecret($chat)) {
        $this->action($user);
    }
}
```

### 6. Use Type Hints Everywhere

✅ **Do**:
```php
public function handle(TelegraphChat $chat): void
{
    // Implementation
}
```

❌ **Don't**:
```php
public function handle($chat)  // No type hints
{
    // Implementation
}
```

### 7. Keep Handlers Focused

✅ **Do**:
```php
// One responsibility per handler
class StartCommandHandler implements TelegramCommandHandler
{
    public function handle(TelegraphChat $chat): void
    {
        // Only handle /start command
    }
}
```

❌ **Don't**:
```php
class MultiCommandHandler  // Handles multiple commands
{
    public function handleStart() { }
    public function handleHelp() { }
    public function handleAbout() { }
}
```

### 8. Use Dependency Injection

✅ **Do**:
```php
public function __construct(
    private readonly KeyboardFactory $keyboardFactory,
    private readonly TelegramUserService $userService,
) {}
```

❌ **Don't**:
```php
public function handle(TelegraphChat $chat): void
{
    $factory = new KeyboardFactory();  // Direct instantiation
    $service = new TelegramUserService();
}
```

---

## Troubleshooting

### Problem: UnknownCommandException

**Error**: `Unknown command: /mycommand`

**Cause**: Command handler not registered in `TelegramCommandRegistry`

**Solution**:
```php
// In TelegramBotServiceProvider::boot()
$registry->register(new MyCommandHandler($keyboardFactory));
```

---

### Problem: Middleware Not Executing

**Symptom**: Protected actions execute without authorization checks

**Cause**: Middleware not added to pipeline

**Solution**:
```php
// Add middleware to pipeline
$pipeline = new MiddlewarePipeline([
    new RequireAccountLinkMiddleware($userService, $keyboardFactory),
]);

$result = $pipeline->through($chat, $destination);
```

---

### Problem: KeyboardFactory Method Not Found

**Error**: `Call to undefined method KeyboardFactory::myCustomKeyboard()`

**Cause**: Custom keyboard not defined in factory

**Solution**:
```php
// Option 1: Add to KeyboardFactory
public function myCustomKeyboard(): Keyboard
{
    return Keyboard::make()->buttons([
        // Buttons here
    ]);
}

// Option 2: Use KeyboardBuilder for dynamic keyboards
$keyboard = KeyboardBuilder::create()
    ->addButton('Text', 'action')
    ->build();
```

---

### Problem: ConversationResult Static Factory Type Error

**Error**: Type mismatch on `ConversationResult::continue()`

**Cause**: Wrong parameter types passed to static factory

**Solution**:
```php
// ✅ Correct
ConversationResult::continue(
    nextStep: 'value_input',     // string
    message: 'Enter value',       // string
    data: ['key' => 'value']      // array
);

// ❌ Wrong
ConversationResult::continue(
    nextStep: null,               // Wrong: should be string
    message: ['array'],           // Wrong: should be string
    data: 'string'                // Wrong: should be array
);
```

---

### Problem: Middleware Pipeline Returns Null

**Symptom**: Pipeline execution stops unexpectedly

**Cause**: Middleware returning `null` (stopping the chain)

**Diagnosis**:
```php
$result = $pipeline->through($chat, $destination);

if ($result === null) {
    // Middleware stopped execution
    // Check which middleware failed by adding logging:

    class RequireAccountLinkMiddleware implements TelegramMiddleware
    {
        public function handle(TelegraphChat $chat, Closure $next): mixed
        {
            $user = $this->userService->getCurrentUser($chat->chat_id);

            if (!$user) {
                \Log::info('RequireAccountLinkMiddleware: User not found');  // Add this
                $this->sendNotLinkedMessage($chat);
                return null;
            }

            return $next($user);
        }
    }
}
```

---

### Problem: Message Formatting Inconsistency

**Symptom**: Messages look different across handlers

**Cause**: Manual string formatting instead of using `MessageResponseBuilder`

**Solution**:
```php
// ✅ Use builder for consistency
$message = MessageResponseBuilder::create()
    ->title('My Title')
    ->text('My text')
    ->build();

// ❌ Don't manually format
$message = "**My Title**\nMy text";  // Inconsistent formatting
```

---

## Appendix: File Checklist

Use this checklist to verify Phase 1 infrastructure is complete:

### Interfaces/Contracts (7 files)

- [ ] `app/Telegram/Commands/Contracts/TelegramCommandHandler.php`
- [ ] `app/Telegram/Callbacks/Contracts/CallbackHandler.php`
- [ ] `app/Telegram/Conversations/Contracts/ConversationHandler.php`
- [ ] `app/Telegram/Conversations/Contracts/ConversationContext.php`
- [ ] `app/Telegram/Conversations/Contracts/ConversationResult.php`
- [ ] `app/Telegram/Conversations/Contracts/ConversationStatus.php`
- [ ] `app/Telegram/Middleware/Contracts/TelegramMiddleware.php`

### Services (2 files)

- [ ] `app/Telegram/Services/TelegramCommandRegistry.php`
- [ ] `app/Telegram/Services/MessageResponseBuilder.php`

### Keyboards (2 files)

- [ ] `app/Telegram/Keyboards/KeyboardFactory.php`
- [ ] `app/Telegram/Keyboards/KeyboardBuilder.php`

### Middleware (3 files)

- [ ] `app/Telegram/Middleware/MiddlewarePipeline.php`
- [ ] `app/Telegram/Middleware/RequireAccountLinkMiddleware.php`
- [ ] `app/Telegram/Middleware/RequireFatSecretAuthMiddleware.php`

### Exceptions (4 files)

- [ ] `app/Telegram/Exceptions/UnknownCommandException.php`
- [ ] `app/Telegram/Exceptions/NoActiveConversationException.php`
- [ ] `app/Telegram/Exceptions/InvalidConversationStepException.php`
- [ ] `app/Telegram/Exceptions/NoHandlerForConversationTypeException.php`

### Documentation (2 files)

- [ ] `TELEGRAM_REFACTORING_PHASE1_CHANGELOG.md`
- [ ] `TELEGRAM_REFACTORING_PHASE1_TECHNICAL_DOCS.md` (this file)

**Total**: 20 files, ~1,796 lines of code

---

## Next Steps

After completing Phase 1 infrastructure, the next phase is **Phase 2: Commands Migration**.

### Phase 2 Preview

**Tasks**:
1. Create `StartCommandHandler`
2. Create `HelpCommandHandler`
3. Create `AccountCommandHandler`
4. Create `FatSecretCommandHandler`
5. Create `SyncCommandHandler`
6. Register all commands in service provider
7. Update webhook handler to delegate to registry

**Estimated Time**: 4-6 hours

**Expected Impact**: Reduce handler by ~200-250 lines

**Reference**: See `TELEGRAM_REFACTORING_IMPLEMENTATION_PLAN.md` for detailed task breakdown

---

**Document Version**: 1.0
**Last Updated**: 2025-10-16
**Maintainer**: FitnessCoach Development Team