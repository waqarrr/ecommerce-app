<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    #[Route('/api/orders', name: 'api_user_orders', methods: ['GET'])]
    public function getUserOrders(EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $orders = $entityManager->getRepository(Order::class)->findBy(['user' => $user]);

        $data = array_map(function (Order $order) {
            return [
                'id' => $order->getId(),
                'createdAt' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
                'total' => $order->getTotal(),
                'items' => $order->getItems()->map(function ($item) {
                    return [
                        'product' => [
                            'id' => $item->getProduct()->getId(),
                            'name' => $item->getProduct()->getName(),
                        ],
                        'quantity' => $item->getQuantity(),
                        'price' => $item->getPrice(),
                    ];
                })->toArray(),
            ];
        }, $orders);

        return $this->json($data);
    }

    #[Route('/api/admin/orders', name: 'api_admin_orders', methods: ['GET'])]
    public function getAllOrders(EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $orders = $entityManager->getRepository(Order::class)->findAll();

        $data = array_map(function (Order $order) {
            return [
                'id' => $order->getId(),
                'user' => [
                    'id' => $order->getUser()->getId(),
                    'email' => $order->getUser()->getEmail(),
                ],
                'createdAt' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
                'total' => $order->getTotal(),
                'items' => $order->getItems()->map(function ($item) {
                    return [
                        'product' => [
                            'id' => $item->getProduct()->getId(),
                            'name' => $item->getProduct()->getName(),
                        ],
                        'quantity' => $item->getQuantity(),
                        'price' => $item->getPrice(),
                    ];
                })->toArray(),
            ];
        }, $orders);

        return $this->json($data);
    }
}