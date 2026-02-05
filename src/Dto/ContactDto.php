<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraint as Assert;

class ContactDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'El campo nombre esta vacío')]
        #[Assert\Type('string')]
        public readonly string $name,

        #[Assert\NotBlank(message: 'El campo correo esta vacío')]
        #[Assert\Email(message: 'El correo ingresado no es válido')]
        public readonly string $Email,

        #[Assert\NotBlank(message: 'El campo telefono esta vacío')]
        #[Assert\Type('string')]
        public readonly string $Phone,

        #[Assert\NotBlank(message: 'El campo mensaje esta vacío')]
        #[Assert\Type('string')]
        public readonly string $message,
    ) {}
}
