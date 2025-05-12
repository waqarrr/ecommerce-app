<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CheckoutController extends AbstractController
{
    #[Route('/api/checkout', name: 'api_checkout', methods: ['POST'])]
    public function checkout(EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $cart = $entityManager->getRepository(Cart::class)->findOneBy(['user' => $user]);

        if (!$cart || $cart->getItems()->isEmpty()) {
            return $this->json(['message' => 'Cart is empty'], 400);
        }

        $order = new Order();
        $order->setUser($user);
        $total = 0.0;

        foreach ($cart->getItems() as $cartItem) {
            $product = $cartItem->getProduct();
            $quantity = $cartItem->getQuantity();

            if ($product->getStock() < $quantity) {
                return $this->json(['message' => "Insufficient stock for product: {$product->getName()}"], 400);
            }

            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($quantity);
            $orderItem->setPrice($product->getPrice());
            $order->addItem($orderItem);

            $total += $product->getPrice() * $quantity;
            $product->setStock($product->getStock() - $quantity);
        }

        $order->setTotal($total);
        $entityManager->persist($order);

        // Clear cart
        foreach ($cart->getItems() as $cartItem) {
            $cart->removeItem($cartItem);
            $entityManager->remove($cartItem);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Order placed successfully',
            'orderId' => $order->getId(),
            'total' => $order->getTotal(),
        ], 201);
    }
}