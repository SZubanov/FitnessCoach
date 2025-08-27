<?php

namespace App\Telegram\Services;

use App\Models\User;
use App\Telegram\Builders\KeyboardBuilder\InlineKeyboardButtonBuilder\InlineKeyboardButtonBuilderInterface;
use App\Telegram\Builders\MessageBuilder\MessageBuilderInterface;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
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

    public function sendMessageWelcome(Nutgram $bot): ?Message
    {
        $text = $this->messageBuilder->createWelcomeMessage();

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                $this->inlineKeyboardButtonBuilder->addMeasurements(),
                $this->inlineKeyboardButtonBuilder->addSync(),
            )
            ->addRow($this->inlineKeyboardButtonBuilder->addHelp());

        return $bot->sendMessage($text, parse_mode: ParseMode::MARKDOWN, reply_markup: $keyboard);
    }

    public function sendMessageLinkNewAccount(Nutgram $bot, string $linkCode): ?Message
    {
        $text = $this->messageBuilder->createNewLinkAccountMessage($linkCode);

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow($this->inlineKeyboardButtonBuilder->addLinkNewAccount())
            ->addRow($this->inlineKeyboardButtonBuilder->addCancel());

        return $bot->sendMessage($text, parse_mode: ParseMode::MARKDOWN, reply_markup: $keyboard);
    }

    public function sendMessageLinkExistingAccount(Nutgram $bot, User $user): ?Message
    {
        $text = $this->messageBuilder->createExistingLinkAccountMessage($user->name, $user->email);

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                $this->inlineKeyboardButtonBuilder->addCheckLinkAccount(),
                $this->inlineKeyboardButtonBuilder->addLinkNewAccount(),
            )
            ->addRow($this->inlineKeyboardButtonBuilder->addCancel());

        return $bot->sendMessage($text, parse_mode: ParseMode::MARKDOWN, reply_markup: $keyboard);
    }

    public function sendMessageHelp(Nutgram $bot): ?Message
    {
        $text = $this->messageBuilder->createHelpMessage();

        return $bot->sendMessage($text, parse_mode: ParseMode::MARKDOWN);
    }

    public function sendMessageApiError(Nutgram $bot): ?Message
    {
        $text = $this->messageBuilder->createApiErrorHandlerMessage();

        return $bot->sendMessage($text, parse_mode: ParseMode::MARKDOWN);
    }

    public function sendMessageExceptionError(Nutgram $bot): ?Message
    {
        $text = $this->messageBuilder->createExceptionHandlerMessage();

        return $bot->sendMessage($text, parse_mode: ParseMode::MARKDOWN);
    }
}
