# Claude Code Rules for FitnessCoach Project

## Project Context
FitnessCoach is a personal fitness tracking web application built with Laravel, featuring a clean architecture with action-interface patterns, FatSecret API integration, and comprehensive health metrics tracking.

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

## Code Standards

### 5. Naming Conventions
- Controllers: `{Action}{Entity}Controller` (e.g., `UpdateUserController`)
- Actions: `{Action}{Entity}Action` (e.g., `UpdateUserAction`)
- Interfaces: `{Action}{Entity}` (e.g., `UpdateUser`)
- Models: Singular, PascalCase (e.g., `User`, `WeightEntry`)
- Routes: RESTful naming with kebab-case

### 6. Data Transfer Objects (DTOs)
- Use DTOs for complex data structures
- DTOs should be immutable
- Implement proper validation within DTOs

### 7. Repository Pattern
- Use repositories for data access abstraction
- Repositories should handle Eloquent queries
- Keep business logic out of repositories

### 8. Russian Language Support
- Comments and documentation in English
- User-facing text in Russian
- Use Laravel localization features
- Key names should be descriptive: `nutrition.calories`, `weight.current`

## Feature-Specific Rules

### 9. Nutrition Tracking (КБЖУ)
- Always validate nutritional data (calories, proteins, fats, carbohydrates)
- Support both manual entry and FatSecret API import
- Use consistent units across the application
- Implement proper date handling for daily tracking

### 10. Weight Tracking
- Support multiple weight units with proper conversion
- Implement automatic sync with FatSecret when available
- Maintain historical data integrity
- Validate weight entries for reasonable ranges

### 11. Body Measurements (Замеры)
- Allow custom measurement types
- Implement proper unit handling
- Support historical tracking and comparisons

### 12. FatSecret API Integration
- Always handle OAuth1 authentication properly
- Implement proper error handling for API failures
- Use the decorator pattern for logging
- Cache tokens appropriately
- Handle rate limiting gracefully

## Technical Requirements

### 13. Laravel Standards
- Follow Laravel 9+ conventions
- Use service providers for dependency injection
- Implement proper middleware usage
- Use Laravel's built-in features (validation, pagination, etc.)

### 14. Security
- Use Spatie Laravel Permission for role-based access
- Validate all user inputs
- Implement proper CSRF protection
- Secure FatSecret OAuth tokens

### 15. Admin Interface
- Use AdminLTE components consistently
- Implement proper user management for admins
- Maintain responsive design patterns
- Follow established UI patterns

### 16. Error Handling
- Use custom exceptions for business logic errors
- Implement proper logging with the decorator pattern
- Provide meaningful error messages in Russian
- Handle FatSecret API errors gracefully

### 17. Testing Considerations
- Write tests for Actions, not Controllers
- Mock external API calls (FatSecret)
- Test business logic thoroughly
- Use Laravel's testing utilities

## Development Workflow

### 18. New Feature Implementation
1. Define interface in `app/Contracts/Actions/`
2. Implement Action class in `app/Actions/`
3. Create thin Controller with `__invoke()` method
4. Bind interface to implementation in service provider
5. Add routes and views
6. Write tests

### 19. Database Changes
- Use Laravel migrations
- Maintain foreign key relationships
- Consider data privacy for health information
- Implement soft deletes where appropriate

### 20. API Integration
- Follow the FatSecret component pattern for any new API integrations
- Implement proper authentication flows
- Use the decorator pattern for cross-cutting concerns
- Handle API failures gracefully with fallbacks

## Quality Assurance

### 21. Code Quality
- Follow PSR standards
- Use type hints consistently
- Implement proper documentation
- Use Laravel collections and helper functions

### 22. Performance
- Optimize database queries
- Use Laravel's caching mechanisms
- Consider pagination for large datasets
- Optimize FatSecret API calls

### 23. Maintenance
- Keep dependencies updated
- Monitor Laravel Telescope for performance issues
- Regularly review and refactor code
- Maintain comprehensive logging

These rules ensure consistency with your established clean architecture while maintaining the high-quality standards of the FitnessCoach project.
