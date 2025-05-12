<?php

namespace App\Controller;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategoryController extends AbstractController
{
    #[Route('/api/categories', name: 'api_category_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);
        $category = new Category();
        $category->setName($data['name'] ?? '');
        $category->setDescription($data['description'] ?? null);

        $errors = $validator->validate($category);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        $entityManager->persist($category);
        $entityManager->flush();

        return $this->json([
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
        ], 201);
    }

    #[Route('/api/categories', name: 'api_category_list', methods: ['GET'])]
    public function list(EntityManagerInterface $entityManager): JsonResponse
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $data = array_map(function (Category $category) {
            return [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'description' => $category->getDescription(),
                'products' => $category->getProducts()->map(fn($p) => $p->getId())->toArray(),
            ];
        }, $categories);

        return $this->json($data);
    }

    #[Route('/api/categories/{id}', name: 'api_category_get', methods: ['GET'])]
    public function get(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $category = $entityManager->getRepository(Category::class)->find($id);
        if (!$category) {
            return $this->json(['message' => 'Category not found'], 404);
        }

        return $this->json([
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
            'products' => $category->getProducts()->map(fn($p) => $p->getId())->toArray(),
        ]);
    }

    #[Route('/api/categories/{id}', name: 'api_category_update', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $category = $entityManager->getRepository(Category::class)->find($id);
        if (!$category) {
            return $this->json(['message' => 'Category not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $category->setName($data['name'] ?? $category->getName());
        $category->setDescription($data['description'] ?? $category->getDescription());

        $errors = $validator->validate($category);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        $entityManager->flush();

        return $this->json([
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
            'products' => $category->getProducts()->map(fn($p) => $p->getId())->toArray(),
        ]);
    }

    #[Route('/api/categories/{id}', name: 'api_category_delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $category = $entityManager->getRepository(Category::class)->find($id);
        if (!$category) {
            return $this->json(['message' => 'Category not found'], 404);
        }

        $entityManager->remove($category);
        $entityManager->flush();

        return $this->json(['message' => 'Category deleted']);
    }
}