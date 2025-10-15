# Migration Plan: Nutgram → defstudio/telegraph

## Current Architecture Analysis

### Nutgram Implementation Overview

**Structure:**
```
app/Telegram/
├── Commands/              # Command handlers (extend Nutgram Command)
├── Middleware/           # Authorization middleware
├── Conversations/        # Multi-step conversation flows
├── Menus/               # InlineMenu classes for keyboards
├── Services/            # Business logic services
├── Constants/           # Callback data and conversation steps
├── Builders/            # Message and keyboard builders
├── Handlers/            # Error handlers
├── Exceptions/          # Custom exceptions
└── Providers/           # TelegramServiceProvider
```

**Key Components:**
- **Webhook Entry**: `TelegramWebhookController` receives updates, calls `$bot->run()`
- **Command Registration**: Service provider registers commands with `$bot->onCommand()`
- **Conversation System**: Extends `Conversation` class with step-based state management
- **Menu System**: Extends `InlineMenu` for keyboard interactions
- **Middleware**: Manual middleware application (not globally enforced)
- **State Management**: Laravel Cache for conversation state and temporary data
- **Error Handling**: Centralized exception handlers in service provider

**Identified Issues:**
1. **Loose Coupling**: Commands and menus use `::begin()` static methods creating tight coupling
2. **State Management Complexity**: Manual cache key management across multiple services
3. **Inconsistent Patterns**: Mix of callbacks (@method notation) and direct routing
4. **Limited Middleware Support**: Middleware not globally enforced, applied manually
5. **Verbose Keyboard Building**: Custom builders add unnecessary abstraction
6. **Message Editing Issues**: Try-catch blocks needed around message operations
7. **Conversation State Persistence**: Manual property management and cache coordination

## Telegraph Architecture & Features

### Core Concepts

**Database-Driven Architecture:**
- `telegraph_bots` table: Bot configurations and tokens
- `telegraph_chats` table: Individual chat instances with relationships to bots
- Eloquent models: `TelegraphBot` and `TelegraphChat` with fluent API

**Webhook Handler Pattern:**
- Single `WebhookHandler` class processes all updates
- Method-based routing: `/command` → `public function command()`
- Callback routing: `callback_data` → `public function callbackMethod()`
- Built-in message type handlers: `handleChatMessage()`, `handleInlineQuery()`, etc.

**Key Advantages:**
1. **Laravel-Native**: Follows Laravel conventions and patterns
2. **Simpler Keyboard API**: Built-in `Keyboard::make()->buttons()` with fluent interface
3. **Automatic Callback Routing**: No manual registration needed
4. **Database-Backed State**: Bot and chat state stored in database, not cache
5. **Middleware Support**: Standard Laravel middleware applies to webhook route
6. **Model-Based API**: `$this->chat->message()`, `$this->bot->sendMessage()`
7. **Cleaner Error Handling**: Override `onFailure()` method for custom error responses

## Migration Strategy

### Phase 1: Setup & Database Migration

**Tasks:**

1. **Install Telegraph Package**
   - Add `defstudio/telegraph` to composer.json
   - Run migrations to create `telegraph_bots` and `telegraph_chats` tables
   - Publish configuration: `telegraph.php`

2. **Migrate Bot Configuration**
   - Create `TelegraphBot` record with existing bot token
   - Set webhook URL and secret token
   - Register bot commands via `registerCommands()` method

3. **Data Migration**
   - Identify all existing Telegram users (from `users.telegram_id`)
   - Create `TelegraphChat` records for each user
   - Link chats to bot instance
   - Preserve user linkage data

### Phase 2: Core Webhook Handler

**Tasks:**

1. **Create Base WebhookHandler**
   ```php
   app/Telegram/Handlers/FitnessCoachWebhookHandler.php
   ```
   - Extend `DefStudio\Telegraph\Handlers\WebhookHandler`
   - Implement `onFailure()` for centralized error handling
   - Inject required services via constructor

2. **Replace Webhook Controller**
   - Remove `TelegramWebhookController`
   - Telegraph handles webhook routing automatically via `config/telegraph.php`
   - Configure custom handler: `config('telegraph.webhook.handler')`

3. **Configure Middleware**
   - Apply authentication middleware via `telegraph.webhook.middleware` config
   - Remove manual middleware checks from individual handlers

### Phase 3: Command Migration

**Current Pattern:**
```php
// Nutgram
class StartCommand extends Command
{
    protected string $command = 'start';
    public function handle(Nutgram $bot): void { /* ... */ }
}

// Service Provider
$bot->onCommand('start', StartCommand::class);
```

**Telegraph Pattern:**
```php
// Telegraph
class FitnessCoachWebhookHandler extends WebhookHandler
{
    public function start()
    {
        $welcomeText = "🎯 **Добро пожаловать в FitnessCoach!**...";
        $this->chat->message($welcomeText)->send();

        // Send keyboard
        $this->chat->message('Выберите действие:')
            ->keyboard($this->buildMainMenuKeyboard())
            ->send();
    }
}
```

**Migration Mapping:**

| Nutgram Command | Telegraph Method | Notes |
|----------------|------------------|-------|
| `StartCommand` | `start()` | Welcome message + main menu keyboard |
| `HelpCommand` | `help()` | Help text display |
| `AccountCommand` | `account()` | Account linking flow trigger |
| `FatSecretCommand` | `fatsecret()` | FatSecret OAuth flow trigger |
| `SyncCommand` | `sync()` | Synchronization menu |

**Tasks:**
- Migrate each command to a method in `FitnessCoachWebhookHandler`
- Replace `$bot->sendMessage()` with `$this->chat->message()->send()`
- Convert keyboard generation to Telegraph's `Keyboard::make()->buttons()` API
- Remove command registration from service provider (automatic routing)

### Phase 4: Callback Handler Migration

**Current Pattern:**
```php
// Nutgram Menu
class MainMenu extends InlineMenu
{
    public function start(Nutgram $bot) {
        $this->menuText('🏠 Главное меню')
            ->addButtonRow(InlineKeyboardButton::make(
                '⚙️ Настройки',
                callback_data: CallbackData::SETTINGS . '@showSettings'
            ))
            ->showMenu();
    }

    public function showSettings(Nutgram $bot) { /* ... */ }
}
```

**Telegraph Pattern:**
```php
// Telegraph
class FitnessCoachWebhookHandler extends WebhookHandler
{
    protected function buildMainMenuKeyboard(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('⚙️ Настройки')->action('showSettings'),
            Button::make('📏 Замеры')->action('showMeasurements'),
            Button::make('🔄 Синхронизация')->action('showSync'),
            Button::make('🍎 КБЖУ')->action('showMacros'),
            Button::make('⚖️ Вес')->action('showWeight'),
        ]);
    }

    public function showSettings()
    {
        $keyboard = Keyboard::make()->buttons([
            Button::make('🔗 Привязка аккаунта')->action('accountLinking'),
            Button::make('🔐 FatSecret')->action('fatSecretConnection'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);

        $this->chat->edit($this->messageId)
            ->message('⚙️ Настройки')
            ->keyboard($keyboard)
            ->send();
    }

    public function showMeasurements() { /* ... */ }
    public function showSync() { /* ... */ }
    public function showMacros() { /* ... */ }
    public function showWeight() { /* ... */ }
}
```

**Migration Tasks:**

1. **Convert InlineMenu Classes to Handler Methods**
   - `MainMenu` → `mainMenu()` method + helper keyboard builders
   - `SettingsMenu` → `showSettings()` + callback methods
   - `MeasurementsMenu` → `showMeasurements()` + callback methods
   - `SyncMenu` → `showSync()` + callback methods
   - `MacrosMenu` → `showMacros()` + callback methods
   - `WeightMenu` → `showWeight()` + callback methods
   - `AccountLinkingMenu` → `accountLinking()` methods
   - `FatSecretConnectionMenu` → `fatSecretConnection()` methods

2. **Simplify Keyboard Building**
   - Remove custom `KeyboardBuilder` and `InlineKeyboardButtonBuilder`
   - Use Telegraph's `Keyboard::make()->buttons([])` directly
   - Use `Button::make()` for inline buttons
   - Leverage `->action()` for callbacks, `->url()` for links

3. **Update Callback Data Strategy**
   - Remove `@method` notation from callback data
   - Telegraph automatically routes callback data to matching methods
   - Keep `CallbackData` constants, but simplify values (no method suffix)
   - Use `->param()` on buttons for passing data: `Button::make('Delete')->action('delete')->param('id', '42')`

### Phase 5: Conversation Flow Migration

**Current Pattern:**
```php
// Nutgram Conversation
class MeasurementConversation extends Conversation
{
    protected ?string $step = ConversationSteps::INPUT_DATE;

    public function inputDate(Nutgram $bot) {
        // Ask for date
        $bot->sendMessage($dateService->getDateInputInstructions());
        $this->next(ConversationSteps::INPUT_MEASUREMENT_VALUE);
    }

    public function inputMeasurementValue(Nutgram $bot) {
        // Validate and store date
        $this->next(ConversationSteps::SHOW_SUCCESS);
    }
}
```

**Telegraph Pattern:**

Telegraph doesn't have built-in conversation management. Two approaches:

**Approach A: Stateless Conversation via Callback Parameters**
```php
// Step 1: Show date selection
public function startMeasurement()
{
    $type = $this->data->get('type'); // 'chest', 'waist', etc.

    $keyboard = Keyboard::make()->buttons([
        Button::make('Сегодня')->action('measurementDate')->param('type', $type)->param('date', 'today'),
        Button::make('Вчера')->action('measurementDate')->param('type', $type)->param('date', 'yesterday'),
        Button::make('Выбрать дату')->action('measurementDateCustom')->param('type', $type),
    ]);

    $this->chat->message("Выберите дату для замера: {$type}")->keyboard($keyboard)->send();
}

// Step 2: Handle date selection
public function measurementDate()
{
    $type = $this->data->get('type');
    $date = $this->data->get('date');

    // Store in cache for next step
    cache()->put("measurement_flow_{$this->chat->id}", [
        'type' => $type,
        'date' => $date,
    ], now()->addMinutes(15));

    $this->chat->message("Введите значение замера {$type} в сантиметрах:")->send();
}

// Step 3: Handle text input
protected function handleChatMessage(Stringable $text): void
{
    $flow = cache()->get("measurement_flow_{$this->chat->id}");

    if ($flow && is_numeric($text)) {
        $this->saveMeasurement($flow['type'], (float)$text, $flow['date']);
        cache()->forget("measurement_flow_{$this->chat->id}");

        $this->chat->message("✅ Замер сохранен!")->keyboard($this->buildMainMenuKeyboard())->send();
    } else {
        parent::handleChatMessage($text);
    }
}
```

**Approach B: State Machine Pattern**
```php
// Use cache with conversation state
protected function handleChatMessage(Stringable $text): void
{
    $state = cache()->get("user_state_{$this->chat->id}");

    if (!$state) {
        // No active conversation
        $this->chat->message('Используйте меню для выбора действия.')->send();
        return;
    }

    match($state['step']) {
        'measurement_date_input' => $this->processMeasurementDateInput($text, $state),
        'measurement_value_input' => $this->processMeasurementValueInput($text, $state),
        'macro_value_input' => $this->processMacroValueInput($text, $state),
        'weight_value_input' => $this->processWeightValueInput($text, $state),
        default => $this->chat->message('Неизвестное состояние. Возврат в главное меню.')->send(),
    };
}

private function processMeasurementDateInput(Stringable $text, array $state)
{
    $dateService = app(DateValidationService::class);
    $result = $dateService->validateAndParseDate($text);

    if (!$result['valid']) {
        $this->chat->message($result['error'])->send();
        return;
    }

    cache()->put("user_state_{$this->chat->id}", [
        'step' => 'measurement_value_input',
        'type' => $state['type'],
        'date' => $result['formatted'],
    ], now()->addMinutes(15));

    $this->chat->message("Введите значение в сантиметрах:")->send();
}
```

**Migration Tasks:**

1. **Choose Conversation Strategy**
   - **Recommendation**: Use Approach B (State Machine) for consistency with existing architecture
   - Maintains similar flow structure to Nutgram conversations
   - Reuses existing services (`DateValidationService`, `ConversationHelper`)

2. **Migrate Conversation Classes**
   - `MeasurementConversation` → State machine handlers in `WebhookHandler`
   - `MacroConversation` → State machine handlers
   - `WeightConversation` → State machine handlers
   - `SyncConversation` → State machine handlers

3. **Implement State Management Helpers**
   - Create `TelegraphConversationState` service
   - Encapsulate state storage/retrieval logic
   - Migrate cache key patterns from `ConversationHelper`

4. **Update Text Input Routing**
   - Override `handleChatMessage(Stringable $text)` in handler
   - Route to appropriate state processor based on cached state
   - Implement validation and error recovery

### Phase 6: Service Integration

**Current Services:**
- `TelegramUserService` - User management
- `TelegramAccountService` - Account linking
- `TelegramFatSecretService` - FatSecret OAuth
- `TelegramMessageService` - Message formatting
- `DateValidationService` - Date parsing
- `ConversationHelper` - State management

**Migration Tasks:**

1. **Update Service Dependencies**
   - Services currently receive `Nutgram $bot` or use bot methods
   - Refactor to receive `TelegraphChat` models instead
   - Update method signatures to use Telegraph types

2. **Adapt TelegramUserService**
   ```php
   // Before
   public function getCurrentUser(int $userId): ?User

   // After
   public function getCurrentUser(TelegraphChat $chat): ?User
   {
       return $this->getUserByTelegramId($chat->chat_id);
   }
   ```

3. **Update TelegramAccountService**
   - Replace Nutgram message sending with `TelegraphChat` methods
   - Update link code generation to use `TelegraphChat` IDs

4. **Simplify TelegramMessageService**
   - Remove custom builders (`MessageBuilder`, `KeyboardBuilder`)
   - Use Telegraph's native message formatting
   - Keep localization logic

5. **Preserve Business Logic Services**
   - `DateValidationService` - No changes needed (pure logic)
   - FatSecret services - No changes needed
   - Only update integration points

### Phase 7: Middleware & Security

**Current Middleware:**
- `AccountLinkMiddleware` - Checks if user account is linked
- `FatSecretMiddleware` - Verifies FatSecret connection

**Telegraph Pattern:**

Telegraph uses standard Laravel middleware applied to webhook route.

**Migration Tasks:**

1. **Convert to Laravel HTTP Middleware**
   ```php
   // app/Http/Middleware/TelegramAccountLinkedMiddleware.php
   class TelegramAccountLinkedMiddleware
   {
       public function handle($request, Closure $next)
       {
           // Extract chat ID from request
           $update = json_decode($request->getContent(), true);
           $chatId = $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'] ?? null;

           if (!$chatId) {
               return $next($request);
           }

           $chat = TelegraphChat::where('chat_id', $chatId)->first();

           if (!$chat || !$chat->user_id) {
               // Send linking instructions
               $chat->message('Необходимо привязать аккаунт')->send();
               return response()->json(['ok' => true]);
           }

           return $next($request);
       }
   }
   ```

2. **Alternative: Handler-Level Guards**
   ```php
   // Inside WebhookHandler methods
   protected function requireLinkedAccount(): ?User
   {
       $user = $this->chat->user;

       if (!$user) {
           $this->chat->message('Аккаунт не привязан. Используйте /account для привязки.')
               ->keyboard($this->buildAccountLinkingKeyboard())
               ->send();
           return null;
       }

       return $user;
   }

   public function showMeasurements()
   {
       if (!$user = $this->requireLinkedAccount()) {
           return;
       }

       // Continue with measurements logic
   }
   ```

3. **Recommendation**
   - Use handler-level guards for cleaner logic
   - Apply HTTP middleware only for global security (rate limiting, IP filtering)
   - Leverage Telegraph's built-in security features (secret tokens, IP validation)

### Phase 8: Error Handling & Logging

**Current Approach:**
- Global exception handler in `TelegramServiceProvider`
- Manual try-catch blocks around message editing
- `ErrorHandlers` utility class

**Telegraph Approach:**

Override `onFailure()` in `WebhookHandler`:

```php
class FitnessCoachWebhookHandler extends WebhookHandler
{
    protected function onFailure(Throwable $throwable): void
    {
        // Log error
        logger()->error('Telegram bot error', [
            'exception' => $throwable->getMessage(),
            'trace' => $throwable->getTraceAsString(),
            'chat_id' => $this->chat?->chat_id,
            'user_id' => $this->chat?->user_id,
            'message' => $this->message?->text(),
            'callback_data' => $this->callbackQuery?->data(),
        ]);

        // Handle specific exceptions
        if ($throwable instanceof UserNotFoundException) {
            $this->chat->message('Пользователь не найден. Привяжите аккаунт.')
                ->keyboard($this->buildAccountLinkingKeyboard())
                ->send();
            return;
        }

        if ($throwable instanceof FatSecretException) {
            $this->chat->message('Ошибка FatSecret API. Попробуйте позже.')->send();
            return;
        }

        // Generic error message
        $this->chat->message('Произошла ошибка. Попробуйте позже или обратитесь в поддержку.')->send();
    }
}
```

**Migration Tasks:**

1. **Remove Global Exception Handler**
   - Delete exception handler from `TelegramServiceProvider`
   - Remove `ErrorHandlers` utility

2. **Implement `onFailure()` Method**
   - Centralized error handling in `WebhookHandler`
   - Maintain user-friendly Russian error messages
   - Preserve logging patterns

3. **Remove Try-Catch Blocks**
   - Telegraph handles message editing errors gracefully
   - Remove defensive try-catch around `editMessage()` calls

## File Changes Summary

### Files to Delete
```
app/Telegram/Commands/*                       # All command classes
app/Telegram/Conversations/*                  # All conversation classes
app/Telegram/Menus/*                         # All menu classes
app/Telegram/Builders/                       # Custom builders
app/Telegram/Handlers/ErrorHandlers.php      # Old error handling
app/Telegram/Providers/TelegramServiceProvider.php  # Old provider
app/Http/Controllers/Api/TelegramWebhookController.php  # Old webhook controller
```

### Files to Create
```
app/Telegram/Handlers/FitnessCoachWebhookHandler.php  # Main webhook handler
app/Telegram/Services/TelegraphConversationState.php   # State management
database/migrations/*_add_user_id_to_telegraph_chats.php  # Add user relationship
```

### Files to Modify
```
app/Telegram/Services/TelegramUserService.php      # Update to use TelegraphChat
app/Telegram/Services/TelegramAccountService.php   # Update to use TelegraphChat
app/Telegram/Constants/CallbackData.php            # Remove @method suffixes
composer.json                                       # Replace nutgram/laravel with defstudio/telegraph
config/services.php                                # Update telegram config
routes/api.php                                     # Remove manual webhook route (Telegraph handles it)
```

### Files to Keep (No Changes)
```
app/Telegram/Constants/*                      # Callback and command constants
app/Telegram/Services/DateValidationService.php  # Pure logic, no dependencies
app/Telegram/Services/TelegramFatSecretService.php  # Core logic stays
app/Telegram/Exceptions/*                     # Custom exceptions
app/Http/Controllers/Api/TelegramFatSecretAuthController.php  # OAuth controllers
app/Http/Controllers/Api/TelegramFatSecretCallbackController.php
app/Http/Controllers/Web/TelegramLinkController.php
app/Http/Controllers/Web/TelegramOAuthResultController.php
```
