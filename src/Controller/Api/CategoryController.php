<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Enum\UserRole;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CategoryController extends AbstractController
{
    #[Route('/api/categories', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        $categories = $em->getRepository(Category::class)->findAll();

        return $this->json($categories);
    }

    #[Route('/api/categories/{id}', methods: ['GET'])]
    public function show(Category $category): JsonResponse
    {
        return $this->json($category);
    }

    #[Route('/api/categories', methods: ['POST'])]
    #[IsGranted(UserRole::ADMIN->value)]
    public function create(Request $request,
                           SerializerInterface $serializer,
                           EntityManagerInterface $em,
                           ValidatorInterface $validator
    ): JsonResponse {
        $jsonContent = $request->getContent();

        $category = $serializer->deserialize($jsonContent, Category::class, 'json');

        $errors = $validator->validate($category);

        if (count($errors) > 0) {
            return $this->json(["errors" => $errors], 422);
        }

        $em->persist($category);
        $em->flush();

        return $this->json($category);
    }

    #[Route('/api/categories/{id}', methods: ['PATCH'])]
    #[IsGranted(UserRole::ADMIN->value)]
    public function update(
        Request $request,
        Category $category,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {

        $serializer->deserialize(
            $request->getContent(),
            Category::class,
            'json',
            ['object_to_populate' => $category]
        );

        $errors = $validator->validate($category);
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], JsonResponse::HTTP_UNPROCESSABLE_ENTITY); // 422
        }

        $em->flush();

        return $this->json($category);
    }

    #[Route('/api/categories/{id}', methods: ['DELETE'])]
    #[IsGranted(UserRole::ADMIN->value)]
    function delete(Category $category,
                    EntityManagerInterface $em
    ): JsonResponse {
        $em->remove($category);
        $em->flush();

        return $this->json(
            ['message' => 'Категория успешно удалена'],
            JsonResponse::HTTP_OK,
            [],
            ['json_encode_options' => JSON_UNESCAPED_UNICODE]
        );
    }
}
