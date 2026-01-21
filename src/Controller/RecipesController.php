<?php

namespace App\Controller;

use App\Entity\Recipes;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

//Paginator
use Knp\Component\Pager\PaginatorInterface;

final class RecipesController extends AbstractController
{
    private $em;
    private $slugger;

    public function __construct(EntityManagerInterface $em, SluggerInterface $slugger)
    {
        $this->em = $em;
        $this->slugger = $slugger;
    }

    #[Route('/api/v1/recipes', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): JsonResponse
    {
        $data = $this->em->getRepository(Recipes::class)->findAll();
        $pagination=$paginator->paginate($data, $request->query->getInt('page', 1), 2);

        /**
         * getCategory() Retorna toda la categoría
         * getCategory()->getId() Retorna Id
         * 'image' => $request->getUriForPath(''), Retorna URL del proyecto
         */
        foreach ($pagination as $c) {
            $res[] = [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'slug' => $c->getSlug(),
                'time' => $c->getTime(),
                'detail' => $c->getDetail(),
                'date' => $c->getDate()->format('d/m/Y'),
                'image' => $request->getUriForPath("/uploads/recipes/{$c->getImage()}"),
                'category_id' => $c->getCategory()->getId()
            ];
        }

        return $this->json($res, Response::HTTP_OK);
    }
}
