<?php

namespace App\Telegram\Services;

use App\Models\User;
use App\Telegram\Builders\KeyboardBuilder\InlineKeyboardButtonBuilder\InlineKeyboardButtonBuilderInterface;
use App\Telegram\Builders\MessageBuilder\MessageBuilderInterface;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Message\Message;

class TelegramMessageService
{
    public function __construct(
        readonly public InlineKeyboardButtonBuilderInterface $inlineKeyboardButtonBuilder,
        readonly public MessageBuilderInterface              $messageBuilder,
        readonly public TelegramAccountService               $telegramAccountService,
    ) {
    }

    public function sendMessageLinkNewAccount(Nutgram $bot): ?Message
    {
        $linkCode = $this->telegramAccountService->generateLinkAccountCode($bot->userId());

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                $this->inlineKeyboardButtonBuilder->addCheckLinkAccount(),
                $this->inlineKeyboardButtonBuilder->addLinkNewAccount(),
            )
            ->addRow(
                $this->inlineKeyboardButtonBuilder->addCancel()
            );

        $text = $this->messageBuilder->createMessageNewLinkAccount($linkCode);

        return $bot->sendMessage($text, parse_mode: 'Markdown', reply_markup: $keyboard);
    }

    public function sendMessageLinkExistingAccount(Nutgram $bot, User $user): ?Message
    {
        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                $this->inlineKeyboardButtonBuilder->addLinkNewAccount(),
                $this->inlineKeyboardButtonBuilder->addCancel()
            );

        $text = $this->messageBuilder->createMessageExistingLinkAccount($user->name, $user->email);
        return $bot->sendMessage($text, parse_mode: 'Markdown', reply_markup: $keyboard);
    }
}
