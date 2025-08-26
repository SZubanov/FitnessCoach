<?php

namespace App\Telegram\Builders\KeyboardBuilder\InlineKeyboardButtonBuilder;

use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class InlineKeyboardButtonBuilderBuilder implements InlineKeyboardButtonBuilderInterface
{
    public const CALLBACK = 'cancel',
                LINK_NEW = 'link_new',
                CHECK_LINK = 'check_link';

    public function addCancel(): InlineKeyboardButton
    {
       return InlineKeyboardButton::make(text: __('telegram.button.cancel'), callback_data: self::CALLBACK);
    }

    public function addLinkNewAccount(): InlineKeyboardButton
    {
        return InlineKeyboardButton::make( __('telegram.button.link_new'), callback_data: self::LINK_NEW);
    }

    public function addCheckLinkAccount(): InlineKeyboardButton
    {
        return InlineKeyboardButton::make( __('telegram.button.check_link'), callback_data: self::CHECK_LINK);
    }
}
