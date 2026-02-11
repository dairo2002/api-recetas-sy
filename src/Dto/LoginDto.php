<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraint AS Assert;

class LoginDto
{
    public function __construct(

        #[Assert\NotBlank( message: 'El campo tiempo esta vacio')]
        #[Assert\Email( message: 'El correo electrónico no es valido')]
        public readonly string $email,

        #[Assert\NotBlank( message: 'El campo password esta vacio')]
        public readonly string $password,
        
        )
    {}
}

/**
 * RecipesDto es una clase que se utiliza para transferir y validar datos
 */