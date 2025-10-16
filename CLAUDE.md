# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# Claude Code Rules for FitnessCoach Project

## Project Context
FitnessCoach is a personal fitness tracking web application built with Laravel, featuring a clean architecture with action-interface patterns, FatSecret API integration, Telegram bot functionality, and comprehensive health metrics tracking.

## Development Environment

### Docker Setup
The project runs in Docker containers. All shell commands must be executed inside the `coach_fpm` container:

```bash
# Start containers
docker-compose -p coach up -d

# Execute commands inside FMP container
docker exec coach_fpm php artisan [command]
docker exec coach_fpm composer install --dev

# Initial setup
docker exec coach_fmp composer install --dev
docker exec coach_fmp php artisan migrate
docker exec coach_fpm php artisan ide-helper:generate
```

### Key Commands

```bash
# Laravel commands (inside coach_fpm container)
php artisan migrate                    # Run migrations
php artisan telescope:install         # Install Telescope
php artisan cache:clear               # Clear application cache

# Telegram bot commands
php artisan telegram:webhook --url=https://domain.com/api/telegram/webhook
php artisan telegram:info             # Check webhook status

# Development tools
php artisan ide-helper:generate       # Generate IDE helpers
composer install --dev               # Install dev dependencies
```

### Frontend Build Process

```bash
# Frontend development (on host machine)
npm install                           # Install dependencies
npm run dev                          # Development build
npm run watch                        # Watch for changes
npm run prod                         # Production build
```

## Architectural Principles

### 1. Single Responsibility Controllers
- **Controllers MUST only contain `__invoke()` method**
- Controllers act as thin HTTP entry points
- All business logic MUST be delegated to Actions
- Controllers should only handle:
    - Request validation
    - Calling appropriate Actions
    - Returning responses

```php
// ✅ Correct controller pattern
class UserUpdateController extends Controller
{
    public function __invoke(UpdateUserRequest $request, UpdateUser $action): RedirectResponse
    {
        $action->handle($request->validated());
        return redirect()->route('users.index');
    }
}
```

### 2. Action-Interface Pattern
- **Business logic MUST be implemented in Action classes**
- All Actions MUST implement corresponding interfaces from `app/Contracts/Actions/`
- Actions MUST be invokable classes with `handle()` method
- Actions should be bound to interfaces in service providers

```php
// ✅ Interface first
interface UpdateUser
{
    public function handle(array $data): User;
}

// ✅ Implementation
class UpdateUserAction implements UpdateUser
{
    public function __invoke(array $data): User
    {
        return $this->handle($data);
    }
    
    public function handle(array $data): User
    {
        // Business logic here
    }
}
```

### 3. Directory Structure Compliance
- Follow the established directory structure:
    - `app/Actions/` - Business logic implementations
    - `app/Contracts/Actions/` - Action interfaces
    - `app/Dto/` - Data Transfer Objects
    - `app/FatSecret/` - FatSecret API integration component
    - `app/Http/Controllers/` - Thin controllers
    - `app/Models/` - Eloquent models
    - `app/Repositories/` - Data access layer
    - `app/Services/` - Application services

### 4. FatSecret Integration Component
- Keep FatSecret integration as a separate, cohesive component
- Use the established component structure:
    - `FatSecret.php` - Configuration and constants
    - `FatSecretAuth.php` - OAuth1 authentication
    - `FatSecretClient.php` - HTTP client
    - `FatSecretService.php` - Main business logic
    - `FatSecretRepository.php` - Data persistence
    - `FatSecretServiceLoggerDecorator.php` - Logging decorator

### 5. Telegram Bot Architecture ⚡ REFACTORING IN PROGRESS (Phase 1/6 Complete - Oct 2025)

**CURRENT STATE: Phase 1 Foundation Complete ✅**

The Telegram bot is undergoing a systematic refactoring from a monolithic handler (1,406 lines) to a SOLID-compliant, pattern-based architecture. **Phase 1 infrastructure is complete** with 20 foundational files implementing 6 design patterns.

#### Phase 1: Foundation & Infrastructure (✅ COMPLETED)

**Design Patterns Implemented**:
- **Command Pattern**: Command and callback handlers with registry
- **Strategy Pattern**: Polymorphic conversation handlers
- **Chain of Responsibility**: Composable middleware pipeline
- **Factory Pattern**: Centralized keyboard construction
- **Builder Pattern**: Fluent message and keyboard builders
- **DTO Pattern**: Immutable conversation context and results
- **Registry Pattern**: O(1) command/callback lookup

**Core Infrastructure** (20 files, ~1,796 lines):

1. **Interfaces/Contracts** (`app/Telegram/*/Contracts/`):
   - `TelegramCommandHandler` - Command execution contract
   - `CallbackHandler` - Callback query contract
   - `ConversationHandler` - Strategy for conversation flows
   - `ConversationContext` - Immutable conversation state DTO
   - `ConversationResult` - Conversation outcome DTO
   - `ConversationStatus` - Type-safe state enum
   - `TelegramMiddleware` - Middleware chain contract

2. **Services** (`app/Telegram/Services/`):
   - `TelegramCommandRegistry` - Command pattern registry with O(1) lookup
   - `MessageResponseBuilder` - Fluent API for formatted messages (30+ methods)

3. **Keyboards** (`app/Telegram/Keyboards/`):
   - `KeyboardFactory` - Factory for 13 predefined keyboards (eliminates 155 lines of duplication)
   - `KeyboardBuilder` - Fluent builder for dynamic keyboards

4. **Middleware** (`app/Telegram/Middleware/`):
   - `MiddlewarePipeline` - Laravel-style pipeline orchestrator
   - `RequireAccountLinkMiddleware` - Account linking guard
   - `RequireFatSecretAuthMiddleware` - FatSecret OAuth guard

5. **Exceptions** (`app/Telegram/Exceptions/`):
   - `UnknownCommandException` - Unregistered command errors
   - `NoActiveConversationException` - Missing conversation errors
   - `InvalidConversationStepException` - Invalid step errors
   - `NoHandlerForConversationTypeException` - Missing handler errors

**Benefits Realized**:
- ✅ Full type safety with comprehensive type hints
- ✅ Interface-based programming enabling dependency injection
- ✅ Eliminated keyboard duplication (155 lines)
- ✅ Composable middleware for reusable authorization
- ✅ Consistent message formatting across all handlers
- ✅ O(1) command lookup vs. linear if-else chains
- ✅ Testable components with clear interfaces

**Documentation**:
- Architecture proposal: `TELEGRAM_BOT_ARCHITECTURE_PROPOSAL.md` (1,443 lines)
- Implementation plan: `TELEGRAM_REFACTORING_IMPLEMENTATION_PLAN.md` (2,283 lines)
- Phase 1 changelog: `TELEGRAM_REFACTORING_PHASE1_CHANGELOG.md` (complete metrics)
- Phase 1 technical docs: `TELEGRAM_REFACTORING_PHASE1_TECHNICAL_DOCS.md` (detailed guides)

#### Current Production State (Telegraph Framework)

**Main Handler**: `app/Telegram/Handlers/FitnessCoachWebhookHandler.php` (1,406 lines - being decomposed)
- Method-based routing: `Button::make('Text')->action('methodName')` → `public function methodName()`
- Cache-based conversations: 15-minute TTL with step tracking
- Configuration: `config/telegraph.php` with custom webhook handler

**Key Services**:
- `TelegramUserService.php` - User management and auto-registration
- `TelegramAccountService.php` - Account linking with temporary codes
- `TelegramFatSecretService.php` - OAuth flow management
- `DateValidationService.php` - Date input validation and parsing
- `ConversationStateService.php` - Custom conversation state management (294 lines)

**Conversation Patterns** (being refactored):
- Guard methods: `requireLinkedAccount()`, `requireFatSecretAuth()` → Replaced by middleware
- Central router: `handleChatMessage()` with match expression → Being replaced by registry
- Step-based flows: date input → value input → save → cleanup
- 4 conversation types: measurement, weight, macro (КБЖУ), sync

**Legacy Code (Deprecated - Nutgram migration artifacts)**:
- `app/Telegram/Commands/` - Old Nutgram commands
- `app/Telegram/Conversations/` - Old Nutgram conversations
- `app/Telegram/Menus/` - Old Nutgram menus
- `app/Telegram/Constants/CallbackData.php` - String constants

#### Upcoming Phases

**Phase 2: Commands Migration** (Next - ~4-6 hours)
- Extract 5 command handlers: start, help, account, fatsecret, sync
- Register in TelegramCommandRegistry
- Reduce handler by ~200-250 lines

**Phase 3: Callback System** (~6-8 hours)
- Extract 12+ callback handlers
- Implement callback registry
- Reduce handler by ~300-400 lines

**Phase 4: Conversation Manager** (~8-10 hours)
- Extract 4 conversation handlers
- Implement conversation manager
- Reduce handler by ~400-500 lines

**Phase 5: Integration & Testing** (~4-6 hours)
- Wire all components together
- Comprehensive testing
- Performance validation

**Phase 6: Final Migration** (~2-3 hours)
- Remove old handler
- Update configuration
- Final cleanup

**Goal**: Reduce handler from 1,406 lines to ~150 lines while improving testability and maintainability

#### Development Guidelines for Telegram Bot

**When Creating New Commands**:
```php
// 1. Implement TelegramCommandHandler interface
class MyCommandHandler implements TelegramCommandHandler
{
    public function handle(TelegraphChat $chat): void
    {
        // Use KeyboardFactory and MessageResponseBuilder
        $message = MessageResponseBuilder::create()
            ->title('My Feature')
            ->text('Description here')
            ->build();

        $chat->message($message)
            ->keyboard($this->keyboardFactory->mainMenu())
            ->send();
    }

    public function getCommandName(): string
    {
        return 'mycommand';
    }
}

// 2. Register in service provider
$registry->register(new MyCommandHandler($keyboardFactory));
```

**When Creating Callbacks**:
```php
// Implement CallbackHandler interface
class MyCallbackHandler implements CallbackHandler
{
    public function handle(TelegraphChat $chat, ?int $messageId = null): void
    {
        // Implementation here
    }

    public function getCallbackName(): string
    {
        return 'my_callback';
    }
}
```

**When Using Middleware**:
```php
// Build pipeline for protected actions
$pipeline = new MiddlewarePipeline([
    new RequireAccountLinkMiddleware($userService, $keyboardFactory),
    new RequireFatSecretAuthMiddleware($keyboardFactory),
]);

$result = $pipeline->through($chat, function ($user) {
    // Only executed if all guards pass
    return $this->performAction($user);
});
```

**When Building Messages**:
```php
// Use MessageResponseBuilder for consistency
$message = MessageResponseBuilder::create()
    ->greeting('FitnessCoach')
    ->addFeatures(['weight', 'measurements', 'macros'])
    ->success('Operation completed')
    ->addInstructions('Use /help for more information')
    ->build();
```

**When Creating Keyboards**:
```php
// Use KeyboardFactory for predefined keyboards
$keyboard = $this->keyboardFactory->mainMenu();

// Use KeyboardBuilder for dynamic keyboards
$keyboard = KeyboardBuilder::create()
    ->addButton('Custom', 'customAction')
    ->addMainMenuButton()
    ->inColumns(2)
    ->build();
```

## Code Standards

### 6. Naming Conventions
- Controllers: `{Action}{Entity}Controller` (e.g., `UpdateUserController`)
- Actions: `{Action}{Entity}Action` (e.g., `UpdateUserAction`)
- Interfaces: `{Action}{Entity}` (e.g., `UpdateUser`)
- Models: Singular, PascalCase (e.g., `User`, `WeightEntry`)
- Routes: RESTful naming with kebab-case

### 7. Data Transfer Objects (DTOs)
- Use DTOs for complex data structures
- DTOs should be immutable
- Implement proper validation within DTOs

### 8. Repository Pattern
- Use repositories for data access abstraction
- Repositories should handle Eloquent queries
- Keep business logic out of repositories

### 9. Russian Language Support
- Comments and documentation in English
- User-facing text in Russian
- Use Laravel localization features
- Key names should be descriptive: `nutrition.calories`, `weight.current`

### 10. Telegram Bot Patterns
- Use callback data prefixes for routing: `measurements_start`, `sync_connect`
- State management with cache keys: `telegram_user_state_{userId}`
- Answer callback queries first before processing to avoid timeouts
- Use try-catch blocks around message editing operations
- Cache OAuth states using token identifiers as keys
- Follow repository cache key patterns: `fatsecret:temp_cred:user:{identifier}`

## Feature-Specific Rules

### 11. Nutrition Tracking (КБЖУ)
- Always validate nutritional data (calories, proteins, fats, carbohydrates)
- Support both manual entry and FatSecret API import
- Use consistent units across the application
- Implement proper date handling for daily tracking

### 12. Weight Tracking
- Support multiple weight units with proper conversion
- Implement automatic sync with FatSecret when available
- Maintain historical data integrity
- Validate weight entries for reasonable ranges

### 13. Body Measurements (Замеры)
- Allow custom measurement types
- Implement proper unit handling
- Support historical tracking and comparisons

### 14. FatSecret API Integration
- Always handle OAuth1 authentication properly
- Implement proper error handling for API failures
- Use the decorator pattern for logging
- Cache tokens appropriately
- Handle rate limiting gracefully

### 15. Telegram Bot Features
- Support date selection with calendar interface and quick options
- Implement measurement tracking with historical suggestions
- Provide FatSecret OAuth flow through bot with web callback
- User account linking system with temporary codes (15-minute TTL)
- Cache-based state management for complex dialogues
- Comprehensive error handling with user-friendly Russian messages

## Technical Requirements

### 16. Laravel Standards
- Follow Laravel 9+ conventions
- Use service providers for dependency injection
- Implement proper middleware usage
- Use Laravel's built-in features (validation, pagination, etc.)

### 17. Security
- Use Spatie Laravel Permission for role-based access
- Validate all user inputs
- Implement proper CSRF protection
- Secure FatSecret OAuth tokens

### 18. Admin Interface
- Use AdminLTE components consistently
- Implement proper user management for admins
- Maintain responsive design patterns
- Follow established UI patterns

### 19. Error Handling
- Use custom exceptions for business logic errors
- Implement proper logging with the decorator pattern
- Provide meaningful error messages in Russian
- Handle FatSecret API errors gracefully

### 20. Testing Considerations
- Write tests for Actions, not Controllers
- Mock external API calls (FatSecret)
- Test business logic thoroughly
- Use Laravel's testing utilities

## Development Workflow

### 21. New Feature Implementation
1. Define interface in `app/Contracts/Actions/`
2. Implement Action class in `app/Actions/`
3. Create thin Controller with `__invoke()` method
4. Bind interface to implementation in service provider
5. Add routes and views
6. Write tests

### 22. Database Changes
- Use Laravel migrations
- Maintain foreign key relationships
- Consider data privacy for health information
- Implement soft deletes where appropriate

### 23. API Integration
- Follow the FatSecret component pattern for any new API integrations
- Implement proper authentication flows
- Use the decorator pattern for cross-cutting concerns
- Handle API failures gracefully with fallbacks

## Quality Assurance

### 25. Code Quality
- Follow PSR standards
- Use type hints consistently
- Implement proper documentation
- Use Laravel collections and helper functions

### 26. Performance
- Optimize database queries
- Use Laravel's caching mechanisms
- Consider pagination for large datasets
- Optimize FatSecret API calls

### 27. Maintenance
- Keep dependencies updated
- Monitor Laravel Telescope for performance issues
- Regularly review and refactor code
- Maintain comprehensive logging

## Important Notes

### Cache Key Patterns
- User states: `telegram_user_state_{userId}`
- Selected dates: `telegram_user_selected_date_{userId}`
- OAuth temp credentials: `fatsecret:temp_cred:user:{identifier}`
- Link codes: `telegram_link_code_{code}`
- Last sync timestamps: `fatsecret_last_sync_{userId}`

### Environment Variables Required
```env
# Database
DB_HOST=postgres
DB_DATABASE=app
DB_USERNAME=root
DB_PASSWORD=root

# Telegram Bot
TELEGRAM_TOKEN=your_bot_token
TELEGRAM_WEBHOOK_URL=https://domain.com/api/telegram/webhook

# FatSecret API
FATSECRET_CONSUMER_KEY=your_key
FATSECRET_CONSUMER_SECRET=your_secret
```

### Container Architecture
- `coach_fmp` - Main PHP-FPM application container
- `coach_nginx` - Web server
- `coach_db` - PostgreSQL database
- `coach_redis` - Redis cache
- `coach_cron` - Cron jobs

These rules ensure consistency with your established clean architecture while maintaining the high-quality standards of the FitnessCoach project.
