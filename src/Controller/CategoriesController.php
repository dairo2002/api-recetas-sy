<?php

namespace App\Controller;

use App\Dto\CategoryDto;
use App\Entity\Category;
use App\Entity\Recipes;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class CategoriesController extends AbstractController
{
    private $em;
    private $slugger;

    public function __construct(EntityManagerInterface $em, SluggerInterface $slugger)
    {
        $this->em = $em;
        $this->slugger = $slugger;
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

    /**
     * Crear una categoría
     */

    #[Route('api/v1/categories', methods: 'POST')]
    public function create(Request $request, #[MapRequestPayload] CategoryDto $dto): JsonResponse
    {
        $entity = new Category();
        $entity->setName($dto->name);
        $entity->setSlug($this->slugger->slug(strtolower($dto->name)));
        $this->em->persist($entity);
        $this->em->flush();

        return $this->json([
            "status" => "success",
            "message" => "Se registro una categoría correctamente"
        ], Response::HTTP_CREATED);
    }

    /**
     * Actualizar una categoría
     */

    #[Route('api/v1/categories/{id}', methods: ['PUT'])]
    public function update(Request $request, #[MapRequestPayload] CategoryDto $dto, int $id): JsonResponse
    {
        $categoryId = $this->em->getRepository(Category::class)->find($id);

        if (!$categoryId) {
            return $this->json([
                "status" => "Error",
                "message" => "No se encontro ninguna categoría"
            ], Response::HTTP_NOT_FOUND);
        }

        $categoryId->setName($dto->name);
        $categoryId->setSlug($this->slugger->slug(strtolower($dto->name)));
        $this->em->flush();

        return $this->json([
            "status" => "success",
            "message" => "Se modifico la categoría correctamente"
        ], Response::HTTP_CREATED);
    }

    /**
     * Eliminar una categoría
     * Si el registro no existe en las recetas se elimina 
     */
    #[Route('api/v1/categories/{id}', methods: ['DELETE'])]
    public function detete(Request $request, int $id): JsonResponse
    {
        $categoryId = $this->em->getRepository(Category::class)->find($id);

        if (!$categoryId) {
            return $this->json([
                "status" => "error",
                "message" => "No se encontro ninguna categoría"
            ], Response::HTTP_NOT_FOUND);
        }
        
        $exist = $this->em->getRepository(Recipes::class)->findBy(array('category' => $id));
        if ($exist) {
            return $this->json([
                "status" => "error",
                "message" => "No se puede eliminar el registro porque la receta se encuntra en uso"
            ], Response::HTTP_BAD_REQUEST);
        } else {
            $this->em->remove($categoryId);
            $this->em->flush();

            return $this->json([
                "status" => "success",
                "message" => "Se elimino el registro"
            ], Response::HTTP_OK);
        }


    }
}
