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

### 5. Telegram Bot Architecture
- Telegram bot services located in `app/Services/Telegram/`
- Key services:
    - `TelegramBotService.php` - Main command router and callback handler
    - `TelegramUserService.php` - User management and auto-registration
    - `DateSelectionService.php` - Calendar and date selection UI
    - `MeasurementService.php` - Body measurement tracking
    - `FatSecretSyncService.php` - FatSecret OAuth and synchronization
    - `TelegramFatSecretService.php` - OAuth flow management
- All services use Nutgram framework with webhook mode
- State management via Laravel Cache with appropriate TTL
- User linking system with temporary codes for account connection

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

### 24. Telegram Bot Development
1. Create service in `app/Services/Telegram/`
2. Register in `TelegramServiceProvider`
3. Add command handlers to `TelegramBotService`
4. Implement state management with cache
5. Add callback routing with descriptive prefixes
6. Test webhook functionality with ngrok/public URL

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
