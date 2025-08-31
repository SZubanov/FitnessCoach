## **FitnessCoach Project Overview**

**FitnessCoach** is a **personal fitness tracking web application** built with Laravel that helps users monitor their nutrition, weight, body measurements, and physical activity. The app integrates with the FatSecret API to automatically import nutritional and weight data.

### **Core Functionality**

The application provides a **personal fitness diary** where users can track:

1. **Nutrition (КБЖУ - Calories, Proteins, Fats, Carbohydrates)**
    - Manual entry of food data
    - Automatic import from FatSecret API
    - Daily nutritional tracking

2. **Weight Tracking**
    - Manual weight logging with different units
    - Automatic sync from FatSecret
    - Historical weight data storage

3. **Body Measurements (Замеры)**
    - Track various body measurements
    - Custom measurement tracking

4. **Physical Activity**
    - Step counting and activity logging
    - Daily activity records

### **Custom Laravel Architecture**

You've implemented a **clean architecture pattern** with several key customizations:

#### **Controller Pattern**
- **Single-purpose controllers** with only `__invoke()` methods
- Controllers act as **thin entry points** that delegate to business logic
- Example: `UserUpdateController` only handles the HTTP request and calls the business action

#### **Action-Interface Pattern**
- **Business logic separated into Actions** (e.g., `UpdateUser`, `StoreUser`)
- **Interface-driven design** with contracts in `app/Contracts/Actions/`
- **Dependency injection** through interfaces bound to concrete implementations
- Actions are **invokable classes** that contain the actual business logic

#### **Directory Structure**
```
app/
├── Actions/           # Business logic implementations
├── Contracts/         # Interfaces for actions
├── Dto/              # Data Transfer Objects
├── FatSecret/        # Separate component for API integration
├── Http/Controllers/ # Thin controllers with __invoke() only
├── Models/           # Eloquent models
├── Repositories/     # Data access layer
└── Services/         # Application services
```

### **FatSecret Integration Component**

You've built a **comprehensive FatSecret API integration** as a separate component:

#### **Key Components:**
- **`FatSecret.php`** - Main configuration and constants
- **`FatSecretAuth.php`** - OAuth1 authentication handling
- **`FatSecretClient.php`** - HTTP client for API calls
- **`FatSecretService.php`** - Main service with business logic
- **`FatSecretRepository.php`** - Data persistence for OAuth tokens
- **`FatSecretServiceLoggerDecorator.php`** - Logging decorator

#### **Features:**
- **OAuth1 authentication flow** with temporary and access tokens
- **Automatic data import** for weight and food entries
- **Date-based data retrieval** from FatSecret
- **Error handling** with custom exceptions
- **Token management** for users

### **Technical Features**

- **Laravel 10** with PHP 8.1+
- **AdminLTE** for the admin interface
- **Spatie Laravel Permission** for role-based access
- **Laravel Telescope** for debugging
- **Docker setup** for development
- **Role-based access control** for admin users
- **Multi-language support** (Russian interface)
- **Responsive web design**

### **User Experience**

- **Dashboard-style interface** with cards for different tracking categories
- **Date-picker integration** for historical data entry
- **One-click FatSecret sync** for automated data import
- **Settings page** for user profile and FatSecret connection management
- **Admin panel** for user management (for admin roles)

The application essentially serves as a **personal fitness assistant** that consolidates various health metrics in one place, with the convenience of automatic data import from FatSecret's extensive food and weight database.
