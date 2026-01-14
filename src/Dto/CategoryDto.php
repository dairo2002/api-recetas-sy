<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraint AS Assert;

class CategoryDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'El campo nombre esta vacío')]
        #[Assert\Length(min: 5, message: 'El campo nombre debe tener al menos 5 caracteres')]
        public readonly string $name)
    {
        
    }
}