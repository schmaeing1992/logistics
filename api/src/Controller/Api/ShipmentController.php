<?php
// src/Controller/Api/ShipmentController.php

namespace App\Controller\Api;

use App\Entity\Shipment;
use App\Service\TrackingNumberGeneratorService;
use App\Service\LabelGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/shipments', name: 'api_shipments_')]
class ShipmentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface          $em,
        private SerializerInterface             $serializer,
        private TrackingNumberGeneratorService  $tnGen,
        private LabelGeneratorService           $labelGen,
        private ValidatorInterface              $validator
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $qb = $this->em->getRepository(Shipment::class)
            ->createQueryBuilder('s');

        // --- Filter nach TrackingNumber
        if ($tn = $request->query->getInt('trackingNumber')) {
            $qb->andWhere('s.trackingNumber = :tn')
               ->setParameter('tn', $tn);
        }

        // --- Sort
        $allowedSort = ['createdAt','trackingNumber'];
        $sort  = $request->query->getAlpha('sort', 'createdAt');
        $order = strtoupper($request->query->getAlpha('order', 'DESC'));
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'createdAt';
        }
        if (!in_array($order, ['ASC','DESC'], true)) {
            $order = 'DESC';
        }
        $qb->orderBy("s.{$sort}", $order);

        // --- Pagination
        $page  = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $paginator = new Paginator($qb);
        $total     = count($paginator);
        $pages     = (int) ceil($total / $limit);

        $items = iterator_to_array($paginator);

        $payload = [
            'meta' => [
                'total' => $total,
                'page'  => $page,
                'pages' => $pages,
                'limit' => $limit,
            ],
            'data' => $items,
        ];

        $json = $this->serializer->serialize(
            $payload,
            'json',
            ['groups' => ['shipment:read']]
        );

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var Shipment $shipment */
        $shipment = $this->serializer->deserialize(
            $request->getContent(),
            Shipment::class,
            'json',
            ['groups' => ['shipment:write']]
        );

        // Validierung
        $errors = $this->validator->validate($shipment);
        if (count($errors) > 0) {
            $errorData = [];
            foreach ($errors as $err) {
                $errorData[$err->getPropertyPath()] = $err->getMessage();
            }
            return new JsonResponse(['errors' => $errorData], Response::HTTP_BAD_REQUEST);
        }

        // Tracking-Nummer
        $shipment->setTrackingNumber($this->tnGen->getNextShipmentNumber());

        // Paket-Nummern
        foreach ($shipment->getPackages() as $pkg) {
            $pkg->setPackageNumber($this->tnGen->getNextShipmentNumber());
        }

        // Label generieren
        $shipment->setLabelBase64($this->labelGen->generateLabel($shipment));

        // Timestamps
        $now = new \DateTimeImmutable();
        $shipment->setCreatedAt($now)
                 ->setUpdatedAt($now);

        $this->em->persist($shipment);
        $this->em->flush();

        $json = $this->serializer->serialize(
            $shipment,
            'json',
            ['groups' => ['shipment:read']]
        );

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Shipment $shipment): JsonResponse
    {
        $json = $this->serializer->serialize(
            $shipment,
            'json',
            ['groups' => ['shipment:read']]
        );
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT','PATCH'])]
    public function update(Shipment $shipment, Request $request): JsonResponse
    {
        // Das Shipment kommt bereits fertig per ParamConverter
        $this->serializer->deserialize(
            $request->getContent(),
            Shipment::class,
            'json',
            [
                'object_to_populate' => $shipment,
                'groups'             => ['shipment:write'],
            ]
        );

        // Validierung
        $errors = $this->validator->validate($shipment);
        if (count($errors) > 0) {
            $errorData = [];
            foreach ($errors as $err) {
                $errorData[$err->getPropertyPath()] = $err->getMessage();
            }
            return new JsonResponse(['errors' => $errorData], Response::HTTP_BAD_REQUEST);
        }

        $shipment->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        $json = $this->serializer->serialize(
            $shipment,
            'json',
            ['groups' => ['shipment:read']]
        );
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Shipment $shipment): JsonResponse
    {
        // Stornieren
        $shipment->setCancelledAt(new \DateTimeImmutable());
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
