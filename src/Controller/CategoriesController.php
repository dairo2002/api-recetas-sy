<?php

namespace App\Controller;

use App\Dto\CategoryDto;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CategoriesController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('api/v1/categories', methods: 'GET')]
    public function index(): JsonResponse
    {
        $data = $this->em->getRepository(Category::class)->findAll();

        $res = array_map(fn($c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'slug' => $c->getSlug(),
        ], $data);

        return $this->json($res, Response::HTTP_OK);
    }

    #[Route('api/v1/categories/{id}', methods: 'GET')]
    public function getCategoryById(int $id): JsonResponse
    {
        $data = $this->em->getRepository(Category::class)->find($id);

        if (!$data) {
            return $this->json([
                "status" => "Error",
                "message" => "No se encontraron categorías"
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $data->getId(),
            'name' => $data->getName(),
            'slug' => $data->getSlug(),
        ], Response::HTTP_OK);
    }

    #[Route('api/v1/categories', methods: 'POST')]
    public function create(Request $request, #[MapRequestPayload] CategoryDto $dto): JsonResponse
    {
        $entity = new Category();
        $entity->setName($dto->name);
        $entity->setSlug($dto->name);
        $this->em->persist($entity);
        $this->em->flush();

        return $this->json([
            "status" => "Success",
            "message" => "Se registro una categoría correctamente"
        ], Response::HTTP_CREATED);
    }


}
