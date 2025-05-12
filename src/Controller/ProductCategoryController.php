<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductCategoryController extends AbstractController
{
    #[Route('/api/admin/product/{productId}/category/{categoryId}', name: 'api_add_product_category', methods: ['POST'])]
    public function addCategory(int $productId, int $categoryId, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = $entityManager->getRepository(Product::class)->find($productId);
        $category = $entityManager->getRepository(Category::class)->find($categoryId);

        if (!$product || !$category) {
            return $this->json(['message' => 'Product or Category not found'], 404);
        }

        $product->addCategory($category);
        $entityManager->flush();

        return $this->json(['message' => 'Category added to product']);
    }

    #[Route('/api/admin/product/{productId}/category/{categoryId}', name: 'api_remove_product_category', methods: ['DELETE'])]
    public function removeCategory(int $productId, int $categoryId, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = $entityManager->getRepository(Product::class)->find($productId);
        $category = $entityManager->getRepository(Category::class)->find($categoryId);

        if (!$product || !$category) {
            return $this->json(['message' => 'Product or Category not found'], 404);
        }

        $product->removeCategory($category);
        $entityManager->flush();

        return $this->json(['message' => 'Category removed from product']);
    }
}