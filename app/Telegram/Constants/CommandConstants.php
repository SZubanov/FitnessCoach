<?php

namespace App\Telegram\Constants;

class CommandConstants
{
    // Core Navigation Commands
    public const START = 'start';
    public const HELP = 'help';
    public const MENU = 'menu';

    // Feature Shortcut Commands
    public const MEASUREMENTS = 'measurements';
    public const MACROS = 'macros';
    public const WEIGHT = 'weight';
    public const SYNC = 'sync';

    // Admin & Management Commands
    public const SETTINGS = 'settings';
    public const ACCOUNT = 'account';
    public const FATSECRET = 'fatsecret';
    public const STATUS = 'status';

    // Utility Commands
    public const CANCEL = 'cancel';
    public const INFO = 'info';
    public const SUPPORT = 'support';

    // Command descriptions in Russian
    public const DESCRIPTIONS = [
        self::START => 'Главное меню и приветствие',
        self::HELP => 'Помощь и список команд',
        self::MENU => 'Вернуться в главное меню',
        self::MEASUREMENTS => 'Записать замеры тела',
        self::MACROS => 'Записать КБЖУ',
        self::WEIGHT => 'Записать вес',
        self::SYNC => 'Синхронизация с FatSecret',
        self::SETTINGS => 'Настройки бота',
        self::ACCOUNT => 'Привязка аккаунта',
        self::FATSECRET => 'Подключение к FatSecret',
        self::STATUS => 'Статус аккаунта и подключений',
        self::CANCEL => 'Отменить текущую операцию',
        self::INFO => 'Информация о боте',
        self::SUPPORT => 'Поддержка и обратная связь',
    ];

    // Parameter patterns for commands
    public const MEASUREMENT_PARAMS = ['грудь', 'талия', 'шея', 'бицепс', 'таз', 'бедро'];
    public const MACRO_PARAMS = ['калории', 'белки', 'жиры', 'углеводы'];
    public const SYNC_PARAMS = ['полная', 'вес', 'питание'];
}