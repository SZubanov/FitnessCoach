<?php

namespace App\Telegram\Keyboards;

use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;

/**
 * Keyboard Builder
 *
 * Fluent API for building custom keyboards programmatically.
 * Useful for dynamic keyboards that depend on runtime data.
 */
class KeyboardBuilder
{
    /**
     * @var array<Button>
     */
    private array $buttons = [];

    /**
     * @var array<array<Button>>
     */
    private array $rows = [];

    /**
     * @var bool
     */
    private bool $useRows = false;

    /**
     * Add a button to the current row
     *
     * @param string $text Button text
     * @param string $action Callback action name
     * @return self
     */
    public function addButton(string $text, string $action): self
    {
        $this->buttons[] = Button::make($text)->action($action);
        return $this;
    }

    /**
     * Add a URL button (opens external link)
     *
     * @param string $text Button text
     * @param string $url External URL
     * @return self
     */
    public function addUrlButton(string $text, string $url): self
    {
        $this->buttons[] = Button::make($text)->url($url);
        return $this;
    }

    /**
     * Add main menu button
     *
     * Convenience method for the most common button
     *
     * @return self
     */
    public function addMainMenuButton(): self
    {
        return $this->addButton('🏠 Главное меню', 'mainMenu');
    }

    /**
     * Add back button
     *
     * @param string $action Action to navigate back to
     * @return self
     */
    public function addBackButton(string $action): self
    {
        return $this->addButton('↩️ Назад', $action);
    }

    /**
     * Start a new row
     *
     * Subsequent buttons will be added to a new row
     *
     * @return self
     */
    public function newRow(): self
    {
        if (!empty($this->buttons)) {
            $this->rows[] = $this->buttons;
            $this->buttons = [];
            $this->useRows = true;
        }
        return $this;
    }

    /**
     * Add multiple buttons from array
     *
     * @param array<array{text: string, action: string}> $buttons
     * @return self
     */
    public function addButtons(array $buttons): self
    {
        foreach ($buttons as $button) {
            $this->addButton($button['text'], $button['action']);
        }
        return $this;
    }

    /**
     * Build the keyboard
     *
     * @return Keyboard
     */
    public function build(): Keyboard
    {
        if ($this->useRows) {
            // Add remaining buttons as final row
            if (!empty($this->buttons)) {
                $this->rows[] = $this->buttons;
            }

            // Build keyboard with rows
            $keyboard = Keyboard::make();
            foreach ($this->rows as $row) {
                $keyboard = $keyboard->row($row);
            }

            return $keyboard;
        }

        // Simple single-row keyboard
        return Keyboard::make()->buttons($this->buttons);
    }

    /**
     * Reset the builder to initial state
     *
     * @return self
     */
    public function reset(): self
    {
        $this->buttons = [];
        $this->rows = [];
        $this->useRows = false;
        return $this;
    }
}