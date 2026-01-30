<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraint AS Assert;

class RecipesDto
{
    public function __construct(

        #[Assert\Length(message: 'El campo nombre esta vacio')]
        public readonly string $name,

        #[Assert\Length( message: 'El campo tiempo esta vacio')]
        public readonly string $time,

        #[Assert\Length( message: 'El campo detalle esta vacio')]
        public readonly string $detail,

        #[Assert\Positive( message: 'El campo category_id debe ser numerico')]
        public readonly string $category_id,
        
        )
    {}
}

/**
 * RecipesDto es una clase que se utiliza para transferir y validar datos
 */