<?php

namespace App\Controller;

use AddressInfo;
use App\Dto\LoginDto;
use App\Dto\SignupDto;
use App\Entity\State;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class UserController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/api/v1/auth/login', methods: ['POST'])]
    public function login(Request $request, #[MapRequestPayload] LoginDto $dto, UserPasswordHasherInterface $pwdHasher): JsonResponse
    {
        $inactive = $this->em->getRepository(State::class)->find(1);
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $dto->email,
            'statu' => $inactive
        ]);

        if (!$user) {
            return $this->json([
                'state' => 'error',
                'message' => "Las credenciales ingresadas no son válidas"
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($pwdHasher->isPasswordValid($user, $dto->password)) {

            $date = date_create(date('Y-m-d'));
            $timestamp = date_add($date, date_interval_create_from_date_string('1 days'));

            $payload = [
                'iss' => $request->getUriForPath(""),
                'aud' => $user->getId(),
                'iat' => time(),
                'exp' => strtotime($timestamp->format('Y-m-d'))
            ];

            //print_r($_ENV['JWT_SECRET']).die();
            $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS512');

            return $this->json([
                'id' => $user->getId(),
                'name' => $user->getName(),
                'token' => $jwt
            ]);
        } else {
            return $this->json([
                'state' => 'error',
                'message' => "Las credenciales ingresadas no son válidas"
            ], Response::HTTP_BAD_REQUEST);
        }
    }


    #[Route('/api/v1/auth/resgister', methods: ['POST'])]
    public function create(Request $request, #[MapRequestPayload] SignupDto $dto, UserPasswordHasherInterface $pwdHasher, MailerInterface $mailer): JsonResponse
    {
        $isExist = $this->em->getRepository(User::class)->findOneBy(['email' => $dto->email]);
        if ($isExist) {
            return $this->json([
                'state' => 'error',
                'message' => "El correo ya existe"
            ], Response::HTTP_BAD_REQUEST);
        }

        $state = $this->em->getRepository(State::class)->find(2);
        // Token generado aleatorio con la hora actual y es hashea
        $token = sha1(uniqid() . rand(1, 100000) . time());

        $user = new User();
        $user->setName($dto->name);
        $user->setEmail($dto->email);
        $user->setPassword($pwdHasher->hashPassword($user, $dto->password));
        $user->setRoles(['ROLE_USER']);
        $user->setStatu($state); // Inactivo
        $user->setToken($token);
        $this->em->persist($user);
        $this->em->flush();

        // Revisar funciones
        //$url = $request->getUriForPath("/auth/verification/");    
        //$this->sendMail($mailer, $user->getEmail(), $user->getName(), $url);

        return $this->json([
            'state' => 'success',
            'message' => "Usuario registrado existosamente"
        ], Response::HTTP_CREATED);
    }

    private function sendMail(MailerInterface $mailer, string $email, string $name, string $url)
    {
        $message = (new TemplatedEmail())
            ->from('') // correo
            ->to($email)
            ->subject('Verificación de cuenta')
            ->htmlTemplate('/mail/verifyAccount.html.twig')
            ->context([
                'name' => $name,
                'url' => $url
            ]);

        $mailer->send($message);
    }
}
