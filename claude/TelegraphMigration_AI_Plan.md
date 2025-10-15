# Telegraph Migration: AI-Assisted Development Plan

## Overview

This plan breaks down the Nutgram → Telegraph migration into **actionable, AI-friendly tasks**. Each task is designed to be completed in a single AI coding session with clear inputs, outputs, and validation criteria.

## Prerequisites

- Telegraph documentation available via Context7 MCP
- Access to codebase at `/Users/szubanov/Projects/FitnessCoach`
- Docker environment running (`coach_fpm` container)
- Understanding of existing Nutgram implementation

## Development Workflow

Each task follows this pattern:

1. **AI Request**: What to ask AI to do
2. **Context Files**: Files AI should read
3. **Expected Output**: What AI should produce
4. **Validation**: How to verify the task is complete
5. **Rollback**: How to undo if needed

---

## Phase 1: Setup & Database Migration

### Task 1.1: Install Telegraph Package

**AI Request:**
```
Install defstudio/telegraph package:
1. Add to composer.json
2. Show me the commands to run in Docker
3. Publish Telegraph config and migrations
```

**Context Files:**
- `composer.json`
- `.env`

**Expected Output:**
- Updated `composer.json` with `"defstudio/telegraph": "^1.0"`
- Commands to run:
  ```bash
  docker exec coach_fpm composer require defstudio/telegraph
  docker exec coach_fpm php artisan vendor:publish --tag=telegraph-config
  docker exec coach_fpm php artisan vendor:publish --tag=telegraph-migrations
  docker exec coach_fpm php artisan migrate
  ```

**Validation:**
- ✓ `telegraph_bots` table exists
- ✓ `telegraph_chats` table exists
- ✓ `config/telegraph.php` exists

**Rollback:**
```bash
docker exec coach_fpm php artisan migrate:rollback
composer remove defstudio/telegraph
```

---

### Task 1.2: Create Bot Record & Data Migration

**AI Request:**
```
Create a migration or seeder to:
1. Insert TelegraphBot record with token from TELEGRAM_TOKEN env
2. Migrate existing users with telegram_id to TelegraphChat records
3. Link TelegraphChat records to the bot
4. Add user_id column to telegraph_chats if not exists
```

**Context Files:**
- `.env` (for TELEGRAM_TOKEN)
- `app/Models/User.php`
- `database/migrations/*create_users_table.php`

**Expected Output:**
- `database/migrations/YYYY_MM_DD_HHMMSS_setup_telegraph_bot_and_chats.php`
- Migration handles:
  - Creating bot record
  - Adding `user_id` foreign key to `telegraph_chats`
  - Migrating existing telegram users

**Validation:**
- ✓ Run migration successfully
- ✓ Check bot record exists: `SELECT * FROM telegraph_bots;`
- ✓ Check chat records: `SELECT COUNT(*) FROM telegraph_chats;`

**Rollback:**
```bash
docker exec coach_fpm php artisan migrate:rollback
```

---

### Task 1.3: Configure Telegraph

**AI Request:**
```
Update config/telegraph.php:
1. Set custom webhook handler to FitnessCoachWebhookHandler (we'll create it next)
2. Configure security settings (allow unknown chats, store in DB)
3. Set middleware array (empty for now)
```

**Context Files:**
- `config/telegraph.php`

**Expected Output:**
```php
'webhook' => [
    'handler' => \App\Telegram\Handlers\FitnessCoachWebhookHandler::class,
    'middleware' => [],
],
'security' => [
    'allow_callback_queries_from_unknown_chats' => true,
    'allow_messages_from_unknown_chats' => true,
    'store_unknown_chats_in_db' => true,
],
```

**Validation:**
- ✓ Config file syntax valid
- ✓ Handler class path correct (will exist in next phase)

**Rollback:**
- Revert config changes via git

---

## Phase 2: Core Webhook Handler

### Task 2.1: Create Base WebhookHandler

**AI Request:**
```
Create app/Telegram/Handlers/FitnessCoachWebhookHandler.php:
1. Extend DefStudio\Telegraph\Handlers\WebhookHandler
2. Inject services via constructor (TelegramUserService, etc.)
3. Implement onFailure() method with error handling for:
   - UserNotFoundException
   - FatSecretException
   - Generic exceptions
4. Add protected helper methods:
   - requireLinkedAccount(): ?User
   - getUserFromChat(): ?User
```

**Context Files:**
- `app/Telegram/Exceptions/UserNotFoundException.php`
- `app/Telegram/Exceptions/UnlinkTelegramAccountException.php`
- `app/Telegram/Services/TelegramUserService.php`
- `@TelegraphMigrationPlan.md` (Phase 8 section)

**Expected Output:**
- New file: `app/Telegram/Handlers/FitnessCoachWebhookHandler.php`
- Skeleton with:
  - Constructor with DI
  - `onFailure()` implementation
  - Helper methods stubbed
  - Russian error messages

**Validation:**
- ✓ File created
- ✓ No syntax errors: `docker exec coach_fpm php artisan about`
- ✓ Class exists: Check via IDE or `php artisan tinker`

**Rollback:**
- Delete file

---

### Task 2.2: Remove Old Webhook Controller

**AI Request:**
```
1. Comment out or delete app/Http/Controllers/Api/TelegramWebhookController.php
2. Remove the webhook route from routes/api.php
3. Verify Telegraph's automatic routing is configured
```

**Context Files:**
- `app/Http/Controllers/Api/TelegramWebhookController.php`
- `routes/api.php`

**Expected Output:**
- Route `POST /api/telegram/webhook` removed or commented
- Controller file deleted or moved to `_old/` directory

**Validation:**
- ✓ No errors when running: `docker exec coach_fpm php artisan route:list | grep telegram`
- ✓ Telegraph route registered automatically

**Rollback:**
- Restore route and controller from git

---

## Phase 3: Command Migration

### Task 3.1: Migrate /start Command

**AI Request:**
```
In FitnessCoachWebhookHandler, add start() method:
1. Read app/Telegram/Commands/StartCommand.php
2. Convert to Telegraph pattern
3. Create buildMainMenuKeyboard() helper
4. Use Telegraph's Keyboard::make()->buttons() API
5. Send welcome message + keyboard
```

**Context Files:**
- `app/Telegram/Commands/StartCommand.php`
- `app/Telegram/Menus/MainMenu.php`
- `app/Telegram/Constants/CallbackData.php`
- Telegraph docs (via MCP)

**Expected Output:**
- New methods in `FitnessCoachWebhookHandler`:
  - `public function start()`
  - `protected function buildMainMenuKeyboard(): Keyboard`
- Using Telegraph's API for keyboard buttons

**Validation:**
- ✓ Send `/start` to bot via Telegram
- ✓ Receive welcome message with keyboard
- ✓ Keyboard buttons appear correctly

**Rollback:**
- Delete methods from handler

---

### Task 3.2: Migrate /help Command

**AI Request:**
```
Add help() method to FitnessCoachWebhookHandler:
1. Read app/Telegram/Commands/HelpCommand.php
2. Convert to Telegraph pattern
3. Use localization strings if available
4. Include back-to-menu keyboard
```

**Context Files:**
- `app/Telegram/Commands/HelpCommand.php`
- `app/Telegram/Menus/MainMenu.php` (help display)
- Localization files: `resources/lang/ru/telegram.php` (if exists)

**Expected Output:**
- New method: `public function help()`
- Help text display with keyboard

**Validation:**
- ✓ Send `/help` to bot
- ✓ Receive help text
- ✓ Can navigate back to main menu

**Rollback:**
- Delete method

---

### Task 3.3: Migrate /account Command

**AI Request:**
```
Add account() method to FitnessCoachWebhookHandler:
1. Read app/Telegram/Commands/AccountCommand.php
2. Read app/Telegram/Menus/AccountLinkingMenu.php
3. Convert to Telegraph pattern
4. Integrate with TelegramAccountService
5. Create buildAccountLinkingKeyboard() helper
```

**Context Files:**
- `app/Telegram/Commands/AccountCommand.php`
- `app/Telegram/Menus/AccountLinkingMenu.php`
- `app/Telegram/Services/TelegramAccountService.php`
- `app/Telegram/Builders/MessageBuilder/MessageBuilder.php`

**Expected Output:**
- New methods:
  - `public function account()`
  - `protected function buildAccountLinkingKeyboard(): Keyboard`
- Account linking flow working

**Validation:**
- ✓ Send `/account` to bot
- ✓ Receive link code generation message
- ✓ Keyboard allows linking/checking status

**Rollback:**
- Delete methods

---

### Task 3.4: Migrate /fatsecret Command

**AI Request:**
```
Add fatsecret() method to FitnessCoachWebhookHandler:
1. Read app/Telegram/Commands/FatSecretCommand.php
2. Read app/Telegram/Menus/FatSecretConnectionMenu.php
3. Convert to Telegraph pattern
4. Integrate with TelegramFatSecretService
5. Create buildFatSecretKeyboard() helper
```

**Context Files:**
- `app/Telegram/Commands/FatSecretCommand.php`
- `app/Telegram/Menus/FatSecretConnectionMenu.php`
- `app/Telegram/Services/TelegramFatSecretService.php`

**Expected Output:**
- New methods:
  - `public function fatsecret()`
  - `protected function buildFatSecretKeyboard(): Keyboard`

**Validation:**
- ✓ Send `/fatsecret` to bot
- ✓ Keyboard shows connect/disconnect options
- ✓ Can trigger OAuth flow (may not complete without web setup)

**Rollback:**
- Delete methods

---

### Task 3.5: Migrate /sync Command

**AI Request:**
```
Add sync() method to FitnessCoachWebhookHandler:
1. Read app/Telegram/Commands/SyncCommand.php (if exists)
2. Read app/Telegram/Menus/SyncMenu.php
3. Convert to Telegraph pattern
4. Create buildSyncMenuKeyboard() helper
```

**Context Files:**
- `app/Telegram/Menus/SyncMenu.php`
- `app/Telegram/Constants/CallbackData.php`

**Expected Output:**
- New methods:
  - `public function sync()`
  - `protected function buildSyncMenuKeyboard(): Keyboard`

**Validation:**
- ✓ Send `/sync` or access via menu
- ✓ Sync options displayed

**Rollback:**
- Delete methods

---

## Phase 4: Callback Handler Migration

### Task 4.1: Migrate Main Menu Callbacks

**AI Request:**
```
Add callback methods for main menu navigation:
1. Read app/Telegram/Menus/MainMenu.php
2. Implement these methods in FitnessCoachWebhookHandler:
   - mainMenu() / showMainMenu()
   - showSettings()
   - showMeasurements()
   - showSync()
   - showMacros()
   - showWeight()
3. Each method should edit the current message with new keyboard
4. Use $this->chat->edit($this->messageId)
```

**Context Files:**
- `app/Telegram/Menus/MainMenu.php`
- `app/Telegram/Constants/CallbackData.php`

**Expected Output:**
- 6 new public methods for menu navigation
- Each uses `$this->chat->edit()` to update message
- Keyboards built with `Keyboard::make()->buttons()`

**Validation:**
- ✓ Click each main menu button
- ✓ Message updates correctly
- ✓ Navigation works smoothly
- ✓ No callback query timeouts

**Rollback:**
- Delete methods

---

### Task 4.2: Migrate Settings Menu Callbacks

**AI Request:**
```
Implement Settings menu callback methods:
1. Read app/Telegram/Menus/SettingsMenu.php
2. Implement:
   - showSettings() (if not done)
   - accountLinking() / linkNew() / linkCheck() / linkRemove()
   - fatSecretConnection() / fatSecretConnect() / fatSecretLogout()
3. Integrate with services (TelegramAccountService, TelegramFatSecretService)
```

**Context Files:**
- `app/Telegram/Menus/SettingsMenu.php`
- `app/Telegram/Menus/AccountLinkingMenu.php`
- `app/Telegram/Menus/FatSecretConnectionMenu.php`
- `app/Telegram/Services/TelegramAccountService.php`
- `app/Telegram/Services/TelegramFatSecretService.php`

**Expected Output:**
- Multiple callback methods for settings navigation
- Full account linking flow
- FatSecret connection management

**Validation:**
- ✓ Navigate to Settings
- ✓ Test account linking flow
- ✓ Test FatSecret connection options
- ✓ All callbacks work without errors

**Rollback:**
- Delete methods

---

### Task 4.3: Update CallbackData Constants

**AI Request:**
```
Clean up app/Telegram/Constants/CallbackData.php:
1. Remove @method suffixes from all constants
2. Keep constant values simple (e.g., 'showSettings' not 'settings@showSettings')
3. Ensure they match method names in FitnessCoachWebhookHandler
4. Update any usages in handler methods
```

**Context Files:**
- `app/Telegram/Constants/CallbackData.php`
- `app/Telegram/Handlers/FitnessCoachWebhookHandler.php`

**Expected Output:**
- Updated `CallbackData.php` with simplified constants
- All button `->action()` calls match constant values

**Validation:**
- ✓ No syntax errors
- ✓ Test callbacks still work
- ✓ No 'unknown callback' errors

**Rollback:**
- Revert CallbackData.php changes

---

## Phase 5: Conversation Flow Migration

### Task 5.1: Create State Management Service

**AI Request:**
```
Create app/Telegram/Services/TelegraphConversationState.php:
1. Encapsulate conversation state storage/retrieval
2. Use Laravel Cache with 15-minute TTL
3. Methods:
   - getState(TelegraphChat $chat): ?array
   - setState(TelegraphChat $chat, string $step, array $data): void
   - clearState(TelegraphChat $chat): void
   - hasActiveState(TelegraphChat $chat): bool
4. Use cache keys: "telegraph_conv_state_{chat_id}"
```

**Context Files:**
- `app/Telegram/Services/ConversationHelper.php` (reference)
- `app/Telegram/Constants/ConversationSteps.php`

**Expected Output:**
- New service file: `TelegraphConversationState.php`
- Clean interface for state management
- Proper cache key management

**Validation:**
- ✓ File created
- ✓ No syntax errors
- ✓ Can instantiate via DI

**Rollback:**
- Delete file

---

### Task 5.2: Implement handleChatMessage Router

**AI Request:**
```
In FitnessCoachWebhookHandler, implement:
1. Override handleChatMessage(Stringable $text): void
2. Check for active conversation state
3. Route to appropriate processor using match():
   - measurement_date_input
   - measurement_value_input
   - macro_date_input
   - macro_value_input (for each macro type)
   - weight_date_input
   - weight_value_input
4. If no state, show "Use menu" message
```

**Context Files:**
- `app/Telegram/Conversations/MeasurementConversation.php`
- `app/Telegram/Conversations/MacroConversation.php`
- `app/Telegram/Conversations/WeightConversation.php`
- `app/Telegram/Constants/ConversationSteps.php`
- `@TelegraphMigrationPlan.md` (Phase 5, Approach B)

**Expected Output:**
- `handleChatMessage()` method with state routing
- Stub methods for each processor

**Validation:**
- ✓ No syntax errors
- ✓ Method exists and is callable

**Rollback:**
- Delete method

---

### Task 5.3: Migrate Measurement Conversation

**AI Request:**
```
Implement measurement conversation flow:
1. Read app/Telegram/Conversations/MeasurementConversation.php
2. Add callback methods to start flow:
   - measurementChest() / measurementWaist() / etc.
3. Implement processors:
   - processMeasurementDateInput(Stringable $text, array $state)
   - processMeasurementValueInput(Stringable $text, array $state)
4. Use DateValidationService for date parsing
5. Save measurement to database
6. Return to menu on success
```

**Context Files:**
- `app/Telegram/Conversations/MeasurementConversation.php`
- `app/Telegram/Menus/MeasurementsMenu.php`
- `app/Telegram/Services/DateValidationService.php`
- Models: `app/Models/BodyMeasurement.php` or similar

**Expected Output:**
- Callback methods for each measurement type
- Two processor methods
- Full flow: select type → enter date → enter value → save

**Validation:**
- ✓ Start measurement from menu
- ✓ Enter date
- ✓ Enter value
- ✓ Record saved to database
- ✓ Success message displayed

**Rollback:**
- Delete methods

---

### Task 5.4: Migrate Macro (КБЖУ) Conversation

**AI Request:**
```
Implement macro conversation flow:
1. Read app/Telegram/Conversations/MacroConversation.php
2. Add callback methods:
   - macrosCalories() / macrosProteins() / macrosFats() / macrosCarbs()
3. Implement processors:
   - processMacroDateInput(Stringable $text, array $state)
   - processMacroValueInput(Stringable $text, array $state)
4. Save macro entry to database
5. Support both manual entry and FatSecret import context
```

**Context Files:**
- `app/Telegram/Conversations/MacroConversation.php`
- `app/Telegram/Menus/MacrosMenu.php`
- Models: `app/Models/MacronutrientEntry.php` or similar

**Expected Output:**
- Callback methods for each macro type
- Two processor methods
- Full flow working

**Validation:**
- ✓ Start macro entry from menu
- ✓ Enter date
- ✓ Enter value
- ✓ Record saved
- ✓ Success message

**Rollback:**
- Delete methods

---

### Task 5.5: Migrate Weight Conversation

**AI Request:**
```
Implement weight conversation flow:
1. Read app/Telegram/Conversations/WeightConversation.php
2. Add callback method: weightStart() or similar
3. Implement processors:
   - processWeightDateInput(Stringable $text, array $state)
   - processWeightValueInput(Stringable $text, array $state)
4. Validate weight range (reasonable values)
5. Save to database
```

**Context Files:**
- `app/Telegram/Conversations/WeightConversation.php`
- `app/Telegram/Menus/WeightMenu.php`
- Models: `app/Models/WeightEntry.php` or similar

**Expected Output:**
- Callback method to start flow
- Two processor methods
- Full flow working

**Validation:**
- ✓ Start weight entry
- ✓ Enter date and weight
- ✓ Record saved
- ✓ Success message

**Rollback:**
- Delete methods

---

### Task 5.6: Migrate Sync Conversation

**AI Request:**
```
Implement sync conversation flow:
1. Read app/Telegram/Conversations/SyncConversation.php
2. Add callback methods for sync types:
   - syncWeight() / syncFood() / syncFull()
3. Implement processors if needed
4. Integrate with FatSecret services
5. Handle async operations (show "syncing..." message)
```

**Context Files:**
- `app/Telegram/Conversations/SyncConversation.php`
- `app/Telegram/Menus/SyncMenu.php`
- FatSecret services

**Expected Output:**
- Callback methods for sync operations
- Processor methods if date selection needed
- Integration with FatSecret sync

**Validation:**
- ✓ Trigger sync operations
- ✓ See progress/success messages
- ✓ Data synced from FatSecret

**Rollback:**
- Delete methods

---

## Phase 6: Service Integration

### Task 6.1: Update TelegramUserService

**AI Request:**
```
Refactor app/Telegram/Services/TelegramUserService.php:
1. Update getCurrentUser() to accept TelegraphChat instead of user ID
2. Add method: getUserFromChat(TelegraphChat $chat): ?User
3. Remove Nutgram dependencies
4. Maintain backward compatibility if needed
```

**Context Files:**
- `app/Telegram/Services/TelegramUserService.php`
- `app/Contracts/Actions/Users/GetUserByTelegramIdInterface.php`

**Expected Output:**
- Updated service with Telegraph types
- No Nutgram imports

**Validation:**
- ✓ No syntax errors
- ✓ All usages updated
- ✓ Tests pass (if any)

**Rollback:**
- Revert changes

---

### Task 6.2: Update TelegramAccountService

**AI Request:**
```
Refactor app/Telegram/Services/TelegramAccountService.php:
1. Replace Nutgram message sending with TelegraphChat methods
2. Update method signatures to use TelegraphChat
3. Update link code generation to use TelegraphChat IDs
4. Maintain existing business logic
```

**Context Files:**
- `app/Telegram/Services/TelegramAccountService.php`

**Expected Output:**
- Service refactored for Telegraph
- Message sending uses `$chat->message()->send()`

**Validation:**
- ✓ Account linking flow works
- ✓ Link codes generated correctly
- ✓ Messages sent successfully

**Rollback:**
- Revert changes

---

### Task 6.3: Simplify or Remove TelegramMessageService

**AI Request:**
```
Review app/Telegram/Services/TelegramMessageService.php and MessageBuilder:
1. Check if message building is still needed
2. If simple localization, keep it
3. If complex building, simplify to use Telegraph's native API
4. Remove custom builders if obsolete
```

**Context Files:**
- `app/Telegram/Services/TelegramMessageService.php`
- `app/Telegram/Builders/MessageBuilder/MessageBuilder.php`
- Localization: `resources/lang/ru/telegram.php`

**Expected Output:**
- Decision: Keep simplified service or remove entirely
- Update usages in WebhookHandler

**Validation:**
- ✓ Messages display correctly
- ✓ Localization works

**Rollback:**
- Restore if removed

---

## Phase 7: Middleware & Security

### Task 7.1: Implement Handler-Level Guards

**AI Request:**
```
In FitnessCoachWebhookHandler, implement guard methods:
1. requireLinkedAccount(): ?User
   - Check if user is linked
   - Send linking message if not
   - Return User or null
2. requireFatSecretAuth(): bool
   - Check if FatSecret is connected
   - Send connection message if not
   - Return true/false
3. Apply guards in methods that need them (measurements, macros, weight, sync)
```

**Context Files:**
- `app/Telegram/Middleware/AccountLinkMiddleware.php` (reference)
- `app/Telegram/Middleware/FatSecretMiddleware.php` (reference)
- `@TelegraphMigrationPlan.md` (Phase 7)

**Expected Output:**
- Guard methods implemented
- Applied in relevant callback methods
- User-friendly error messages in Russian

**Validation:**
- ✓ Unlinked user sees linking prompt
- ✓ User without FatSecret sees connection prompt
- ✓ Linked users can proceed

**Rollback:**
- Remove guard calls

---

### Task 7.2: Configure Telegraph Security

**AI Request:**
```
Review and update config/telegraph.php security settings:
1. Set secret token for webhook validation
2. Configure IP validation if needed
3. Set rate limiting options
4. Review unknown chat handling
```

**Context Files:**
- `config/telegraph.php`
- `.env`

**Expected Output:**
- Security config optimized
- Secret token set from env variable

**Validation:**
- ✓ Webhook security working
- ✓ No unauthorized requests processed

**Rollback:**
- Revert config

---

## Phase 8: Cleanup & Testing

### Task 8.1: Remove Old Nutgram Code

**AI Request:**
```
Delete or archive old Nutgram files:
1. Move to _old/ directory for safety:
   - app/Telegram/Commands/*
   - app/Telegram/Conversations/*
   - app/Telegram/Menus/*
   - app/Telegram/Builders/*
   - app/Telegram/Handlers/ErrorHandlers.php
   - app/Telegram/Middleware/* (old middleware)
   - app/Telegram/Providers/TelegramServiceProvider.php
2. Remove Nutgram from composer.json
3. Run composer update
```

**Context Files:**
- All files in `app/Telegram/`

**Expected Output:**
- Old files archived
- Nutgram package removed
- Clean codebase

**Validation:**
- ✓ No syntax errors
- ✓ No missing class errors
- ✓ Bot still works

**Rollback:**
- Restore from _old/
- Reinstall Nutgram

---

### Task 8.2: Update Service Provider Registrations

**AI Request:**
```
Review and update service provider registrations:
1. Remove TelegramServiceProvider registration from config/app.php (if exists)
2. Ensure Telegraph services are auto-discovered
3. Register TelegraphConversationState in a provider if needed
4. Clean up any Nutgram-related bindings
```

**Context Files:**
- `config/app.php`
- `app/Providers/AppServiceProvider.php`

**Expected Output:**
- Clean service provider registrations
- No Nutgram references

**Validation:**
- ✓ `php artisan about` shows no errors
- ✓ Services resolve correctly

**Rollback:**
- Revert provider changes

---

### Task 8.3: Register Webhook with Telegram

**AI Request:**
```
Show me how to register the Telegraph webhook:
1. Set webhook URL in .env
2. Run artisan command to register webhook
3. Verify webhook is set correctly
4. Test webhook receiving updates
```

**Context Files:**
- `.env`
- Telegraph docs

**Expected Output:**
- Commands to run:
  ```bash
  docker exec coach_fpm php artisan telegraph:set-webhook {bot_id} --secret={token}
  docker exec coach_fpm php artisan telegraph:webhook-debug-info
  ```

**Validation:**
- ✓ Webhook registered
- ✓ Bot responds to messages
- ✓ No webhook errors in logs

**Rollback:**
```bash
docker exec coach_fpm php artisan telegraph:unset-webhook
```

---

### Task 8.4: Comprehensive Testing

**AI Request:**
```
Create a test checklist and help me test all bot functionality:
1. Commands: /start, /help, /account, /fatsecret, /sync
2. Main menu navigation
3. Settings menu
4. Measurement entry flow (all types)
5. Macro entry flow (all types)
6. Weight entry flow
7. Sync operations
8. Account linking
9. FatSecret OAuth
10. Error handling
11. Edge cases (invalid input, network errors, etc.)
```

**Expected Output:**
- Testing checklist document
- Bug reports for any issues found
- Fixes applied

**Validation:**
- ✓ All features working
- ✓ No critical bugs
- ✓ User experience smooth

**Rollback:**
- Address bugs individually

---

## Emergency Rollback Plan

If critical issues arise during migration:

### Full Rollback to Nutgram

```bash
# 1. Restore old code
git checkout HEAD~1 -- app/Telegram/
git checkout HEAD~1 -- app/Http/Controllers/Api/TelegramWebhookController.php
git checkout HEAD~1 -- routes/api.php
git checkout HEAD~1 -- composer.json

# 2. Reinstall Nutgram
docker exec coach_fpm composer install

# 3. Rollback migrations
docker exec coach_fpm php artisan migrate:rollback --step=2

# 4. Clear cache
docker exec coach_fpm php artisan cache:clear
docker exec coach_fpm php artisan config:clear

# 5. Restart services
docker-compose -p coach restart
```

---

## AI Collaboration Tips

### When Starting a New Task

**Good Prompt:**
```
I'm working on Task 3.1 (Migrate /start Command) from the AI Plan.

Please:
1. Read app/Telegram/Commands/StartCommand.php
2. Read app/Telegram/Menus/MainMenu.php
3. Implement start() method in FitnessCoachWebhookHandler using Telegraph's API
4. Create buildMainMenuKeyboard() helper
5. Follow the Telegraph pattern from the migration plan
```

**Bad Prompt:**
```
Make the start command work with Telegraph
```

---

### Validation Pattern

After each task:
```
I've completed Task X. Let's validate:
1. Check file exists and has no syntax errors
2. Test the functionality via Telegram
3. Verify database/logs if applicable
4. Confirm rollback plan works

Please show me the validation commands.
```

---

### Incremental Testing

Don't wait until the end. Test after each phase:

- **Phase 1**: `php artisan migrate` succeeds
- **Phase 2**: Handler class loads without errors
- **Phase 3**: Each command works individually
- **Phase 4**: Menu navigation smooth
- **Phase 5**: One conversation flow at a time
- **Phase 6**: Services work with new types
- **Phase 7**: Guards protect routes correctly

---

## Success Metrics

- ✅ All Nutgram features migrated
- ✅ Bot responds within 1 second
- ✅ No webhook errors in logs
- ✅ Zero user-reported issues
- ✅ Codebase ~40% smaller
- ✅ Easier to add new features
- ✅ Better error messages
- ✅ Cleaner code structure

---

## Appendix: Quick Reference

### Telegraph Keyboard Example
```php
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Keyboard\Button;

$keyboard = Keyboard::make()->buttons([
    Button::make('Button 1')->action('callback1'),
    Button::make('Button 2')->action('callback2')->param('id', 42),
    Button::make('Visit')->url('https://example.com'),
]);

$this->chat->message('Choose:')->keyboard($keyboard)->send();
```

### Telegraph Message Editing
```php
$this->chat->edit($this->messageId)
    ->message('Updated text')
    ->keyboard($newKeyboard)
    ->send();
```

### State Management Example
```php
// Set state
app(TelegraphConversationState::class)->setState(
    $this->chat,
    'measurement_value_input',
    ['type' => 'chest', 'date' => '2024-01-15']
);

// Get state
$state = app(TelegraphConversationState::class)->getState($this->chat);

// Clear state
app(TelegraphConversationState::class)->clearState($this->chat);
```

### Guard Usage
```php
public function showMeasurements()
{
    if (!$user = $this->requireLinkedAccount()) {
        return; // User notified, method exits
    }

    // Continue with measurements logic
}
```

---

**Ready to start migration? Begin with Task 1.1!**