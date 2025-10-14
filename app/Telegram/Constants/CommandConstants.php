<?php

namespace App\Telegram\Constants;

class CommandConstants
{
    // Core Navigation Commands
    public const START = 'start';
    public const HELP = 'help';

    // Feature Shortcut Commands
    public const SYNC = 'sync';

    // Admin & Management Commands
    public const SETTINGS = 'settings';
    public const ACCOUNT = 'account';
    public const FATSECRET = 'fatsecret';
    public const STATUS = 'status';

    // Utility Commands

    // Command descriptions in Russian
    public const DESCRIPTIONS = [
        self::START => 'Главное меню и приветствие',
        self::HELP => 'Помощь и список команд',
        self::SYNC => 'Синхронизация с FatSecret',
        self::SETTINGS => 'Настройки бота',
        self::ACCOUNT => 'Привязка аккаунта',
        self::FATSECRET => 'Подключение к FatSecret',
        self::STATUS => 'Статус аккаунта и подключений',
    ];

    // Parameter patterns for commands
    public const MEASUREMENT_PARAMS = ['грудь', 'талия', 'шея', 'бицепс', 'таз', 'бедро'];
    public const MACRO_PARAMS = ['калории', 'белки', 'жиры', 'углеводы'];
    public const SYNC_PARAMS = ['полная', 'вес', 'питание'];
}