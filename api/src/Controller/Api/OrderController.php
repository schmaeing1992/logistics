<?php
// api/src/Controller/Api/OrderController.php

namespace App\Controller\Api;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/orders', name: 'api_orders_')]
class OrderController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $orders = $em->getRepository(Order::class)->findAll();
        $json = $serializer->serialize($orders, 'json', ['groups'=>['order:read']]);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $req, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $order = $serializer->deserialize($req->getContent(), Order::class, 'json', [
            'groups' => ['order:write']
        ]);

        $now = new \DateTimeImmutable();
        $order->setCreatedAt($now)->setUpdatedAt($now);

        $em->persist($order);
        $em->flush();

        $json = $serializer->serialize($order, 'json', ['groups'=>['order:read']]);
        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Order $order, SerializerInterface $serializer): JsonResponse
    {
        $json = $serializer->serialize($order, 'json', ['groups'=>['order:read']]);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT','PATCH'])]
    public function update(int $id, Request $req, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $order = $em->getRepository(Order::class)->find($id);
        if (!$order) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $serializer->deserialize($req->getContent(), Order::class, 'json', [
            'object_to_populate' => $order,
            'groups' => ['order:write']
        ]);

        $order->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        $json = $serializer->serialize($order, 'json', ['groups'=>['order:read']]);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $order = $em->getRepository(Order::class)->find($id);
        if (!$order) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $em->remove($order);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
