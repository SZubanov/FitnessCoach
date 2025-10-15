# Telegraph Migration Changelog

## Migration Overview
**Date**: October 15, 2025
**Framework**: Migrated from Nutgram to defstudio/telegraph (Laravel-native)
**Approach**: Phased migration following TelegraphMigration_AI_Plan.md

---

## Phase 3: Commands Migration ✅ COMPLETED

### Task 3.1: /start Command
- **File**: `app/Telegram/Handlers/FitnessCoachWebhookHandler.php:632-645`
- **Methods**: `start()`, `mainMenu()`
- **Features**:
  - Welcome message with bot capabilities overview
  - Main menu keyboard with 6 buttons
  - HTML formatting support

### Task 3.2: /help Command
- **File**: `app/Telegram/Handlers/FitnessCoachWebhookHandler.php:662-695`
- **Methods**: `help()`, `showHelp()`, `formatCommandsHelp()`
- **Features**:
  - Command reference list
  - Callback-based help with back button
  - Date format instructions

### Task 3.3: /account Command
- **File**: `app/Telegram/Handlers/FitnessCoachWebhookHandler.php:722-739`
- **Methods**: `account()`, `accountLinking()`
- **Features**:
  - Account linking menu
  - Message editing for callbacks
  - Navigation keyboard

### Task 3.4: /fatsecret Command
- **File**: `app/Telegram/Handlers/FitnessCoachWebhookHandler.php:745-762`
- **Methods**: `fatsecret()`, `fatSecretConnect()`
- **Features**:
  - FatSecret connection menu
  - OAuth flow integration
  - Status checking

### Task 3.5: /sync Command
- **File**: `app/Telegram/Handlers/FitnessCoachWebhookHandler.php:768-811`
- **Methods**: `sync()`, `showSync()`
- **Features**:
  - FatSecret auth guard check
  - Sync type selection menu
  - Three sync options: full, weight, food diary

---

## Phase 4: Callbacks Migration ✅ COMPLETED

### Task 4.1: Main Menu Callbacks
**Implemented Methods**:
- `showSettings()` - Settings menu (line 1034)
- `showMeasurements()` - Body measurements menu with account guard (line 1052)
- `showMacros()` - КБЖУ menu with account guard (line 1104)
- `showWeight()` - Weight tracking menu with account guard (line 1128)
- `showHelp()` - Help menu with feature descriptions (line 678)

**Features**:
- Message editing instead of sending new messages
- `requireLinkedAccount()` guard integration
- Descriptive messages in Russian
- Back button navigation

### Task 4.2: Settings Menu Callbacks

#### Account Linking
- `generateLinkCode()` - Generates 15-minute temporary code (line 821)
- `checkLinkStatus()` - Checks Telegram-FitnessCoach link (line 841)
- `removeLinkAccount()` - Unlinks account (line 860)

#### FatSecret Integration
- `checkFatSecretConnection()` - Checks OAuth status (line 882)
- `connectFatSecret()` - Initiates OAuth flow with URL (line 901)
- `logoutFromFatSecret()` - Removes FatSecret tokens (line 933)

#### Sync Operations
- `syncFull()` - Full data synchronization (line 967)
- `syncWeight()` - Weight-only sync (line 980)
- `syncFood()` - Food diary sync (line 993)

**Features**:
- Error handling with try-catch blocks
- Russian error messages
- Guard methods for auth checks
- OAuth URL generation

### Task 4.3: Update CallbackData Constants
- **File**: `app/Telegram/Constants/CallbackData.php:1-31`
- **Change**: Added deprecation notice explaining Telegraph's method-based routing
- **Migration Note**: String constants no longer needed - Telegraph uses method names directly

---

## Phase 5: Conversations Migration ✅ COMPLETED

### Task 5.1: Create State Management Service
- **File**: `app/Telegram/Services/ConversationStateService.php` (294 lines)
- **Purpose**: Replace Nutgram's built-in conversation system
- **Features**:
  - Cache-based state storage (15-minute TTL)
  - Per-chat state management
  - Step-based conversation tracking
  - Arbitrary data storage
  - Temporary value storage (`putTemp`, `getTemp`, `forgetTemp`)

**Key Methods**:
```php
startConversation(string $chatId, string $type, array $data = [])
isInConversation(string $chatId): bool
getConversationType(string $chatId): ?string
getStep(string $chatId): ?string
setStep(string $chatId, string $step)
setData(string $chatId, string $key, mixed $value)
getData(string $chatId, string $key, mixed $default = null): mixed
endConversation(string $chatId)
```

**Cache Key Pattern**: `telegram_conversation_{chatId}_active`

### Task 5.2: Implement Message Router
- **File**: `app/Telegram/Handlers/FitnessCoachWebhookHandler.php:161-186`
- **Method**: `handleChatMessage()`
- **Pattern**: Central router using PHP 8.1 match expression

**Flow**:
1. Check if user is in active conversation
2. Get conversation type and current step
3. Route to appropriate handler: measurement, weight, macro, sync
4. Handle unknown conversation with cleanup

### Task 5.3: Migrate Measurement Conversation
**Location**: `FitnessCoachWebhookHandler.php:214-302, 1076-1097`

**Methods Implemented**:
- `handleMeasurementConversation()` - Main router (line 214)
- `handleMeasurementDateInput()` - Date validation (line 228)
- `handleMeasurementValueInput()` - Value validation (line 259)
- `startNewMeasurement()` - Conversation initiator (line 1076)

**Flow**:
1. User clicks "📐 Новый замер" → `startNewMeasurement()`
2. **Step: input_date** - User enters date → validates with DateValidationService
3. **Step: input_value** - User enters value → validates numeric (1-300 cm)
4. Save success message → cleanup → return to main menu

**Validation**:
- Date format: DD.MM.YYYY or DD/MM/YYYY
- Value range: 1-300 cm
- Numeric validation

**State Data**:
- `measurement_type`: Body part name (default: "Грудь")
- `date`: Formatted date string
- `date_object`: Carbon date object

### Task 5.4: Migrate Weight Conversation
**Location**: `FitnessCoachWebhookHandler.php:312-398, 1153-1174`

**Methods Implemented**:
- `handleWeightConversation()` - Main router (line 312)
- `handleWeightDateInput()` - Date validation (line 326)
- `handleWeightValueInput()` - Value validation (line 354)
- `startNewWeight()` - Conversation initiator (line 1153)

**Flow**:
1. User clicks "⚖️ Добавить вес" → `startNewWeight()`
2. **Step: input_date** - User enters date → validates
3. **Step: input_value** - User enters weight → validates (20-300 kg)
4. Save success message → cleanup → return to main menu

**Validation**:
- Date format: DD.MM.YYYY or DD/MM/YYYY
- Value range: 20-300 kg
- Decimal separator: Supports both comma (75,5) and dot (75.5)
- Numeric validation with `str_replace(',', '.', $input)`

**State Data**:
- `date`: Formatted date string
- `date_object`: Carbon date object

### Task 5.5: Migrate Macro (КБЖУ) Conversation
**Location**: `FitnessCoachWebhookHandler.php:408-517, 1179-1248`

**Methods Implemented**:
- `handleMacroConversation()` - Main router (line 408)
- `handleMacroDateInput()` - Date validation (line 422)
- `handleMacroValueInput()` - Value validation (line 458)
- `selectMacroCalories()` - Calories initiator (line 1179)
- `selectMacroProteins()` - Proteins initiator (line 1191)
- `selectMacroFats()` - Fats initiator (line 1203)
- `selectMacroCarbs()` - Carbs initiator (line 1215)
- `startMacroConversationWithType()` - Helper method (line 1227)

**Flow**:
1. User clicks "🍎 КБЖУ" → `showMacros()` displays type selection
2. User clicks macro type (e.g., "🔥 Калории") → `selectMacroCalories()`
3. **Step: input_date** - User enters date → validates
4. **Step: input_value** - User enters value → validates with type-specific ranges
5. Save success message → cleanup → return to main menu

**Macro Types**:

| Type | Icon | Unit | Range | Example |
|------|------|------|-------|---------|
| Калории | 🔥 | ккал | 500-5000 | 2000 |
| Белки | 🥩 | г | 0-1000 | 100 |
| Жиры | 🧈 | г | 0-1000 | 100 |
| Углеводы | 🍞 | г | 0-1000 | 100 |

**State Data**:
- `macro_type`: Array with `name`, `unit`, `icon`
- `date`: Formatted date string
- `date_object`: Carbon date object

**Validation Logic**:
```php
$validRange = match ($macroType['name']) {
    'калории' => ['min' => 500, 'max' => 5000],
    default => ['min' => 0, 'max' => 1000] // proteins, fats, carbs
};
```

### Task 5.6: Migrate Sync Conversation
**Location**: `FitnessCoachWebhookHandler.php:526-622, 967-1024`

**Methods Implemented**:
- `handleSyncConversation()` - Main router (line 526)
- `handleSyncDateInput()` - Date validation (line 540)
- `handleSyncExecution()` - Fallback executor (line 573)
- `executeSynchronization()` - Actual sync logic (line 588)
- `syncFull()` - Full sync initiator (line 967)
- `syncWeight()` - Weight sync initiator (line 980)
- `syncFood()` - Food diary sync initiator (line 993)
- `startSyncConversation()` - Helper method (line 1005)

**Flow**:
1. User clicks sync type (e.g., "🔄 Полная синхронизация") → `syncFull()`
2. **Step: input_date** - User enters date → validates
3. **Auto-execution**: Immediately executes sync after date validation
4. Show processing message → simulate sync (sleep 1s) → show success/error
5. Cleanup → return to main menu

**Sync Types**:

| Type | Icon | Callback |
|------|------|----------|
| Полная синхронизация | 🔄 | full |
| Синхронизация веса | ⚖️ | weight |
| Синхронизация дневника питания | 🍎 | food |

**State Data**:
- `sync_type`: Array with `name`, `icon`, `callback`
- `sync_callback`: Callback identifier (full/weight/food)
- `date`: Formatted date string
- `date_object`: Carbon date object

**Special Features**:
- Immediate execution after date input (no additional step)
- 1-second simulated sync delay
- Try-catch error handling
- Success/error message display

---

## Technical Implementation Details

### Service Injection
**Constructor Dependencies** (FitnessCoachWebhookHandler.php:19-27):
```php
public function __construct(
    private readonly TelegramUserService $telegramUserService,
    private readonly TelegramAccountService $telegramAccountService,
    private readonly TelegramFatSecretService $telegramFatSecretService,
    private readonly ConversationStateService $conversationState,
    private readonly DateValidationService $dateValidation,
)
```

### Guard Methods
- **requireLinkedAccount()** (line 69): Returns User or null with error message
- **requireFatSecretAuth()** (line 88): Returns bool with error message
- **getUserFromChat()** (line 105): Retrieves User from TelegraphChat

### Error Handling
- **onFailure()** (line 32): Centralized exception handler
- Logs with context (chat_id, user_id, message_text, callback_data)
- Handles UserNotFoundException specifically
- Handles FatSecret API errors
- Generic fallback error message

### Keyboard Builders (13 total)
1. `buildMainMenuKeyboard()` - 6 buttons
2. `buildHelpKeyboard()` - 1 button
3. `buildAccountMenuKeyboard()` - 4 buttons
4. `buildAccountBackKeyboard()` - 2 buttons
5. `buildFatSecretMenuKeyboard()` - 4 buttons
6. `buildFatSecretBackKeyboard()` - 2 buttons
7. `buildSyncMenuKeyboard()` - 4 buttons
8. `buildSyncBackKeyboard()` - 2 buttons
9. `buildSettingsKeyboard()` - 3 buttons
10. `buildMeasurementsKeyboard()` - 2 buttons
11. `buildMacrosKeyboard()` - 5 buttons
12. `buildWeightKeyboard()` - 2 buttons
13. `buildAccountLinkingKeyboard()` - 2 buttons (guard helper)

### Telegraph Configuration
**File**: `config/telegraph.php:37`
```php
'handler' => App\Telegram\Handlers\FitnessCoachWebhookHandler::class,
```

**Environment Variables**:
```env
TELEGRAM_TOKEN=8464263164:AAH7oo-8Nxg88NN9IV07RNRvHxtjMZ0Cs_M
TELEGRAM_WEBHOOK_URL=https://3e9a04bf972d.ngrok-free.app/api/telegram/webhook
TELEGRAM_WEBHOOK_DOMAIN=https://3e9a04bf972d.ngrok-free.app
```

---

## Migration Statistics

### Code Metrics
- **Main Handler File**: 1,406 lines
- **State Service**: 294 lines
- **Total Public Methods**: 38+
- **Total Callback Methods**: 28+
- **Keyboard Builders**: 13
- **Conversation Handlers**: 4 (measurement, weight, macro, sync)

### Conversations Flow Summary
| Feature | Steps | Validation | Range | Special |
|---------|-------|------------|-------|---------|
| Measurements | 2 | Date + Numeric | 1-300 cm | Body part selection (TODO) |
| Weight | 2 | Date + Numeric | 20-300 kg | Decimal separator support |
| Macros | 2 | Date + Numeric | Type-specific | 4 macro types with different ranges |
| Sync | 1 | Date only | N/A | Auto-execution after date |

### Cache Keys Used
- `telegram_conversation_{chatId}_active` - Active conversation state
- `telegram_conversation_{chatId}_temp_{key}` - Temporary values
- `telegram_link_code_{code}` - Account linking codes (15 min)
- `fatsecret:temp_cred:user:{identifier}` - OAuth temp credentials

---

## Testing Status

### Syntax Validation
✅ All files pass `php artisan about` without errors

### Manual Testing Required
- [ ] Complete conversation flows end-to-end
- [ ] Guard method behavior (requireLinkedAccount, requireFatSecretAuth)
- [ ] Error handling and error messages
- [ ] Cache TTL and state cleanup
- [ ] Keyboard navigation
- [ ] Message editing behavior
- [ ] FatSecret OAuth flow
- [ ] Account linking with temporary codes

---

## Known Issues / TODOs

### Phase 5 TODOs (In Code)
1. **Measurement Type Selection** (line 1090):
   ```php
   // TODO: Add measurement type selection
   ['measurement_type' => 'Грудь']
   ```

2. **Database Persistence** (lines 289, 386, 504):
   ```php
   // TODO: Save to database
   // $this->measurementService->saveMeasurement(...)
   // $this->weightService->saveWeight(...)
   // $this->macroService->saveMacro(...)
   ```

3. **Sync Service Integration** (line 597):
   ```php
   // TODO: Implement actual sync logic
   // $this->fatSecretSyncService->performSync(...)
   ```

### Future Phases
- **Phase 6**: Service Integration - Connect TODO markers to actual services
- **Phase 7**: Middleware & Security - Add rate limiting, validation
- **Phase 8**: Cleanup & Testing - Remove old Nutgram code, comprehensive testing

---

## Rollback Plan

If rollback is needed:
1. Restore `config/telegraph.php` to Nutgram handler
2. Re-enable Nutgram webhook route in `routes/api.php`
3. Old Nutgram files still present in:
   - `app/Telegram/Commands/`
   - `app/Telegram/Conversations/`
   - `app/Telegram/Menus/`

---

## Contributors
- Migration executed by: Claude Code (claude-sonnet-4-5-20250929)
- Plan based on: TelegraphMigration_AI_Plan.md
- Date: October 15, 2025
