# Telegraph Migration - Technical Documentation

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Conversation State Management](#conversation-state-management)
3. [Method-Based Routing](#method-based-routing)
4. [Guard Pattern](#guard-pattern)
5. [Keyboard Management](#keyboard-management)
6. [Error Handling](#error-handling)
7. [Date Validation](#date-validation)
8. [Code Examples](#code-examples)

---

## Architecture Overview

### Telegraph vs Nutgram Comparison

| Feature | Nutgram (Old) | Telegraph (New) |
|---------|---------------|-----------------|
| **Framework** | Standalone PHP library | Laravel-native package |
| **Commands** | Class-based with attributes | Public methods in handler |
| **Callbacks** | String-based routing | Method-based routing |
| **Conversations** | Built-in conversation system | Custom state management |
| **Menus** | Menu classes | Inline keyboard methods |
| **State** | Nutgram conversation state | Laravel Cache |
| **DI** | Manual injection | Laravel container |

### File Structure

```
app/Telegram/
├── Handlers/
│   └── FitnessCoachWebhookHandler.php    # Main handler (1406 lines)
├── Services/
│   ├── ConversationStateService.php       # State management (294 lines)
│   ├── DateValidationService.php          # Date validation (existing)
│   ├── TelegramUserService.php            # User management (existing)
│   ├── TelegramAccountService.php         # Account linking (existing)
│   └── TelegramFatSecretService.php       # FatSecret OAuth (existing)
├── Constants/
│   └── CallbackData.php                   # DEPRECATED - kept for reference
├── Commands/                              # OLD - Nutgram commands (kept)
├── Conversations/                         # OLD - Nutgram conversations (kept)
└── Menus/                                 # OLD - Nutgram menus (kept)
```

---

## Conversation State Management

### ConversationStateService Implementation

**Purpose**: Replace Nutgram's built-in conversation system with Laravel Cache-based state management.

### State Structure

```php
// Cache key: telegram_conversation_{chatId}_active
[
    'type' => 'measurement',           // conversation type
    'step' => 'input_value',          // current step
    'started_at' => '2025-10-15T...',  // ISO8601 timestamp
    'data' => [                        // arbitrary data
        'measurement_type' => 'Грудь',
        'date' => '15.10.2025',
        'date_object' => Carbon instance
    ]
]
```

### Core Methods

```php
// Start conversation
$this->conversationState->startConversation(
    chatId: $chatId,
    conversationType: 'measurement',
    initialData: ['measurement_type' => 'Грудь'],
    ttl: 900 // 15 minutes
);

// Check if in conversation
if ($this->conversationState->isInConversation($chatId)) {
    // Handle conversation
}

// Get conversation type
$type = $this->conversationState->getConversationType($chatId);
// Returns: 'measurement', 'weight', 'macro', 'sync', or null

// Get current step
$step = $this->conversationState->getStep($chatId);
// Returns: 'input_date', 'input_value', etc.

// Set step
$this->conversationState->setStep($chatId, 'input_value');

// Store data
$this->conversationState->setData($chatId, 'date', '15.10.2025');

// Retrieve data
$date = $this->conversationState->getData($chatId, 'date');
$date = $this->conversationState->getData($chatId, 'date', 'default');

// Get all data
$allData = $this->conversationState->getAllData($chatId);

// End conversation (cleanup)
$this->conversationState->endConversation($chatId);
```

### Temporary Storage

For single-use data between menu selections:

```php
// Store temporary value (15 min TTL)
$this->conversationState->putTemp($chatId, 'sync_type', 'full');

// Retrieve temporary value
$syncType = $this->conversationState->getTemp($chatId, 'sync_type');

// Remove temporary value
$this->conversationState->forgetTemp($chatId, 'sync_type');

// Clear all common temp keys
$this->conversationState->clearAllTemp($chatId);
```

### Cache Key Patterns

```php
// Active conversation
private const STATE_PREFIX = 'telegram_conversation';
"telegram_conversation_{chatId}_active"

// Temporary values
"telegram_conversation_{chatId}_temp_{key}"
```

---

## Method-Based Routing

### Telegraph Callback Pattern

Instead of string-based callbacks, Telegraph uses method names:

```php
// Button definition
Button::make('📏 Замеры')->action('showMeasurements')

// Handler method
public function showMeasurements(): void
{
    // Method is called automatically by Telegraph
}
```

### Command Handling

Telegraph automatically routes commands to public methods:

```php
// User sends: /start
// Telegraph calls:
public function start(): void
{
    // Command implementation
}

// User sends: /help
// Telegraph calls:
public function help(): void
{
    // Command implementation
}
```

### Message Routing

Non-command messages go through `handleChatMessage()`:

```php
protected function handleChatMessage(Stringable $text): void
{
    $chatId = (string) $this->chat->chat_id;

    if (!$this->conversationState->isInConversation($chatId)) {
        $this->chat->html("💬 Я понимаю только команды...")->send();
        return;
    }

    $conversationType = $this->conversationState->getConversationType($chatId);
    $step = $this->conversationState->getStep($chatId);

    match ($conversationType) {
        'measurement' => $this->handleMeasurementConversation($text, $step),
        'weight' => $this->handleWeightConversation($text, $step),
        'macro' => $this->handleMacroConversation($text, $step),
        'sync' => $this->handleSyncConversation($text, $step),
        default => $this->handleUnknownConversation($chatId),
    };
}
```

### Conversation Router Pattern

Each conversation has a dedicated router method:

```php
private function handleMeasurementConversation(Stringable $text, ?string $step): void
{
    $chatId = (string) $this->chat->chat_id;

    match ($step) {
        'input_date' => $this->handleMeasurementDateInput($text, $chatId),
        'input_value' => $this->handleMeasurementValueInput($text, $chatId),
        default => $this->handleUnknownConversation($chatId),
    };
}
```

---

## Guard Pattern

### Purpose
Guards protect routes by checking authentication state before allowing access.

### Implementation

#### Linked Account Guard

```php
protected function requireLinkedAccount(): ?User
{
    $user = $this->getUserFromChat();

    if (!$user) {
        $this->chat->message('❌ Аккаунт не привязан...')
            ->keyboard($this->buildAccountLinkingKeyboard())
            ->send();
        return null;
    }

    return $user;
}
```

**Usage**:
```php
public function startNewMeasurement(): void
{
    // Guard check - exit early if not linked
    if (!$this->requireLinkedAccount()) {
        return;
    }

    // Proceed with conversation start
    $chatId = (string) $this->chat->chat_id;
    $this->conversationState->startConversation($chatId, 'measurement', []);
}
```

#### FatSecret Auth Guard

```php
protected function requireFatSecretAuth(): bool
{
    $user = $this->getUserFromChat();

    if (!$user || !$user->isFatSecretAuthorized()) {
        $this->chat->message('❌ FatSecret не подключен...')
            ->keyboard($this->buildFatSecretKeyboard())
            ->send();
        return false;
    }

    return true;
}
```

**Usage**:
```php
public function sync(): void
{
    // Guard check - exit early if not authorized
    if (!$this->requireFatSecretAuth()) {
        return;
    }

    // Show sync menu
    $this->chat->html($instructionsText)
        ->keyboard($this->buildSyncMenuKeyboard())
        ->send();
}
```

### Guard Flow Diagram

```
User Action
    ↓
Guard Method
    ↓
   Check Auth State
    ↓
  ┌─────────────┐
  │  Authorized? │
  └─────────────┘
    ↓         ↓
   YES       NO
    ↓         ↓
 Return    Send Error
  User     Message
    ↓         ↓
Continue  Return
 Action   Early
```

---

## Keyboard Management

### Keyboard Builder Pattern

All keyboards are built using protected methods:

```php
protected function buildMainMenuKeyboard(): Keyboard
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
```

### Button Types

#### Action Buttons
```php
Button::make('Text')->action('methodName')
// Calls public function methodName() in handler
```

#### URL Buttons
```php
Button::make('Open Link')->url('https://example.com')
// Opens URL in browser
```

### Keyboard Layouts

Telegraph automatically arranges buttons in rows based on button count.

**2-column layout** (4+ buttons):
```php
Keyboard::make()->buttons([
    Button::make('A')->action('a'),  // Row 1, Col 1
    Button::make('B')->action('b'),  // Row 1, Col 2
    Button::make('C')->action('c'),  // Row 2, Col 1
    Button::make('D')->action('d'),  // Row 2, Col 2
]);
```

**Single column** (explicit):
```php
Keyboard::make()->buttons([
    Button::make('A')->action('a'),
])->chunk(1);  // Force 1 button per row
```

### Message with Keyboard

```php
$this->chat->html('Message text')
    ->keyboard($this->buildMainMenuKeyboard())
    ->send();
```

### Message Editing

For callback responses, edit existing message:

```php
$this->chat->edit($this->messageId)
    ->html('Updated text')
    ->keyboard($this->buildSomeKeyboard())
    ->send();
```

---

## Error Handling

### Centralized Error Handler

```php
protected function onFailure(Throwable $throwable): void
{
    // 1. Log with context
    logger()->error('Telegram bot error', [
        'exception' => $throwable->getMessage(),
        'exception_class' => get_class($throwable),
        'trace' => $throwable->getTraceAsString(),
        'chat_id' => $this->chat?->chat_id,
        'user_id' => $this->chat?->user_id,
        'message_text' => $this->message?->text(),
        'callback_data' => $this->callbackQuery?->data(),
    ]);

    // 2. Handle specific exceptions
    if ($throwable instanceof UserNotFoundException) {
        $this->chat->message('❌ Пользователь не найден...')
            ->keyboard($this->buildAccountLinkingKeyboard())
            ->send();
        return;
    }

    // 3. Handle FatSecret errors
    if (str_contains(get_class($throwable), 'FatSecret')) {
        $this->chat->message('❌ Ошибка FatSecret API...')
            ->send();
        return;
    }

    // 4. Generic fallback
    $this->chat->message('❌ Произошла ошибка...')
        ->send();
}
```

### Error Flow Diagram

```
Exception Thrown
    ↓
onFailure() Called
    ↓
Log Error with Context
    ↓
Check Exception Type
    ↓
  ┌────────────────┐
  │ Match Type?    │
  └────────────────┘
    ↓          ↓
  Specific   Generic
  Handler    Handler
    ↓          ↓
  Custom     Standard
  Message    Message
    ↓          ↓
  Send to User
```

### Try-Catch in Methods

For expected errors, use try-catch:

```php
public function connectFatSecret(): void
{
    $user = $this->requireLinkedAccount();
    if (!$user) {
        return;
    }

    try {
        $oauthUrl = $this->telegramFatSecretService->initiateOAuthForTelegram($user);

        $this->chat->edit($this->messageId)
            ->html("🔗 **Подключение FatSecret**\n\n[Подключить]({$oauthUrl})")
            ->keyboard($this->buildFatSecretBackKeyboard())
            ->send();

    } catch (\Exception $e) {
        $this->chat->edit($this->messageId)
            ->html('❌ Ошибка при создании ссылки...')
            ->keyboard($this->buildFatSecretBackKeyboard())
            ->send();
    }
}
```

---

## Date Validation

### DateValidationService Integration

The existing DateValidationService is used across all conversations:

```php
private readonly DateValidationService $dateValidation;
```

### Validation Method

```php
$dateResult = $this->dateValidation->validateAndParseDate($userInput);

// Returns array:
[
    'valid' => true|false,
    'formatted' => '15.10.2025',
    'date' => Carbon instance,
    'error' => 'Error message if invalid'
]
```

### Usage Pattern

```php
private function handleWeightDateInput(Stringable $text, string $chatId): void
{
    // Validate date
    $dateResult = $this->dateValidation->validateAndParseDate((string) $text);

    if (!$dateResult['valid']) {
        // Send error message and return
        $this->chat->html($dateResult['error'])->send();
        return;
    }

    // Store validated date
    $this->conversationState->setData($chatId, 'date', $dateResult['formatted']);
    $this->conversationState->setData($chatId, 'date_object', $dateResult['date']);

    // Move to next step
    $this->conversationState->setStep($chatId, 'input_value');
}
```

### Date Input Instructions

```php
$instructions = $this->dateValidation->getDateInputInstructions();
$this->chat->html($instructions)->send();
```

Outputs:
```
📅 Введите дату

Формат: ДД.ММ.ГГГГ или ДД/ММ/ГГГГ
Например: 15.10.2025 или 15/10/2025
```

---

## Code Examples

### Example 1: Simple Callback Handler

```php
public function showHelp(): void
{
    $helpText = "📖 **Помощь FitnessCoach Bot**\n\n" .
               "**Доступные функции:**\n" .
               "📏 **Замеры** - Записывайте измерения тела\n" .
               "🔄 **Синхронизация** - Синхронизация с FatSecret\n";

    $this->chat->edit($this->messageId)
        ->html($helpText)
        ->keyboard($this->buildHelpKeyboard())
        ->send();
}
```

### Example 2: Guard-Protected Callback

```php
public function startNewMeasurement(): void
{
    // Guard check
    if (!$this->requireLinkedAccount()) {
        return;  // Error message already sent by guard
    }

    $chatId = (string) $this->chat->chat_id;

    // Start conversation
    $this->conversationState->startConversation(
        $chatId,
        'measurement',
        ['measurement_type' => 'Грудь']
    );

    $this->conversationState->setStep($chatId, 'input_date');

    // Show date input instructions
    $instructions = $this->dateValidation->getDateInputInstructions();
    $this->chat->html($instructions)->send();
}
```

### Example 3: Type Selection Pattern

```php
// Callback methods for each type
public function selectMacroCalories(): void
{
    $this->startMacroConversationWithType([
        'name' => 'калории',
        'unit' => 'ккал',
        'icon' => '🔥'
    ]);
}

public function selectMacroProteins(): void
{
    $this->startMacroConversationWithType([
        'name' => 'белки',
        'unit' => 'г',
        'icon' => '🥩'
    ]);
}

// Helper method
private function startMacroConversationWithType(array $macroType): void
{
    if (!$this->requireLinkedAccount()) {
        return;
    }

    $chatId = (string) $this->chat->chat_id;

    $this->conversationState->startConversation(
        $chatId,
        'macro',
        ['macro_type' => $macroType]
    );

    $this->conversationState->setStep($chatId, 'input_date');

    $instructions = $this->dateValidation->getDateInputInstructions();
    $this->chat->html($instructions)->send();
}
```

### Example 4: Date Input Handler

```php
private function handleMeasurementDateInput(Stringable $text, string $chatId): void
{
    // Step 1: Validate date
    $dateResult = $this->dateValidation->validateAndParseDate((string) $text);

    if (!$dateResult['valid']) {
        $this->chat->html($dateResult['error'])->send();
        return;
    }

    // Step 2: Store validated date
    $this->conversationState->setData($chatId, 'date', $dateResult['formatted']);
    $this->conversationState->setData($chatId, 'date_object', $dateResult['date']);

    // Step 3: Move to next step
    $this->conversationState->setStep($chatId, 'input_value');

    // Step 4: Get stored data
    $measurementType = $this->conversationState->getData($chatId, 'measurement_type', 'замер');

    // Step 5: Ask for value
    $this->chat->html(
        "📏 **Замер: {$measurementType}**\n\n" .
        "Введите значение в сантиметрах:\n" .
        "Например: 95"
    )->send();
}
```

### Example 5: Value Input Handler with Validation

```php
private function handleWeightValueInput(Stringable $text, string $chatId): void
{
    $valueInput = trim((string) $text);

    // Support both comma and dot as decimal separator
    $valueInput = str_replace(',', '.', $valueInput);

    // Validate numeric
    if (!is_numeric($valueInput)) {
        $this->chat->html(
            "❌ **Неверное значение**\n\n" .
            "Введите число (вес в килограммах):\n" .
            "Например: 70.5 или 85,2"
        )->send();
        return;
    }

    $value = (float) $valueInput;

    // Validate range
    if ($value < 20 || $value > 300) {
        $this->chat->html(
            "❌ **Значение вне допустимого диапазона**\n\n" .
            "Введите вес от 20 до 300 кг:"
        )->send();
        return;
    }

    // Get stored date
    $date = $this->conversationState->getData($chatId, 'date');

    // TODO: Save to database
    // $this->weightService->saveWeight($userId, $value, $date);

    // Show success
    $this->chat->html(
        "✅ **Вес сохранен**\n\n" .
        "Значение: {$value} кг\n" .
        "Дата: {$date}"
    )->send();

    // Cleanup and return to menu
    $this->conversationState->endConversation($chatId);
    $this->mainMenu();
}
```

### Example 6: Type-Specific Validation

```php
private function handleMacroValueInput(Stringable $text, string $chatId): void
{
    $valueInput = trim((string) $text);

    if (!is_numeric($valueInput)) {
        // Show error with type-specific example
        $macroType = $this->conversationState->getData($chatId, 'macro_type');
        $example = $macroType['name'] === 'калории' ? '2000' : '100';

        $this->chat->html(
            "❌ **Неверное значение**\n\n" .
            "Введите число в {$macroType['unit']}:\n" .
            "Например: {$example}"
        )->send();
        return;
    }

    $value = (float) $valueInput;
    $macroType = $this->conversationState->getData($chatId, 'macro_type');

    // Type-specific range validation
    $validRange = match ($macroType['name']) {
        'калории' => ['min' => 500, 'max' => 5000],
        default => ['min' => 0, 'max' => 1000]
    };

    if ($value < $validRange['min'] || $value > $validRange['max']) {
        $this->chat->html(
            "❌ **Значение вне допустимого диапазона**\n\n" .
            "Введите значение от {$validRange['min']} до {$validRange['max']} {$macroType['unit']}:"
        )->send();
        return;
    }

    $date = $this->conversationState->getData($chatId, 'date');

    // TODO: Save to database

    $this->chat->html(
        "✅ **КБЖУ сохранено**\n\n" .
        "Тип: {$macroType['name']}\n" .
        "Значение: {$value} {$macroType['unit']}\n" .
        "Дата: {$date}"
    )->send();

    $this->conversationState->endConversation($chatId);
    $this->mainMenu();
}
```

---

## Performance Considerations

### Cache Usage
- **TTL**: 15 minutes (900 seconds) default
- **Driver**: Redis (configured in .env)
- **Keys**: Prefixed with `telegram_conversation_`
- **Cleanup**: Automatic via TTL + manual `endConversation()`

### Memory Management
```php
// Always cleanup after conversation completion
$this->conversationState->endConversation($chatId);

// Clear temporary values when no longer needed
$this->conversationState->forgetTemp($chatId, 'sync_type');
```

### Database Queries
- User lookup cached via TelegramUserService
- OAuth state stored in cache, not database
- Link codes temporary (15 min TTL)

---

## Security Considerations

### Input Validation
1. **Numeric validation**: `is_numeric()` check before casting
2. **Range validation**: Min/max checks for all numeric inputs
3. **Date validation**: Via DateValidationService
4. **HTML escaping**: Telegraph handles automatically

### Authentication
1. **Account linking**: Temporary codes with 15-minute expiration
2. **FatSecret OAuth**: Standard OAuth1 flow
3. **Guard methods**: Protect all sensitive operations
4. **Chat ID**: Validated via Telegraph framework

### Rate Limiting
- TODO: Add rate limiting in Phase 7
- TODO: Add validation middleware in Phase 7

---

## Testing Guide

### Manual Testing Checklist

#### Commands
- [ ] /start - Shows welcome + main menu
- [ ] /help - Shows command list
- [ ] /account - Shows account menu
- [ ] /fatsecret - Shows FatSecret menu
- [ ] /sync - Shows sync menu (requires FatSecret auth)

#### Measurement Conversation
- [ ] Click "📏 Замеры" → "📐 Новый замер"
- [ ] Enter date: 15.10.2025
- [ ] Enter value: 95
- [ ] Verify success message
- [ ] Verify return to main menu

#### Weight Conversation
- [ ] Click "⚖️ Вес" → "⚖️ Добавить вес"
- [ ] Enter date: 15.10.2025
- [ ] Enter weight: 75.5 (test dot)
- [ ] Enter weight: 75,5 (test comma)
- [ ] Verify success message

#### Macro Conversation
- [ ] Click "🍎 КБЖУ" → "🔥 Калории"
- [ ] Enter date: 15.10.2025
- [ ] Enter value: 2000
- [ ] Verify success message
- [ ] Repeat for: Белки, Жиры, Углеводы

#### Sync Conversation
- [ ] Ensure FatSecret connected
- [ ] Click "🔄 Синхронизация" → "🔄 Полная синхронизация"
- [ ] Enter date: 15.10.2025
- [ ] Verify processing message
- [ ] Verify success/error message

#### Error Cases
- [ ] Invalid date format: "abc"
- [ ] Out of range: measurement > 300
- [ ] Out of range: weight < 20 or > 300
- [ ] Out of range: calories < 500 or > 5000
- [ ] Non-numeric input where numeric expected
- [ ] Action without account linked
- [ ] Sync without FatSecret auth

### Unit Testing (Future)

```php
// Example test structure
class ConversationStateServiceTest extends TestCase
{
    public function test_start_conversation_creates_state()
    {
        $service = new ConversationStateService();
        $service->startConversation('12345', 'measurement', []);

        $this->assertTrue($service->isInConversation('12345'));
        $this->assertEquals('measurement', $service->getConversationType('12345'));
    }

    public function test_end_conversation_cleans_up()
    {
        $service = new ConversationStateService();
        $service->startConversation('12345', 'measurement', []);
        $service->endConversation('12345');

        $this->assertFalse($service->isInConversation('12345'));
    }
}
```

---

## Debugging

### Enable Telegraph Webhook Debug

```env
TELEGRAPH_WEBHOOK_DEBUG=true
```

This will dump all incoming webhook messages to logs.

### Check Conversation State

```php
// In handler method
$state = Cache::get("telegram_conversation_{$chatId}_active");
dd($state);
```

### Laravel Telescope

Install and use Telescope for request/query debugging:

```bash
docker exec coach_fpm php artisan telescope:install
docker exec coach_fpm php artisan migrate
```

### Log Locations

```bash
# Laravel logs
docker exec coach_fpm tail -f storage/logs/laravel.log

# Telegraph webhook logs (if debug enabled)
docker exec coach_fpm grep "Telegraph" storage/logs/laravel.log
```

---

## Common Issues & Solutions

### Issue: Conversation state not persisting
**Cause**: Redis not running or cache driver misconfigured
**Solution**:
```bash
docker-compose ps redis
# Check CACHE_DRIVER=redis in .env
```

### Issue: Callback not routing to method
**Cause**: Method not public or typo in action name
**Solution**: Ensure method is `public` and action name matches exactly

### Issue: Message editing fails
**Cause**: Using `send()` instead of `edit()` for callback responses
**Solution**:
```php
// For callbacks, use edit():
$this->chat->edit($this->messageId)->html('text')->send();

// For new messages, use html():
$this->chat->html('text')->send();
```

### Issue: Date validation always fails
**Cause**: DateValidationService not injected or returning wrong format
**Solution**: Check DateValidationService returns array with 'valid' key

### Issue: Guard not working
**Cause**: TelegramUserService not finding user or chat not linked
**Solution**: Check telegraph_chats table and user relationship

---

## Next Steps (Phase 6-8)

### Phase 6: Service Integration
- [ ] Implement MeasurementService::saveMeasurement()
- [ ] Implement WeightService::saveWeight()
- [ ] Implement MacroService::saveMacro()
- [ ] Implement FatSecretSyncService::performSync()
- [ ] Add measurement type selection dialog

### Phase 7: Middleware & Security
- [ ] Add rate limiting middleware
- [ ] Add input validation middleware
- [ ] Add CSRF protection for web callbacks
- [ ] Add user activity logging

### Phase 8: Cleanup & Testing
- [ ] Remove old Nutgram files
- [ ] Write comprehensive tests
- [ ] Performance optimization
- [ ] Documentation updates

---

## References

- [Telegraph Documentation](https://docs.defstudio.it/telegraph/)
- [Laravel Cache Documentation](https://laravel.com/docs/10.x/cache)
- [Telegram Bot API](https://core.telegram.org/bots/api)
- [Project CLAUDE.md](../CLAUDE.md)
- [Migration Plan](TelegraphMigration_AI_Plan.md)
- [Changelog](TELEGRAPH_MIGRATION_CHANGELOG.md)
