<?php

namespace App\Telegram\Builders\KeyboardBuilder\InlineKeyboardButtonBuilder;

use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

interface InlineKeyboardButtonBuilderInterface
{
    public function addCancel(): InlineKeyboardButton;

    public function addLinkNewAccount(): InlineKeyboardButton;

    public function addCheckLinkAccount(): InlineKeyboardButton;

    public function addMeasurements(): InlineKeyboardButton;

    public function addSync(): InlineKeyboardButton;

    public function addHelp(): InlineKeyboardButton;
}
