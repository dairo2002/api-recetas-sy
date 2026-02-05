<?php

namespace App\Controller;

use App\Dto\ContactDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

// https://mailtrap.io/es/
// Utilizar comando de la libreria
use Symfony\Component\Mine\Email;
use Symfony\Component\Mailer\MailerIntarface;
use Symfony\Component\Mine\Address;
use Symfony\Component\Mailer\Exception\TrasnsportExceptionInterface;

final class ContactController extends AbstractController
{
    #[Route('/contact', methods: ['POST'])]
    public function create(Request $request, #[MapRequestPayload] ContactDto $dto): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ContactController.php',
        ]);
    }
}
