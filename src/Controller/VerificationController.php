<?php

namespace App\Controller;

use App\Entity\State;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

final class VerificationController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/auth/verification/{token}', methods:['GET'])]
    public function show(string $token): Response
    {
        $state = $this->em->getRepository(State::class);
        $inactive = $state->find("2");

        $user = $this->em->getRepository(User::class)->findOneBy([
            'token' => $token,
            'statu' => $inactive
        ]);

        if (!$user) {
            return $this->json([
                'status' => 'error',
                'message' => 'Recurso no disponible'
            ], Response::HTTP_NOT_FOUND);
        }
        
        $active = $state->find(1);
        $user->setToken(''); // limpiar el token
        $user->setStatu($active);
        $this->em->flush();

        // rediccionar
        return $this->redirect("http://127.0.0.1:8000/login");
    }
}
