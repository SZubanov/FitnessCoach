# FitnessCoach Telegram Bot Flow Documentation

pkg: "nutgram/laravel"

## Bot Commands

### Core Commands
- `/start` - Initialize bot and show main menu
- `/help` - Show help information
- `/measurments` - Access measurements menu (requires account linking)
- `/sync` - Access synchronization menu (requires account linking and FatSecret)

## Main Flow Structure

### 1. Bot Initialization (`/start`)
```
/start
├── Settings (Настройки)
├── Account Linking (Привязка аккаунта) 
├── Help (Помощь)
└── Middleware: check_link
```

### 2. Account Management

#### Account Linking Flow
```
Account Linking (Привязка аккаунта)
├── Get Link Code (Получить код для привязки)
│   ├── New Link → Message with link
│   │   ├── Main Menu (Главное меню)
│   │   ├── Back (Назад)
│   │   └── Message about revoke account
│   └── Remove Link → Revoke account message
├── Check Link (Проверить привязку)
│   └── Account status message
└── Unlink Account (Отвязать аккаунт)
```

#### Authorization States
- `authorized account` - User has linked account
- `!authorized account` - User needs to link account

### 3. FatSecret Integration

#### FatSecret Linking
```
FatSecret Connection (Привязка fatsecret)
├── authorized fatsecret
│   ├── Logout from FatSecret (Выйти из fatsecret)
│   │   └── Logout confirmation message
│   └── Back (Назад)
└── !authorized fatsecret
    ├── Connect FatSecret (Привязать fatsecret)
    └── Back (Назад)
```

### 4. Measurements System

#### Main Measurements Menu
```
Measurements (Замеры)
├── Body Parts Selection:
│   ├── Chest (Грудь)
│   ├── Waist (Талия)
│   ├── Neck (Шея)
│   ├── Biceps (Бицепс)
│   ├── Pelvis (Таз)
│   ├── Thigh (Бедро)
│   └── Change Date (Изменить дату)
├── Main Menu (Главное меню)
└── Instructions message
```

#### Measurement Process
```
Select Body Part → Instructions → Value Input → Manual Date Input (DD.MM.YYYY) → Success/Error Message → Main Menu
```

### 5. Synchronization System

#### Sync Menu Options
```
Synchronization (Синхронизация)
├── Full Sync (Полная)
├── Weight (Вес)
├── Food Diary (Дневник питания)
├── Change Date (Изменить дату)
└── Main Menu (Главное меню)
```

#### Sync Process
```
Select Sync Type → Manual Date Input (DD.MM.YYYY) → callback:sync_* → Success/Error Message → Main Menu
```

### 6. Nutrition Tracking (КБЖУ)

#### Macros Menu
```
Macronutrients (КБЖУ)
├── Calories (Ккал)
├── Proteins (Белки)
├── Fats (Жиры)
├── Carbs (Углеводы)
├── Change Date (Изменить дату)
└── Main Menu (Главное меню)
```

#### Macro Entry Process
```
Select Macro → Instructions → Value Input → Manual Date Input (DD.MM.YYYY) → Success Message → Main Menu
```

### 7. Weight Tracking

#### Weight Menu
```
Weight (Вес)
├── Instructions message
├── Value Input → Manual Date Input (DD.MM.YYYY) → Success Message
└── Main Menu (Главное меню)
```

## Middleware Functions

### Security Checks
- `middleware:check_link` - Verifies account is linked
- `middleware:check_fatsecret` - Verifies FatSecret connection

### Callback Handlers
- `callback:settings` - Settings menu handler
- `callback:link` - Account linking handler
- `callback:fatsecret` - FatSecret connection handler
- `callback:fatsecret_logout` - FatSecret logout handler
- `callback:measurments_menu` - Measurements menu handler
- `callback:measurments_set` - Save measurement data
- `callback:sync_menu` - Sync menu handler
- `callback:sync_*` - Various sync operations
- `callback:macros_menu` - Macros menu handler
- `callback:macros_set` - Save macro data
- `callback:weight_menu` - Weight menu handler
- `callback:weight_set` - Save weight data
- `callback:new_link` - Create new account link
- `callback:remove_link` - Remove account link
- `callback:check_link` - Check link status

## Message Types

### User Interface Elements
- **Green (color 3)**: Main Menu buttons
- **Blue (color 1)**: Action/navigation buttons
- **Yellow (color 6)**: System messages and responses
- **Red (color 5)**: Status indicators (authorized/not authorized)

### Common UI Patterns
- Most flows end with "Main Menu" (Главное меню) option
- "Back" (Назад) buttons for navigation
- Manual date input required for all data entries (format: DD.MM.YYYY or DD/MM/YYYY)
- Instructions provided before data entry with date format examples
- Success/error messages after operations
- Date validation with helpful error messages

## Flow Dependencies

1. **Account Required**: Measurements, Sync, Weight, Macros
2. **FatSecret Required**: Sync operations, Food diary
3. **Authorization Chain**: Account Link → FatSecret Link → Full Functionality

## Error Handling

- Unauthorized users redirected to account linking
- Missing FatSecret connection prompts for integration
- Date validation for historical data entry
- Success/error feedback for all operations
