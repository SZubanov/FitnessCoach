<?php

namespace App\Telegram\Keyboards;

use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;

/**
 * Keyboard Factory
 *
 * Centralized factory for creating all Telegram keyboards used in the bot.
 * Following the Factory pattern to maintain consistency and reduce duplication.
 */
class KeyboardFactory
{
    /**
     * Main menu keyboard
     *
     * Primary navigation keyboard with all main features
     *
     * @return Keyboard
     */
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

    /**
     * Help menu keyboard
     *
     * Simple keyboard with back to main menu button
     *
     * @return Keyboard
     */
    public function help(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Account linking keyboard
     *
     * Shown when user needs to link their account
     *
     * @return Keyboard
     */
    public function accountLinking(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('🔗 Привязать аккаунт')->action('accountLinking'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Account menu keyboard
     *
     * Full account management options
     *
     * @return Keyboard
     */
    public function accountMenu(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('📝 Получить код для привязки')->action('generateLinkCode'),
            Button::make('✅ Проверить привязку')->action('checkLinkStatus'),
            Button::make('❌ Отвязать аккаунт')->action('removeLinkAccount'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Account back navigation keyboard
     *
     * Back button to return to account menu
     *
     * @return Keyboard
     */
    public function accountBack(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('↩️ Назад')->action('accountLinking'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * FatSecret connection menu keyboard
     *
     * FatSecret OAuth and connection management
     *
     * @return Keyboard
     */
    public function fatSecretMenu(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('✅ Проверить привязку')->action('checkFatSecretConnection'),
            Button::make('🔗 Подключить FatSecret')->action('connectFatSecret'),
            Button::make('🚪 Выйти из FatSecret')->action('logoutFromFatSecret'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * FatSecret back navigation keyboard
     *
     * Back button to return to FatSecret menu
     *
     * @return Keyboard
     */
    public function fatSecretBack(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('↩️ Назад')->action('fatSecretConnect'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Sync menu keyboard
     *
     * Synchronization type selection
     *
     * @return Keyboard
     */
    public function syncMenu(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('🔄 Полная синхронизация')->action('syncFull'),
            Button::make('⚖️ Синхронизация веса')->action('syncWeight'),
            Button::make('🍎 Дневник питания')->action('syncFood'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Sync back navigation keyboard
     *
     * Back button to return to sync menu
     *
     * @return Keyboard
     */
    public function syncBack(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('↩️ Назад')->action('showSync'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Settings keyboard
     *
     * Settings menu with account and FatSecret options
     *
     * @return Keyboard
     */
    public function settings(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('🔗 Привязка аккаунта')->action('accountLinking'),
            Button::make('🔐 FatSecret')->action('fatSecretConnect'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Measurements menu keyboard
     *
     * Body measurements tracking options
     *
     * @return Keyboard
     */
    public function measurements(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('📐 Новый замер')->action('startNewMeasurement'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Macros (КБЖУ) menu keyboard
     *
     * Macro selection: calories, proteins, fats, carbs
     *
     * @return Keyboard
     */
    public function macros(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('🔥 Калории')->action('selectMacroCalories'),
            Button::make('🥩 Белки')->action('selectMacroProteins'),
            Button::make('🧈 Жиры')->action('selectMacroFats'),
            Button::make('🍞 Углеводы')->action('selectMacroCarbs'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Weight tracking menu keyboard
     *
     * Weight entry and tracking options
     *
     * @return Keyboard
     */
    public function weight(): Keyboard
    {
        return Keyboard::make()->buttons([
            Button::make('⚖️ Добавить вес')->action('startNewWeight'),
            Button::make('🏠 Главное меню')->action('mainMenu'),
        ]);
    }

    /**
     * Fluent keyboard builder for dynamic keyboards
     *
     * Use this when you need to build custom keyboards programmatically
     *
     * @return KeyboardBuilder
     */
    public function builder(): KeyboardBuilder
    {
        return new KeyboardBuilder();
    }
}