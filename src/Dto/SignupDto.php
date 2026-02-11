<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraint AS Assert;

class SignupDto
{
    public function __construct(

        #[Assert\NotBlank(message: 'El campo nombre esta vacio')]
        public readonly string $name,

        #[Assert\NotBlank( message: 'El campo tiempo está vació')]
        #[Assert\Email( message: 'El correo electrónico no es valido')]
        public readonly string $email,

        #[Assert\NotBlank( message: 'El campo password está vació')]
        public readonly string $password,
    
        )
    {}
}

/**
 * RecipesDto es una clase que se utiliza para transferir y validar datos
 */