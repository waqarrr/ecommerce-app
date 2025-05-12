<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CartController extends AbstractController
{
    #[Route('/api/cart', name: 'api_cart_get', methods: ['GET'])]
    public function getCart(EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $cart = $entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $entityManager->persist($cart);
            $entityManager->flush();
        }

        $items = $cart->getItems()->map(function (CartItem $item) {
            return [
                'id' => $item->getId(),
                'product' => [
                    'id' => $item->getProduct()->getId(),
                    'name' => $item->getProduct()->getName(),
                    'price' => $item->getProduct()->getPrice(),
                ],
                'quantity' => $item->getQuantity(),
            ];
        })->toArray();

        return $this->json(['items' => $items]);
    }

    #[Route('/api/cart/items', name: 'api_cart_add_item', methods: ['POST'])]
    public function addItem(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $productId = $data['productId'] ?? null;
        $quantity = $data['quantity'] ?? 1;

        $product = $entityManager->getRepository(Product::class)->find($productId);
        if (!$product) {
            return $this->json(['message' => 'Product not found'], 404);
        }

        if ($product->getStock() < $quantity) {
            return $this->json(['message' => 'Insufficient stock'], 400);
        }

        $cart = $entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $entityManager->persist($cart);
        }

        $cartItem = null;
        foreach ($cart->getItems() as $item) {
            if ($item->getProduct()->getId() === $productId) {
                $cartItem = $item;
                break;
            }
        }

        if ($cartItem) {
            $cartItem->setQuantity($cartItem->getQuantity() + $quantity);
        } else {
            $cartItem = new CartItem();
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $cart->addItem($cartItem);
        }

        $errors = $validator->validate($cartItem);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], 400);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Item added to cart']);
    }

    #[Route('/api/cart/items/{id}', name: 'api_cart_remove_item', methods: ['DELETE'])]
    public function removeItem(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $cart = $entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);
        if (!$cart) {
            return $this->json(['message' => 'Cart not found'], 404);
        }

        $cartItem = $entityManager->getRepository(CartItem::class)->find($id);
        if (!$cartItem || $cartItem->getCart() !== $cart) {
            return $this->json(['message' => 'Cart item not found'], 404);
        }

        $cart->removeItem($cartItem);
        $entityManager->remove($cartItem);
        $entityManager->flush();

        return $this->json(['message' => 'Item removed from cart']);
    }
}