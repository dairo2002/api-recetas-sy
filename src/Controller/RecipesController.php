<?php

namespace App\Controller;

use App\Dto\RecipesDto;
use App\Entity\Category;
use App\Entity\Recipes;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Knp\Component\Pager\PaginatorInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


final class RecipesController extends AbstractController
{
    private $em;
    private $slugger;

    public function __construct(EntityManagerInterface $em, SluggerInterface $slugger)
    {
        $this->em = $em;
        $this->slugger = $slugger;
    }

    /**
     * Obtener todos los productos con una paginación
     */

    #[Route('/api/v1/recipes', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): JsonResponse
    {
        $data = $this->em->getRepository(Recipes::class)->findAll();
        $pagination = $paginator->paginate($data, $request->query->getInt('page', 1), 2);

        /**
         * getCategory() Retorna toda la categoría
         * getCategory()->getId() Retorna Id
         * 'image' => $request->getUriForPath(''), Retorna URL del proyecto
         */
        foreach ($pagination as $c) {
            $res[] = [
                'id'          => $c->getId(),
                'name'        => $c->getName(),
                'slug'        => $c->getSlug(),
                'time'        => $c->getTime(),
                'detail'      => $c->getDetail(),
                'date'        => $c->getDate()->format('d/m/Y'),
                'image'       => $request->getUriForPath("/uploads/recipes/{$c->getImage()}"),
                'category_id' => $c->getCategory()->getId(),
                'category'    => $c->getCategory()->getName(),
                'user_id'     => $c->getUser()->getId(),
                'user'        => $c->getUser()->getName()
            ];
        }

        return $this->json($res, Response::HTTP_OK);
    }

    /** 
     * Obtener los ultimos productos
     */

    #[Route('/api/v1/recipes-home', methods: ['GET'])]
    public function recipesHome(Request $request, PaginatorInterface $paginator): JsonResponse
    {
        $data = $this->em->getRepository(Recipes::class)->findBy(array(), array('id' => 'desc'), 3);

        /**
         * getCategory() Retorna toda la categoría
         * getCategory()->getId() Retorna Id
         * 'image' => $request->getUriForPath(''), Retorna URL del proyecto
         */
        foreach ($data as $c) {
            $res[] = [
                'id'          => $c->getId(),
                'name'        => $c->getName(),
                'slug'        => $c->getSlug(),
                'time'        => $c->getTime(),
                'detail'      => $c->getDetail(),
                'date'        => $c->getDate()->format('d/m/Y'),
                'image'       => $request->getUriForPath("/uploads/recipes/{$c->getImage()}"),
                'category_id' => $c->getCategory()->getId(),
                'category'    => $c->getCategory()->getName(),
                'user_id'     => $c->getUser()->getId(),
                'user'        => $c->getUser()->getName()
            ];
        }

        return $this->json($res, Response::HTTP_OK);
    }

    /**
     * Buscador
     */

    #[Route('/api/v1/recipes-search', methods: ['GET'])]
    public function searchRecipes(Request $request): JsonResponse
    {
        // Obtener el id de la categoría
        $categoryId = $request->query->get('category_id');
        $category = $this->em->getRepository(Category::class)->find($categoryId);

        if (!$category) {
            return $this->json([
                'status' => 'error',
                'message' => 'La URL no esta disponible'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = $this->em->getRepository(Recipes::class)
            ->createQueryBuilder('qb')
            ->andWhere('qb.nombre LIKE :search')
            ->setParameter('search', '%' . $request->query->get('search') . '%')
            ->andWhere('r.categoria = ' . $request->query->get('category_id'))
            ->getQuery()
            ->getResult();

        if (count($data) > 0) {
            $res[] = [];
        } else {
            foreach ($data as $c) {
                $res[] = [
                    'id'          => $c->getId(),
                    'name'        => $c->getName(),
                    'slug'        => $c->getSlug(),
                    'time'        => $c->getTime(),
                    'detail'      => $c->getDetail(),
                    'date'        => $c->getDate()->format('d/m/Y'),
                    'image'       => $request->getUriForPath("/uploads/recipes/{$c->getImage()}"),
                    'category_id' => $c->getCategory()->getId(),
                    'category'    => $c->getCategory()->getName(),
                    'user_id'     => $c->getUser()->getId(),
                    'user'        => $c->getUser()->getName()
                ];
            }
        }

        return $this->json($res, Response::HTTP_OK);
    }

    /**
     * Detalle de una categoria
     * 
     */

    #[Route('/api/v1/recipes/{slug}', methods: ['GET'])]
    public function showRecipes(Request $request, string $slug): JsonResponse
    {
        $recipe = $this->em->getRepository(Recipes::class)->findBy(
            array('slug' => $slug),
            array()
        );

        if (!$recipe) {
            return $this->json([
                'status' => 'error',
                'message' => 'La URL no esta disponible'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id'          => $recipe[0]->getId(),
            'name'        => $recipe[0]->getName(),
            'slug'        => $recipe[0]->getSlug(),
            'time'        => $recipe[0]->getTime(),
            'detail'      => $recipe[0]->getDetail(),
            'date'        => $recipe[0]->getDate()->format('d/m/Y'),
            'image'       => $request->getUriForPath("/uploads/recipes/{$recipe[0]->getImage()}"),
            'category_id' => $recipe[0]->getCategory()->getId(),
            'category'    => $recipe[0]->getCategory()->getName(),
            'user_id'     => $recipe[0]->getUser()->getId(),
            'user'        => $recipe[0]->getUser()->getName()
        ], Response::HTTP_OK);
    }

    #[Route('/api/v1/recipes/create', methods: ['POST'])]
    public function createRecipes(Request $request, #[MapRequestPayload()] RecipesDto $dto): JsonResponse
    {
        $category_id = $this->em->getRepository(Category::class)->find($dto->category_id);
        if (!$category_id) {
            return $this->json([
                'status' => 'error',
                'message' => 'La URL no esta disponible'
            ], Response::HTTP_NOT_FOUND);
        }

        $existSlug = $this->em->getRepository(Recipes::class)->findBy(
            array('slug' => $this->slugger->slug($dto->name)),
            array()
        );

        if ($existSlug) {
            return $this->json([
                'status' => 'error',
                'message' => 'La URL no esta disponible'
            ], Response::HTTP_NOT_FOUND);
        }

        $img = $request->files->get('imagen');
        if ($img) {

            $newFileName = time() . '.' . $img->guessExtension();

            try {
                
                $decode = JWT::decode($request->headers->get('X-AUTH-TOKEN'), new Key($_ENV['JWT_SECRET'], 'HS512'));
                $user = $this->em->getRepository(User::class)->findOneBy(['id' => $decode->aud]);

                $img->move($this->getParameter('recetas_directory'), $newFileName);
                $save = new Recipes();
                $save->setName($dto->name);
                $save->setSlug($this->slugger->slug(strtolower($dto->name)));
                $save->setTime($dto->time);
                $save->setDetail($dto->detail);
                $save->setCategory($category_id); // Si existe la categoria se crea
                $save->setDate(new \DateTime());
                $save->setImage($newFileName);
                $save->setUser($user);
                $this->em->persist($save);
                $this->em->flush();

                return $this->json([
                    'status' => 'success',
                    'message' => 'Se creo la receta exitosamente'
                ], Response::HTTP_OK);
            } catch (FileException $th) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'La imagen no existe'
                ], Response::HTTP_NOT_FOUND);
            }
        } else {
            return $this->json([
                'status' => 'error',
                'message' => 'La imagen no existe'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/api/v1/recipes/update/{id}', methods: ['POST'])]
    public function updateRecipes(Request $request, #[MapRequestPayload()] RecipesDto $dto, int $id): JsonResponse
    {
        $uRecipes = $this->em->getRepository(Recipes::class)->find($id);
        if (!$uRecipes) {
            return $this->json([
                'status' => 'error',
                'message' => 'No existe el id de la receta'
            ], Response::HTTP_NOT_FOUND);
        }

        $categoryId = $this->em->getRepository(Category::class)->find($dto->category_id);
        if (!$categoryId) {
            return $this->json([
                'status' => 'error',
                'message' => 'No existe el id de la categoría'
            ], Response::HTTP_NOT_FOUND);
        }

        $uRecipes->setName($dto->name);
        $uRecipes->setSlug($this->slugger->slug(strtolower($dto->name)));
        $uRecipes->setTime($dto->time);
        $uRecipes->setDetail($dto->detail);
        $uRecipes->setCategory($categoryId);
        $this->em->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'La receta ha sido actualizada correctamente'
        ], Response::HTTP_OK);
    }

    #[Route('/api/v1/recipes/update-photos', methods: ['POST'])]
    public function updatePhotos(Request $request): JsonResponse
    {
        $recipeId = $this->em->getRepository(Recipes::class)->find($request->request->get('id'));
        if (!$recipeId) {
            return $this->json([
                'status' => 'error',
                'message' => 'La URL no esta disponible'
            ], Response::HTTP_NOT_FOUND);
        }

        $img = $request->files->get('imagen');
        if ($img) {
            $newFileName = time() . '.' . $img->guessExtension();
            try {

                $img->move($this->getParameter('recetas_directory'), $newFileName);
                unlink(getcwd() . '/uploads/recipes/' . $recipeId->getImagen);
                $recipeId->setImagen($newFileName);
                $this->em->flush();

                return $this->json([
                    'status' => 'error',
                    'message' => 'Se actualizo correctamente la imagen'
                ], Response::HTTP_OK);
            } catch (FileException $e) {

                return $this->json([
                    'status' => 'error',
                    'message' => 'La URL no esta disponible'
                ], Response::HTTP_NOT_FOUND);
            }
        } else {

            return $this->json([
                'status' => 'error',
                'message' => 'La URL no esta disponible'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/api/v1/recipes/delete/{id}', methods: ['DELETE'])]
    public function deleteRecipes(Request $request, int $id): JsonResponse
    {
        $recipeId = $this->em->getRepository(Recipes::class)->find($request->request->get('id'));

        if (!$recipeId) {
            return $this->json([
                'status' => 'error',
                'message' => 'La URL no esta disponible'
            ], Response::HTTP_NOT_FOUND);
        }

        unlink(getcwd() . '/uploads/recipes/' . $recipeId->getImagen);
        $this->em->remove($recipeId);
        $this->em->flush();

        return $this->json([
            'status' => 'succes',
            'message' => 'Se elimino correctamente la imagen'
        ], Response::HTTP_OK);
    }
}
