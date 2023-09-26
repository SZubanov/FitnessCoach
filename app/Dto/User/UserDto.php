<?php
declare(strict_types=1);

namespace App\Dto\User;

class UserDto
{
    public function __construct(
        private int $id,
        private string $name,
        private string $email,
        private int $defaultMeasureSystem,
        private bool $isFatSecretActive
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getDefaultMeasureSystem(): int
    {
        return $this->defaultMeasureSystem;
    }

    public function getIsFatSecretActive(): bool
    {
        return $this->isFatSecretActive;
    }


}
