# Telegram Bot Architecture Proposal for FitnessCoach

**Author**: Claude Code
**Date**: October 15, 2025
**Current State**: 1,406-line monolithic handler after Telegraph migration
**Target State**: SOLID-compliant, maintainable, testable architecture

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Current Architecture Analysis](#current-architecture-analysis)
3. [Problems Identified](#problems-identified)
4. [Proposed Architecture](#proposed-architecture)
5. [Design Patterns & SOLID Principles](#design-patterns--solid-principles)
6. [Directory Structure](#directory-structure)
7. [Implementation Strategy](#implementation-strategy)
8. [Benefits & Trade-offs](#benefits--trade-offs)
9. [Migration Path](#migration-path)

---

## Executive Summary

The current `FitnessCoachWebhookHandler.php` (1,406 lines) violates multiple SOLID principles and contains mixed concerns. This proposal outlines a clean architecture that:

- **Separates concerns** using Command Pattern, Strategy Pattern, and Chain of Responsibility
- **Follows Laravel conventions** (Action-Interface pattern from CLAUDE.md)
- **Maintains Telegraph compatibility** while reducing complexity
- **Enables testing** through dependency injection and interface segregation
- **Scales horizontally** by allowing independent feature development

**Key Metric**: Reduce handler from 1,406 lines to ~150 lines orchestration code.

---

## Current Architecture Analysis

### Current Structure (Monolithic Handler)

```
FitnessCoachWebhookHandler.php (1,406 lines)
├── Error Handling (32 lines)
├── Guard Methods (46 lines)
├── Message Router (86 lines)
├── Conversation Handlers (418 lines)
│   ├── Measurement (88 lines)
│   ├── Weight (86 lines)
│   ├── Macro (109 lines)
│   └── Sync (97 lines)
├── Command Methods (194 lines)
├── Callback Methods (377 lines)
└── Keyboard Builders (155 lines)
```

### Dependencies Injected

```php
TelegramUserService
TelegramAccountService
TelegramFatSecretService
ConversationStateService
DateValidationService
```

### Existing Legacy Code (Preserved from Nutgram)

```
app/Telegram/
├── Commands/         # 6 old command classes (deprecated)
├── Conversations/    # 4 old conversation classes (deprecated)
├── Menus/           # 6 old menu classes (deprecated)
├── Services/        # Still in use
├── Constants/       # Still in use
└── Handlers/        # Current monolith
```

---

## Problems Identified

### 1. **Single Responsibility Principle (SRP) Violations**

The handler class has **at least 8 distinct responsibilities**:

- Webhook request orchestration
- Command routing and execution
- Callback query handling
- Message routing for conversations
- Conversation flow management (4 types)
- Error handling and logging
- Authorization/authentication guards
- Keyboard building (13 different keyboards)

**Impact**: Changes to one feature (e.g., measurement validation) risk breaking unrelated features (e.g., FatSecret OAuth).

### 2. **Open/Closed Principle (OCP) Violations**

Adding new conversation types requires:
- Modifying the central `handleChatMessage()` match expression
- Adding new private methods to the handler
- Modifying constructor dependencies if new services needed
- No extension point without modification

**Example**: Adding "nutrition goals" conversation requires editing 5+ places in the handler.

### 3. **Liskov Substitution Principle (LSP) Concerns**

Guard methods (`requireLinkedAccount()`, `requireFatSecretAuth()`) have side effects:
- Return `null` and send error messages (mixed return/side-effect pattern)
- Cannot be substituted with different authorization strategies
- Testing requires mocking Telegraph chat object

### 4. **Interface Segregation Principle (ISP) Issues**

No interfaces defined for:
- Conversation handlers
- Command handlers
- Callback handlers
- Keyboard builders

All handlers are **tightly coupled** to `FitnessCoachWebhookHandler` base class.

### 5. **Dependency Inversion Principle (DIP) Violations**

Handler depends on **concrete implementations**:
- Direct dependency on `ConversationStateService` (not interface)
- Direct dependency on `DateValidationService` (not interface)
- Hard to swap implementations for testing or feature variants

### 6. **Code Duplication**

- 4 conversation handlers have near-identical date validation flow
- 13 keyboard builders follow same pattern but with different buttons
- Guard methods repeated across multiple callback handlers
- Error message formatting duplicated

### 7. **Testability Issues**

- Cannot test conversation logic without full Telegraph context
- Private methods not testable in isolation
- Guard methods require complex mocking
- No unit tests possible without integration test setup

### 8. **Maintainability Concerns**

- **Cognitive load**: 1,406 lines to understand before making changes
- **Merge conflicts**: Multiple developers touching same file
- **Navigation difficulty**: Finding specific feature implementation
- **Debugging complexity**: Stack traces point to same class for all errors

---

## Proposed Architecture

### Core Architectural Principles

1. **Thin Handler**: Orchestration only, delegates to specialized handlers
2. **Command Pattern**: Each Telegram command = separate handler class
3. **Strategy Pattern**: Conversation types as pluggable strategies
4. **Chain of Responsibility**: Middleware for authorization guards
5. **Factory Pattern**: Keyboard building as composable factories
6. **Action-Interface Pattern**: Following existing FitnessCoach conventions

### High-Level Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│  FitnessCoachWebhookHandler (Thin Orchestrator - 150 lines) │
│  - Registers routes                                          │
│  - Handles exceptions (delegates to ErrorHandler)           │
│  - Applies middleware chain                                  │
└────────────────┬────────────────────────────────────────────┘
                 │
        ┌────────┴──────────┐
        │                   │
        ▼                   ▼
┌──────────────────┐  ┌──────────────────┐
│ Command Registry │  │ Callback Router  │
│ (Factory)        │  │ (Strategy)       │
└────────┬─────────┘  └────────┬─────────┘
         │                     │
    ┌────┴────┐           ┌───┴────┐
    ▼         ▼           ▼        ▼
┌────────┐ ┌────────┐ ┌──────┐ ┌──────┐
│/start  │ │/help   │ │ Menu │ │ Sync │
│Handler │ │Handler │ │ CBs  │ │ CBs  │
└────────┘ └────────┘ └──────┘ └──────┘

┌─────────────────────────────────────────────┐
│ Conversation Manager (State Machine)        │
│ - Routes messages to active conversations   │
└────────┬────────────────────────────────────┘
         │
    ┌────┴────────────┐
    ▼                 ▼
┌────────────┐  ┌────────────┐
│Measurement │  │   Weight   │
│Conversation│  │Conversation│
│(Strategy)  │  │(Strategy)  │
└────────────┘  └────────────┘

┌──────────────────────────────────────┐
│  Middleware Pipeline                 │
│  - RequireAccountLink                │
│  - RequireFatSecretAuth              │
│  - RateLimiting (future)             │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│  Supporting Components               │
│  - KeyboardFactory                   │
│  - MessageResponseBuilder            │
│  - ValidationService                 │
└──────────────────────────────────────┘
```

---

## Design Patterns & SOLID Principles

### 1. Command Pattern for Telegram Commands

**Purpose**: Encapsulate each Telegram command (`/start`, `/help`, `/account`) as an independent handler.

**Benefits**:
- Single Responsibility: One class per command
- Open/Closed: Add new commands without modifying existing code
- Testability: Each command handler tested in isolation

**Structure**:

```php
// Interface
interface TelegramCommandHandler
{
    public function handle(TelegraphChat $chat): void;
    public function getCommandName(): string;
}

// Implementation Example
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
            ->addFeatures(['weight', 'measurements', 'macros', 'sync'])
            ->build();

        $chat->html($message)
            ->keyboard($this->keyboardFactory->mainMenu())
            ->send();
    }

    public function getCommandName(): string
    {
        return 'start';
    }
}
```

**Registration**:

```php
// In service provider
class TelegramCommandRegistry
{
    private array $handlers = [];

    public function register(TelegramCommandHandler $handler): void
    {
        $this->handlers[$handler->getCommandName()] = $handler;
    }

    public function handle(string $command, TelegraphChat $chat): void
    {
        if (!isset($this->handlers[$command])) {
            throw new UnknownCommandException($command);
        }

        $this->handlers[$command]->handle($chat);
    }
}
```

**Commands to Implement**:
- `StartCommandHandler` - Welcome message + main menu
- `HelpCommandHandler` - Command reference list
- `AccountCommandHandler` - Account linking menu
- `FatSecretCommandHandler` - FatSecret OAuth menu
- `SyncCommandHandler` - Synchronization menu

### 2. Strategy Pattern for Conversations

**Purpose**: Each conversation type (measurement, weight, macro, sync) is a separate strategy with consistent interface.

**Benefits**:
- Single Responsibility: Each conversation = one class
- Dependency Inversion: Depend on interface, not concrete implementation
- Open/Closed: Add new conversation types without modifying router

**Structure**:

```php
// Strategy Interface
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

// Context DTO
class ConversationContext
{
    public function __construct(
        public readonly string $chatId,
        public readonly User $user,
        public readonly array $data,
    ) {}
}

// Result DTO
class ConversationResult
{
    public function __construct(
        public readonly ConversationStatus $status, // Continue, Complete, Error
        public readonly ?string $nextStep = null,
        public readonly ?string $message = null,
        public readonly ?Keyboard $keyboard = null,
    ) {}
}

enum ConversationStatus {
    case Continue;
    case Complete;
    case Error;
}
```

**Implementation Example**:

```php
class MeasurementConversationHandler implements ConversationHandler
{
    public function __construct(
        private readonly DateValidationService $dateValidation,
        private readonly SaveMeasurement $saveMeasurementAction,
        private readonly KeyboardFactory $keyboardFactory,
    ) {}

    public function handle(
        TelegraphChat $chat,
        string $step,
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        return match ($step) {
            'input_date' => $this->handleDateInput($chat, $message, $context),
            'input_value' => $this->handleValueInput($chat, $message, $context),
            default => throw new InvalidConversationStepException($step),
        };
    }

    private function handleDateInput(
        TelegraphChat $chat,
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        $dateResult = $this->dateValidation->validateAndParseDate((string) $message);

        if (!$dateResult['valid']) {
            return new ConversationResult(
                status: ConversationStatus::Continue,
                message: $dateResult['error'],
            );
        }

        return new ConversationResult(
            status: ConversationStatus::Continue,
            nextStep: 'input_value',
            message: "📏 Введите значение в сантиметрах:",
        );
    }

    private function handleValueInput(
        TelegraphChat $chat,
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        // Validation logic
        $value = $this->validateValue($message);

        // Business logic via Action
        $this->saveMeasurementAction->handle([
            'user_id' => $context->user->id,
            'type' => $context->data['measurement_type'],
            'value' => $value,
            'date' => $context->data['date'],
        ]);

        return new ConversationResult(
            status: ConversationStatus::Complete,
            message: "✅ Замер сохранен",
            keyboard: $this->keyboardFactory->mainMenu(),
        );
    }

    public function getType(): string
    {
        return 'measurement';
    }

    public function canHandle(string $type): bool
    {
        return $type === $this->getType();
    }
}
```

**Conversation Manager**:

```php
class ConversationManager
{
    public function __construct(
        private readonly ConversationStateService $stateService,
        private readonly iterable $handlers, // Auto-injected via service provider
    ) {}

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
        $context = new ConversationContext($chatId, $this->getUser($chat), $data);

        $result = $handler->handle($chat, $step, $message, $context);

        $this->processResult($chat, $chatId, $result);
    }

    private function findHandler(string $type): ConversationHandler
    {
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($type)) {
                return $handler;
            }
        }

        throw new NoHandlerForConversationTypeException($type);
    }

    private function processResult(TelegraphChat $chat, string $chatId, ConversationResult $result): void
    {
        match ($result->status) {
            ConversationStatus::Continue => $this->continueConversation($chatId, $result),
            ConversationStatus::Complete => $this->completeConversation($chatId, $chat, $result),
            ConversationStatus::Error => $this->handleError($chatId, $chat, $result),
        };
    }
}
```

**Conversations to Implement**:
- `MeasurementConversationHandler` - Body measurements
- `WeightConversationHandler` - Weight tracking
- `MacroConversationHandler` - КБЖУ tracking
- `SyncConversationHandler` - FatSecret sync

### 3. Chain of Responsibility for Middleware

**Purpose**: Handle authorization checks (account linking, FatSecret auth) as middleware chain.

**Benefits**:
- Single Responsibility: Each middleware = one check
- Open/Closed: Add new checks without modifying existing
- Reusability: Same middleware usable across multiple handlers

**Structure**:

```php
interface TelegramMiddleware
{
    public function handle(
        TelegraphChat $chat,
        Closure $next
    ): mixed;
}

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
            $chat->message('❌ Аккаунт не привязан.')
                ->keyboard($this->keyboardFactory->accountLinking())
                ->send();

            return null;
        }

        // Pass user to next middleware/handler
        return $next($user);
    }
}

class RequireFatSecretAuthMiddleware implements TelegramMiddleware
{
    public function handle(TelegraphChat $chat, Closure $next): mixed
    {
        $user = $next->getParameter('user'); // From previous middleware

        if (!$user->isFatSecretAuthorized()) {
            $chat->message('❌ FatSecret не подключен.')
                ->keyboard($this->keyboardFactory->fatSecretMenu())
                ->send();

            return null;
        }

        return $next($user);
    }
}
```

**Pipeline Usage**:

```php
class MiddlewarePipeline
{
    public function __construct(
        private readonly array $middleware = []
    ) {}

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

// Usage in callback handler
class ShowMeasurementsCallbackHandler
{
    public function __invoke(TelegraphChat $chat): void
    {
        $pipeline = new MiddlewarePipeline([
            new RequireAccountLinkMiddleware(...)
        ]);

        $pipeline->through($chat, function(User $user) use ($chat) {
            // Show measurements menu
            $chat->edit($this->messageId)
                ->html($this->buildMessage())
                ->keyboard($this->keyboardFactory->measurements())
                ->send();
        });
    }
}
```

### 4. Factory Pattern for Keyboards

**Purpose**: Centralize keyboard creation logic with fluent builder API.

**Benefits**:
- Single Responsibility: Keyboard logic in one place
- DRY: No duplication of button/keyboard building
- Maintainability: Change button text/icons in one location

**Structure**:

```php
class KeyboardFactory
{
    // Main keyboards
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

    public function accountLinking(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('🔗 Привязать аккаунт')->action('accountLinking'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    // Fluent builder for dynamic keyboards
    public function builder(): KeyboardBuilder
    {
        return new KeyboardBuilder();
    }
}

class KeyboardBuilder
{
    private array $buttons = [];

    public function addButton(string $text, string $action): self
    {
        $this->buttons[] = Button::make($text)->action($action);
        return $this;
    }

    public function addBackButton(string $action = 'mainMenu'): self
    {
        $this->buttons[] = Button::make('🏠 Главное меню')->action($action);
        return $this;
    }

    public function addRow(): self
    {
        // Implementation for multi-row keyboards
        return $this;
    }

    public function build(): Keyboard
    {
        return Keyboard::make()->buttons($this->buttons);
    }
}
```

### 5. Decorator Pattern for Cross-Cutting Concerns

**Purpose**: Add logging, metrics, rate limiting without modifying core handlers.

**Structure**:

```php
class LoggingConversationHandlerDecorator implements ConversationHandler
{
    public function __construct(
        private readonly ConversationHandler $decorated,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(
        TelegraphChat $chat,
        string $step,
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        $this->logger->info('Conversation step started', [
            'type' => $this->decorated->getType(),
            'step' => $step,
            'chat_id' => $context->chatId,
        ]);

        $startTime = microtime(true);

        try {
            $result = $this->decorated->handle($chat, $step, $message, $context);

            $this->logger->info('Conversation step completed', [
                'type' => $this->decorated->getType(),
                'step' => $step,
                'status' => $result->status->name,
                'duration_ms' => (microtime(true) - $startTime) * 1000,
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Conversation step failed', [
                'type' => $this->decorated->getType(),
                'step' => $step,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // Delegate other methods
    public function getType(): string
    {
        return $this->decorated->getType();
    }

    // ... other methods
}
```

### 6. Action-Interface Pattern (Existing FitnessCoach Convention)

**Purpose**: Follow established project conventions for business logic.

**Implementation**:

```php
// Contract
interface SaveMeasurement
{
    public function handle(array $data): Measurement;
}

// Action
class SaveMeasurementAction implements SaveMeasurement
{
    public function __construct(
        private readonly MeasurementRepository $repository,
    ) {}

    public function handle(array $data): Measurement
    {
        // Validation
        // Business logic
        // Persistence

        return $this->repository->save(new Measurement($data));
    }
}

// Service Provider Binding
$this->app->bind(SaveMeasurement::class, SaveMeasurementAction::class);
```

**Actions to Create**:
- `SaveMeasurement` - Store body measurement
- `SaveWeight` - Store weight entry
- `SaveMacro` - Store КБЖУ entry
- `PerformFatSecretSync` - Execute synchronization

---

## Directory Structure

### Proposed New Structure

```
app/Telegram/
├── Handlers/
│   ├── FitnessCoachWebhookHandler.php          # Thin orchestrator (150 lines)
│   └── ErrorHandler.php                        # Centralized error handling
│
├── Commands/
│   ├── Contracts/
│   │   └── TelegramCommandHandler.php          # Interface
│   ├── StartCommandHandler.php                 # /start command
│   ├── HelpCommandHandler.php                  # /help command
│   ├── AccountCommandHandler.php               # /account command
│   ├── FatSecretCommandHandler.php             # /fatsecret command
│   └── SyncCommandHandler.php                  # /sync command
│
├── Conversations/
│   ├── Contracts/
│   │   ├── ConversationHandler.php             # Strategy interface
│   │   ├── ConversationContext.php             # Context DTO
│   │   └── ConversationResult.php              # Result DTO
│   ├── MeasurementConversationHandler.php
│   ├── WeightConversationHandler.php
│   ├── MacroConversationHandler.php
│   ├── SyncConversationHandler.php
│   ├── ConversationManager.php                 # Router/orchestrator
│   └── Steps/
│       ├── DateInputStep.php                   # Reusable step
│       └── NumericInputStep.php                # Reusable step
│
├── Callbacks/
│   ├── Contracts/
│   │   └── CallbackHandler.php                 # Interface
│   ├── MainMenu/
│   │   ├── ShowSettingsCallback.php
│   │   ├── ShowMeasurementsCallback.php
│   │   ├── ShowMacrosCallback.php
│   │   ├── ShowWeightCallback.php
│   │   └── ShowHelpCallback.php
│   ├── Account/
│   │   ├── GenerateLinkCodeCallback.php
│   │   ├── CheckLinkStatusCallback.php
│   │   └── RemoveLinkAccountCallback.php
│   ├── FatSecret/
│   │   ├── CheckConnectionCallback.php
│   │   ├── ConnectFatSecretCallback.php
│   │   └── LogoutFatSecretCallback.php
│   ├── Sync/
│   │   ├── SyncFullCallback.php
│   │   ├── SyncWeightCallback.php
│   │   └── SyncFoodCallback.php
│   └── CallbackRegistry.php                    # Factory/registry
│
├── Middleware/
│   ├── Contracts/
│   │   └── TelegramMiddleware.php              # Interface
│   ├── RequireAccountLinkMiddleware.php
│   ├── RequireFatSecretAuthMiddleware.php
│   ├── RateLimitingMiddleware.php              # Future
│   └── MiddlewarePipeline.php                  # Pipeline implementation
│
├── Keyboards/
│   ├── KeyboardFactory.php                     # Main factory
│   ├── KeyboardBuilder.php                     # Fluent builder
│   └── Presets/
│       ├── MainMenuKeyboard.php
│       ├── AccountKeyboard.php
│       ├── FatSecretKeyboard.php
│       └── SyncKeyboard.php
│
├── Services/
│   ├── ConversationStateService.php            # Existing (keep)
│   ├── DateValidationService.php               # Existing (keep)
│   ├── TelegramUserService.php                 # Existing (keep)
│   ├── TelegramAccountService.php              # Existing (keep)
│   ├── TelegramFatSecretService.php            # Existing (keep)
│   ├── MessageResponseBuilder.php              # New - message formatting
│   └── TelegramCommandRegistry.php             # New - command routing
│
├── Exceptions/
│   ├── AbstractTelegramBotException.php        # Existing (keep)
│   ├── UserNotFoundException.php               # Existing (keep)
│   ├── UnknownCommandException.php             # New
│   ├── NoActiveConversationException.php       # New
│   └── InvalidConversationStepException.php    # New
│
├── Constants/
│   ├── CommandConstants.php                    # Existing (keep)
│   ├── ConversationSteps.php                   # Existing (keep)
│   └── CallbackData.php                        # Deprecated (mark for removal)
│
└── Providers/
    └── TelegramServiceProvider.php             # Enhanced service bindings

```

### File Size Estimate

| Component | Estimated Lines | Count | Total Lines |
|-----------|----------------|-------|-------------|
| Thin Handler | 150 | 1 | 150 |
| Command Handlers | 40-60 | 5 | 250 |
| Conversation Handlers | 100-150 | 4 | 500 |
| Callback Handlers | 20-40 | 15 | 450 |
| Middleware | 30-50 | 3 | 120 |
| Keyboards | 80 | 1 factory | 80 |
| Services | 100-200 | 2 new | 300 |
| **Total** | | | **~1,850** |

**Result**: ~440 more lines total, but:
- Each file < 200 lines (maintainable)
- Single Responsibility per class
- Testable in isolation
- Horizontally scalable

---

## Implementation Strategy

### Phase 1: Foundation (Week 1)

**Goal**: Set up interfaces and base infrastructure without breaking existing code.

**Tasks**:
1. Create `Contracts/` directories with all interfaces
2. Implement `KeyboardFactory` and migrate keyboard building
3. Create `MessageResponseBuilder` service
4. Implement `MiddlewarePipeline` and base middleware classes
5. Create `TelegramCommandRegistry` service

**Success Criteria**:
- All interfaces defined
- No existing functionality broken
- Can run `php artisan about` without errors

### Phase 2: Commands Migration (Week 1-2)

**Goal**: Migrate 5 Telegram commands from handler to separate classes.

**Tasks**:
1. Implement `StartCommandHandler`
2. Implement `HelpCommandHandler`
3. Implement `AccountCommandHandler`
4. Implement `FatSecretCommandHandler`
5. Implement `SyncCommandHandler`
6. Update `FitnessCoachWebhookHandler` to delegate to registry
7. Write unit tests for each command handler

**Success Criteria**:
- All commands work identically to before
- Handler delegates to registry
- Test coverage > 80% for command handlers

### Phase 3: Callbacks Migration (Week 2-3)

**Goal**: Extract 15+ callback methods into separate handler classes.

**Tasks**:
1. Create `CallbackRegistry` with method-based routing
2. Implement main menu callbacks (5 handlers)
3. Implement account callbacks (3 handlers)
4. Implement FatSecret callbacks (3 handlers)
5. Implement sync callbacks (3 handlers)
6. Update handler to delegate callback routing
7. Write unit tests for callback handlers

**Success Criteria**:
- All callbacks work identically
- Handler delegates to registry
- Test coverage > 80% for callback handlers

### Phase 4: Conversations Refactoring (Week 3-4)

**Goal**: Extract 4 conversation flows into strategy pattern.

**Tasks**:
1. Implement `ConversationManager` with strategy routing
2. Create `MeasurementConversationHandler`
3. Create `WeightConversationHandler`
4. Create `MacroConversationHandler`
5. Create `SyncConversationHandler`
6. Extract reusable steps (`DateInputStep`, `NumericInputStep`)
7. Update handler to delegate to conversation manager
8. Write unit tests for each conversation handler

**Success Criteria**:
- All conversations work identically
- Handler delegates to manager
- Test coverage > 80% for conversation handlers
- Can add new conversation without modifying manager

### Phase 6: Cleanup & Optimization (Week 5)

**Goal**: Remove old code, add decorators, finalize documentation.

**Tasks**:
1. Remove deprecated Nutgram classes (`Commands/`, `Conversations/`, `Menus/`)
2. Add `LoggingConversationHandlerDecorator`
3. Add rate limiting middleware
4. Final refactoring pass
5. Update `CLAUDE.md` with new architecture
6. Create architecture diagrams
7. Write developer guide

**Success Criteria**:
- No deprecated code remains
- All TODOs resolved
- Test coverage > 80% overall
- Documentation complete

---

## Migration Path

### Strategy: **Strangler Fig Pattern**

Gradually replace monolith functionality while keeping system operational.

### Migration Phases

```
Week 1: Foundation + Commands
├── ✅ Interfaces defined
├── ✅ Infrastructure services created
└── ✅ Commands migrated

Week 2: Callbacks
├── ✅ Registry implemented
└── ✅ All callbacks migrated

Week 3-4: Conversations
├── ✅ Strategy pattern implemented
├── ✅ Manager created
└── ✅ All conversations migrated

Week 5: Business Logic + Cleanup
├── ✅ Actions integrated
├── ✅ Old code removed
└── ✅ Documentation complete
```

### Rollback Strategy

Each phase keeps old code intact until:
1. New implementation tested
2. Integration tests pass
3. Manual testing complete

**Rollback**: Comment out new code, uncomment old code.

### Feature Flags (Optional)

```php
// In config/telegram.php
'use_new_architecture' => [
    'commands' => env('TELEGRAM_NEW_COMMANDS', false),
    'callbacks' => env('TELEGRAM_NEW_CALLBACKS', false),
    'conversations' => env('TELEGRAM_NEW_CONVERSATIONS', false),
]

// In handler
if (config('telegram.use_new_architecture.commands')) {
    return $this->commandRegistry->handle($command, $chat);
} else {
    return $this->handleCommandOldWay($command);
}
```

---

## Code Examples

### Example 1: Thin Handler (After Refactoring)

```php
class FitnessCoachWebhookHandler extends WebhookHandler
{
    public function __construct(
        private readonly TelegramCommandRegistry $commandRegistry,
        private readonly CallbackRegistry $callbackRegistry,
        private readonly ConversationManager $conversationManager,
        private readonly ErrorHandler $errorHandler,
    ) {
        parent::__construct();
    }

    protected function onFailure(Throwable $throwable): void
    {
        $this->errorHandler->handle($throwable, $this->chat);
    }

    // Automatically routes /start, /help, etc to registry
    public function __call(string $method, array $parameters)
    {
        // Commands are handled by registry
        if ($this->isCommand($method)) {
            return $this->commandRegistry->handle($method, $this->chat);
        }

        // Callbacks are handled by callback registry
        return $this->callbackRegistry->handle($method, $this->chat, $this->messageId);
    }

    protected function handleChatMessage(Stringable $text): void
    {
        try {
            $this->conversationManager->route($this->chat, $text);
        } catch (NoActiveConversationException $e) {
            $this->chat->html(
                "💬 Я понимаю только команды.\n\n" .
                "Используйте /help для списка доступных команд."
            )->send();
        }
    }

    private function isCommand(string $method): bool
    {
        return in_array($method, ['start', 'help', 'account', 'fatsecret', 'sync']);
    }
}
```

**Result**: ~150 lines, pure orchestration, no business logic.

### Example 2: Measurement Conversation Handler (Full Implementation)

```php
class MeasurementConversationHandler implements ConversationHandler
{
    public function __construct(
        private readonly DateValidationService $dateValidation,
        private readonly SaveMeasurement $saveMeasurementAction,
        private readonly KeyboardFactory $keyboardFactory,
        private readonly MessageResponseBuilder $messageBuilder,
    ) {}

    public function handle(
        TelegraphChat $chat,
        string $step,
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        return match ($step) {
            'input_date' => $this->handleDateInput($message, $context),
            'input_value' => $this->handleValueInput($message, $context),
            default => throw new InvalidConversationStepException($step),
        };
    }

    private function handleDateInput(
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        $dateResult = $this->dateValidation->validateAndParseDate((string) $message);

        if (!$dateResult['valid']) {
            return ConversationResult::error($dateResult['error']);
        }

        $measurementType = $context->data['measurement_type'] ?? 'замер';

        return ConversationResult::continue(
            nextStep: 'input_value',
            message: $this->messageBuilder
                ->icon('📏')
                ->title("Замер: {$measurementType}")
                ->instruction('Введите значение в сантиметрах:')
                ->example('Например: 95')
                ->build(),
            data: [
                'date' => $dateResult['formatted'],
                'date_object' => $dateResult['date'],
            ]
        );
    }

    private function handleValueInput(
        Stringable $message,
        ConversationContext $context
    ): ConversationResult {
        $valueInput = trim((string) $message);

        if (!is_numeric($valueInput)) {
            return ConversationResult::error(
                $this->messageBuilder
                    ->icon('❌')
                    ->title('Неверное значение')
                    ->instruction('Введите число (в сантиметрах):')
                    ->example('Например: 95')
                    ->build()
            );
        }

        $value = (float) $valueInput;

        if ($value < 1 || $value > 300) {
            return ConversationResult::error(
                'Введите значение от 1 до 300 см:'
            );
        }

        // Business logic via Action
        $measurement = $this->saveMeasurementAction->handle([
            'user_id' => $context->user->id,
            'type' => $context->data['measurement_type'],
            'value' => $value,
            'date' => $context->data['date'],
        ]);

        return ConversationResult::complete(
            message: $this->messageBuilder
                ->icon('✅')
                ->title('Замер сохранен')
                ->addField('Тип', $measurement->type)
                ->addField('Значение', "{$value} см")
                ->addField('Дата', $context->data['date'])
                ->build(),
            keyboard: $this->keyboardFactory->mainMenu()
        );
    }

    public function getType(): string
    {
        return 'measurement';
    }

    public function getInitialStep(): string
    {
        return 'input_date';
    }

    public function canHandle(string $type): bool
    {
        return $type === $this->getType();
    }
}
```

**Result**: ~120 lines, single responsibility, fully testable, no Telegraph coupling in tests.

### Example 3: Unit Test Example

```php
class MeasurementConversationHandlerTest extends TestCase
{
    private MeasurementConversationHandler $handler;
    private DateValidationService $dateValidation;
    private SaveMeasurement $saveMeasurementAction;
    private KeyboardFactory $keyboardFactory;
    private MessageResponseBuilder $messageBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dateValidation = $this->createMock(DateValidationService::class);
        $this->saveMeasurementAction = $this->createMock(SaveMeasurement::class);
        $this->keyboardFactory = $this->createMock(KeyboardFactory::class);
        $this->messageBuilder = new MessageResponseBuilder();

        $this->handler = new MeasurementConversationHandler(
            $this->dateValidation,
            $this->saveMeasurementAction,
            $this->keyboardFactory,
            $this->messageBuilder
        );
    }

    public function test_handles_valid_date_input(): void
    {
        // Arrange
        $message = Str::of('15.10.2025');
        $context = new ConversationContext(
            chatId: '123',
            user: User::factory()->make(),
            data: ['measurement_type' => 'Грудь']
        );

        $this->dateValidation
            ->expects($this->once())
            ->method('validateAndParseDate')
            ->with('15.10.2025')
            ->willReturn([
                'valid' => true,
                'formatted' => '15.10.2025',
                'date' => Carbon::parse('2025-10-15'),
            ]);

        // Act
        $result = $this->handler->handle(
            $this->createMock(TelegraphChat::class),
            'input_date',
            $message,
            $context
        );

        // Assert
        $this->assertEquals(ConversationStatus::Continue, $result->status);
        $this->assertEquals('input_value', $result->nextStep);
        $this->assertStringContainsString('Введите значение', $result->message);
    }

    public function test_rejects_invalid_numeric_value(): void
    {
        // Arrange
        $message = Str::of('abc');
        $context = new ConversationContext(
            chatId: '123',
            user: User::factory()->make(),
            data: [
                'measurement_type' => 'Грудь',
                'date' => '15.10.2025',
            ]
        );

        // Act
        $result = $this->handler->handle(
            $this->createMock(TelegraphChat::class),
            'input_value',
            $message,
            $context
        );

        // Assert
        $this->assertEquals(ConversationStatus::Continue, $result->status);
        $this->assertStringContainsString('Неверное значение', $result->message);
    }

    public function test_saves_measurement_with_valid_inputs(): void
    {
        // Arrange
        $message = Str::of('95');
        $user = User::factory()->make(['id' => 1]);
        $context = new ConversationContext(
            chatId: '123',
            user: $user,
            data: [
                'measurement_type' => 'Грудь',
                'date' => '15.10.2025',
            ]
        );

        $expectedMeasurement = new Measurement([
            'user_id' => 1,
            'type' => 'Грудь',
            'value' => 95.0,
            'date' => '15.10.2025',
        ]);

        $this->saveMeasurementAction
            ->expects($this->once())
            ->method('handle')
            ->with([
                'user_id' => 1,
                'type' => 'Грудь',
                'value' => 95.0,
                'date' => '15.10.2025',
            ])
            ->willReturn($expectedMeasurement);

        $this->keyboardFactory
            ->expects($this->once())
            ->method('mainMenu')
            ->willReturn(Keyboard::make());

        // Act
        $result = $this->handler->handle(
            $this->createMock(TelegraphChat::class),
            'input_value',
            $message,
            $context
        );

        // Assert
        $this->assertEquals(ConversationStatus::Complete, $result->status);
        $this->assertStringContainsString('Замер сохранен', $result->message);
        $this->assertNotNull($result->keyboard);
    }
}
```

**Result**: Full test coverage without Telegraph integration, fast execution, easy to understand.

---

## Appendix A: Comparison Table

| Aspect | Current (Monolith) | Proposed (Modular) |
|--------|-------------------|-------------------|
| **File Count** | 1 handler | ~30 files |
| **Largest File** | 1,406 lines | ~200 lines max |
| **Testability** | Integration only | Unit + Integration |
| **SRP Compliance** | ❌ Multiple responsibilities | ✅ One per class |
| **OCP Compliance** | ❌ Modify to extend | ✅ Extend without modify |
| **LSP Compliance** | ❌ No substitution | ✅ Interface-based |
| **ISP Compliance** | ❌ No interfaces | ✅ Focused interfaces |
| **DIP Compliance** | ❌ Concrete dependencies | ✅ Abstract dependencies |
| **Developer Onboarding** | Hard (1,406 lines) | Easy (small files) |
| **Parallel Development** | High conflict risk | Low conflict risk |
| **Debugging Complexity** | High (one class) | Low (isolated) |
| **Maintenance Cost** | High | Low |
| **Test Coverage** | Low (~0%) | High (>80%) |

---

## Appendix B: Key Interfaces

### TelegramCommandHandler
```php
interface TelegramCommandHandler
{
    public function handle(TelegraphChat $chat): void;
    public function getCommandName(): string;
}
```

### ConversationHandler
```php
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
```

### TelegramMiddleware
```php
interface TelegramMiddleware
{
    public function handle(TelegraphChat $chat, Closure $next): mixed;
}
```

### CallbackHandler
```php
interface CallbackHandler
{
    public function handle(TelegraphChat $chat, ?int $messageId = null): void;
    public function getCallbackName(): string;
}
```

---

## Appendix C: Recommended Reading

### Design Patterns
- **Command Pattern**: [Refactoring Guru](https://refactoring.guru/design-patterns/command)
- **Strategy Pattern**: [Refactoring Guru](https://refactoring.guru/design-patterns/strategy)
- **Chain of Responsibility**: [Refactoring Guru](https://refactoring.guru/design-patterns/chain-of-responsibility)
- **Factory Pattern**: [Refactoring Guru](https://refactoring.guru/design-patterns/factory-method)
- **Decorator Pattern**: [Refactoring Guru](https://refactoring.guru/design-patterns/decorator)

### SOLID Principles
- **Clean Architecture (Robert C. Martin)**
- **Agile Software Development (Robert C. Martin)**

### Laravel Patterns
- **Laravel Beyond CRUD (Spatie)**
- **Domain-Driven Laravel (Brent Roose)**

---
