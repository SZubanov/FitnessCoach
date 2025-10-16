<?php

namespace App\Telegram\Services;

/**
 * Message Response Builder
 *
 * Fluent API for building formatted Telegram messages.
 * Handles HTML formatting and provides consistent message structure.
 */
class MessageResponseBuilder
{
    /**
     * @var array<string>
     */
    private array $parts = [];

    /**
     * Add an icon/emoji
     *
     * @param string $icon Emoji or icon
     * @return self
     */
    public function icon(string $icon): self
    {
        $this->parts[] = $icon;
        return $this;
    }

    /**
     * Add a bold title
     *
     * @param string $title Title text
     * @return self
     */
    public function title(string $title): self
    {
        $this->parts[] = "**{$title}**";
        return $this;
    }

    /**
     * Add plain text
     *
     * @param string $text Text content
     * @return self
     */
    public function text(string $text): self
    {
        $this->parts[] = $text;
        return $this;
    }

    /**
     * Add an instruction line
     *
     * @param string $instruction Instruction text
     * @return self
     */
    public function instruction(string $instruction): self
    {
        $this->parts[] = $instruction;
        return $this;
    }

    /**
     * Add an example
     *
     * @param string $example Example text
     * @return self
     */
    public function example(string $example): self
    {
        $this->parts[] = $example;
        return $this;
    }

    /**
     * Add a field (label: value)
     *
     * @param string $label Field label
     * @param string $value Field value
     * @return self
     */
    public function addField(string $label, string $value): self
    {
        $this->parts[] = "{$label}: {$value}";
        return $this;
    }

    /**
     * Add a blank line for spacing
     *
     * @return self
     */
    public function newLine(): self
    {
        $this->parts[] = '';
        return $this;
    }

    /**
     * Alias for newLine() - adds a blank line for spacing
     *
     * @return self
     */
    public function blank(): self
    {
        return $this->newLine();
    }

    /**
     * Add a section separator
     *
     * @return self
     */
    public function separator(): self
    {
        $this->parts[] = '---';
        return $this;
    }

    /**
     * Add a greeting message
     *
     * @param string $appName Application name
     * @return self
     */
    public function greeting(string $appName): self
    {
        $this->parts[] = "🎯 **Добро пожаловать в {$appName}!**";
        return $this;
    }

    /**
     * Add feature list
     *
     * @param array<string> $features Feature identifiers
     * @return self
     */
    public function addFeatures(array $features): self
    {
        $this->parts[] = 'Я помогу вам отслеживать:';

        $featureIcons = [
            'weight' => '⚖️ Вес и измерения тела',
            'measurements' => '📏 Замеры тела',
            'macros' => '🍎 Макронутриенты (КБЖУ)',
            'sync' => '🔄 Синхронизацию с FatSecret',
            'nutrition' => '🍎 Питание и калории',
            'steps' => '👟 Шаги и активность',
        ];

        foreach ($features as $feature) {
            if (isset($featureIcons[$feature])) {
                $this->parts[] = $featureIcons[$feature];
            }
        }

        return $this;
    }

    /**
     * Add a success message
     *
     * @param string $message Success message
     * @return self
     */
    public function success(string $message): self
    {
        $this->parts[] = "✅ **{$message}**";
        return $this;
    }

    /**
     * Add an error message
     *
     * @param string $message Error message
     * @return self
     */
    public function error(string $message): self
    {
        $this->parts[] = "❌ **{$message}**";
        return $this;
    }

    /**
     * Add a warning message
     *
     * @param string $message Warning message
     * @return self
     */
    public function warning(string $message): self
    {
        $this->parts[] = "⚠️ **{$message}**";
        return $this;
    }

    /**
     * Add an info message
     *
     * @param string $message Info message
     * @return self
     */
    public function info(string $message): self
    {
        $this->parts[] = "💡 **{$message}**";
        return $this;
    }

    /**
     * Add a bullet point list
     *
     * @param array<string> $items List items
     * @return self
     */
    public function bulletList(array $items): self
    {
        foreach ($items as $item) {
            $this->parts[] = "• {$item}";
        }
        return $this;
    }

    /**
     * Add a section with title and bullet list
     *
     * @param string $title Section title
     * @param array<string> $items List items for the section
     * @return self
     */
    public function addSection(string $title, array $items): self
    {
        $this->parts[] = $title;
        foreach ($items as $item) {
            $this->parts[] = $item;
        }
        return $this;
    }

    /**
     * Add a numbered list
     *
     * @param array<string> $items List items
     * @return self
     */
    public function numberedList(array $items): self
    {
        $count = 1;
        foreach ($items as $item) {
            $this->parts[] = "{$count}. {$item}";
            $count++;
        }
        return $this;
    }

    /**
     * Add code block
     *
     * @param string $code Code content
     * @return self
     */
    public function code(string $code): self
    {
        $this->parts[] = "`{$code}`";
        return $this;
    }

    /**
     * Add bold text
     *
     * @param string $text Text to make bold
     * @return self
     */
    public function bold(string $text): self
    {
        $this->parts[] = "**{$text}**";
        return $this;
    }

    /**
     * Add italic text
     *
     * @param string $text Text to make italic
     * @return self
     */
    public function italic(string $text): self
    {
        $this->parts[] = "*{$text}*";
        return $this;
    }

    /**
     * Add a link
     *
     * @param string $text Link text
     * @param string $url URL
     * @return self
     */
    public function link(string $text, string $url): self
    {
        $this->parts[] = "[{$text}]({$url})";
        return $this;
    }

    /**
     * Build the final message
     *
     * Joins all parts with newlines
     *
     * @return string
     */
    public function build(): string
    {
        return implode("\n", $this->parts);
    }

    /**
     * Reset the builder to initial state
     *
     * Clears all parts for reuse
     *
     * @return self
     */
    public function reset(): self
    {
        $this->parts = [];
        return $this;
    }

    /**
     * Create a new instance (static factory)
     *
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }
}