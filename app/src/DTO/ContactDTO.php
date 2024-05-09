<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ContactDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 255)]
        #[Assert\Regex(pattern: '/^[a-z\s]+$/i', message: 'Name can only contain letters and spaces')]
        private string $name = '',
        #[Assert\NotBlank]
        #[Assert\Email]
        private string $email = '',
        #[Assert\NotBlank]
        #[Assert\Length(min: 10, max: 255)]
        private string $message = ''
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}