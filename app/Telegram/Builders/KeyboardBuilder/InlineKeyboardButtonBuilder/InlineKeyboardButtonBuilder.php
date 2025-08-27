<?php

namespace App\Telegram\Builders\KeyboardBuilder\InlineKeyboardButtonBuilder;

use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class InlineKeyboardButtonBuilder implements InlineKeyboardButtonBuilderInterface
{
    public const CALLBACK = 'cancel',
        LINK_NEW = 'link_new',
        CHECK_LINK = 'check_link',
        MEASUREMENTS_START = 'measurements_start',
        SYNC_START = 'sync_start',
        HELP = 'help',
        CHANGE_DATE = 'change_date';

    public function addCancel(): InlineKeyboardButton
    {
        return InlineKeyboardButton::make(text: __('telegram.button.cancel'), callback_data: self::CALLBACK);
    }

    public function addLinkNewAccount(): InlineKeyboardButton
    {
        return InlineKeyboardButton::make(__('telegram.button.link_new'), callback_data: self::LINK_NEW);
    }

    public function addCheckLinkAccount(): InlineKeyboardButton
    {
        return InlineKeyboardButton::make(__('telegram.button.check_link'), callback_data: self::CHECK_LINK);
    }

    public function addMeasurements(): InlineKeyboardButton
    {
        return InlineKeyboardButton::make(__('telegram.button.measurements'), callback_data: self::MEASUREMENTS_START);
    }

    public function addSync(): InlineKeyboardButton
    {
        return InlineKeyboardButton::make(__('telegram.button.sync'), callback_data: self::SYNC_START);
    }

    public function addHelp(): InlineKeyboardButton
    {
        return InlineKeyboardButton::make(__('telegram.button.help'), callback_data: self::HELP);
    }

    public function addChangeDate(): InlineKeyboardButton
    {
        return InlineKeyboardButton::make(__('telegram.button.change_date'), callback_data: self::CHANGE_DATE);
    }
}
