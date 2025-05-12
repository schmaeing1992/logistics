<?php
// src/Controller/Api/ShipmentController.php

namespace App\Controller\Api;

use App\Entity\Package;
use App\Entity\Partner;
use App\Entity\Shipment;
use App\Exception\ValidationException;
use App\Repository\PartnerRepository;
use App\Service\LabelGeneratorService;
use App\Service\PartnerRoutingService;
use App\Service\TrackingNumberGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @OA\Tag(name: "Shipments")
 * @OA\Tag(name: "Packages")
 *
 * Sämtliche Endpunkte erfordern den Header **X-API-KEY**
 * (Security-Scheme „ApiKeyAuth“ – siehe `nelmio_api_doc.yaml`).
 */
class ShipmentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface         $em,
        private readonly SerializerInterface            $serializer,
        private readonly TrackingNumberGeneratorService $tnGen,
        private readonly LabelGeneratorService          $labelService,
        private readonly PartnerRoutingService          $routing,
        private readonly PartnerRepository              $partnerRepo,
        private readonly ValidatorInterface             $validator,
    ) {}

    /**
     * Hilfsfunktion: Suche Partner anhand der 3-stelligen Stationsnummer.
     */
    private function partnerByStation(?int $station): ?Partner
    {
        return $station
            ? $this->partnerRepo->findOneBy(['stationNumber' => $station])
            : null;
    }

    /* ======================================================================
     * SHIPMENT – LIST
     * ==================================================================== */
    #[Route('/api/shipments', name: 'api_shipments_list', methods: ['GET'])]
    #[OA\Get(
        summary: 'Alle Sendungen (paginierbar)',
        parameters: [
            new OA\Parameter(name: 'trackingNumber', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page',           in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit',          in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'sort',           in: 'query', schema: new OA\Schema(type: 'string', enum: ['createdAt','trackingNumber'])),
            new OA\Parameter(name: 'order',          in: 'query', schema: new OA\Schema(type: 'string', enum: ['ASC','DESC']))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste mit Meta-Daten',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: Shipment::class, groups: ['shipment:read']))
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth'=>[]]]
    )]
    public function list(Request $request): JsonResponse
    {
        $qb = $this->em->getRepository(Shipment::class)->createQueryBuilder('s');

        if ($tn = $request->query->getInt('trackingNumber')) {
            $qb->andWhere('s.trackingNumber = :tn')->setParameter('tn', $tn);
        }

        $allowedSort = ['createdAt','trackingNumber'];
        $sort  = $request->query->getAlpha('sort',  'createdAt');
        $order = strtoupper($request->query->getAlpha('order', 'DESC'));
        $sort  = \in_array($sort,  $allowedSort, true) ? $sort  : 'createdAt';
        $order = \in_array($order, ['ASC','DESC'],true) ? $order : 'DESC';
        $qb->orderBy("s.$sort", $order);

        $page  = max(1, $request->query->getInt('page',  1));
        $limit = min(100, max(1, $request->query->getInt('limit',20)));
        $qb->setFirstResult(($page-1)*$limit)->setMaxResults($limit);

        $paginator = new Paginator($qb);
        $payload   = [
            'meta' => [
                'total' => \count($paginator),
                'page'  => $page,
                'pages' => (int)\ceil(\count($paginator)/$limit),
                'limit' => $limit
            ],
            'data' => \iterator_to_array($paginator)
        ];

        return new JsonResponse(
            $this->serializer->serialize($payload,'json',['groups'=>['shipment:read']]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    /* ======================================================================
     * SHIPMENT – CREATE
     * ==================================================================== */
    #[Route('/api/shipments', name: 'api_shipments_create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Neue Sendung anlegen',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: Shipment::class, groups: ['shipment:write']))
        ),
        responses: [
            new OA\Response(response: 201, description: 'Sendung erzeugt', content:
                new OA\JsonContent(ref: new Model(type: Shipment::class, groups: ['shipment:read']))),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth'=>[]]]
    )]
    public function create(Request $request): JsonResponse
    {
        /** @var Shipment $shipment */
        $shipment = $this->serializer->deserialize(
            $request->getContent(),
            Shipment::class,
            'json',
            ['groups'=>['shipment:write']]
        );

        /* ---------- Tracking-Nr. setzen ---------- */
        $shipment->setTrackingNumber($this->tnGen->getNextShipmentNumber());

        /* ---------- Partner-Routing / Overrides ---------- */
        $payload = json_decode($request->getContent(), true) ?: [];
        $pickupStation   = $payload['pickup_partner_id']   ?? null;
        $deliveryStation = $payload['delivery_partner_id'] ?? null;

        $pickupPartner   = $this->partnerByStation($pickupStation);
        $deliveryPartner = $this->partnerByStation($deliveryStation);

        if ($pickupStation && !$pickupPartner) {
            return new JsonResponse(['error'=>'pickup_partner_id ungültig'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($deliveryStation && !$deliveryPartner) {
            return new JsonResponse(['error'=>'delivery_partner_id ungültig'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var Partner|null $bookingPartner */
        $bookingPartner = $this->getUser()?->getPartner();

        // immer erst per PLZ routen
        $this->routing->assignPartners($shipment, $bookingPartner);

        // dann manuelle Overrides
        if ($pickupPartner)   { $shipment->setPickupPartner($pickupPartner); }
        if ($deliveryPartner) { $shipment->setDeliveryPartner($deliveryPartner); }
        // bookingPartner ist immer der anlegende Partner
        if ($bookingPartner)  { $shipment->setBookingPartner($bookingPartner); }

        /* ---------- Validierung ---------- */
        $violations = $this->validator->validate($shipment);
        if (\count($violations) > 0) {
            throw new ValidationException($violations);
        }

        /* ---------- Pakete & Labels ---------- */
        $total = \count($shipment->getPackages());
        foreach ($shipment->getPackages() as $i=>$pkg) {
            $pkg->setPackageNumber($this->tnGen->getNextShipmentNumber());
            $pkg->setLabelBase64(
                base64_encode($this->labelService->generatePackagePdf($pkg, $i+1, $total))
            );
        }
        $shipment->setLabelBase64($this->labelService->generateLabel($shipment))
                 ->setCreatedAt(new \DateTimeImmutable())
                 ->setUpdatedAt(new \DateTimeImmutable());

        /* ---------- Persistieren ---------- */
        $this->em->persist($shipment);
        $this->em->flush();

        return new JsonResponse(
            $this->serializer->serialize($shipment,'json',['groups'=>['shipment:read']]),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    /* ======================================================================
     * SHIPMENT – SHOW
     * ==================================================================== */
    #[Route('/api/shipments/{trackingNumber}', name: 'api_shipments_show', methods: ['GET'])]
    #[OA\Get(
        summary: 'Einzelne Sendung abrufen',
        parameters: [
            new OA\Parameter(name: 'trackingNumber', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Sendung',
                content: new OA\JsonContent(ref: new Model(type: Shipment::class, groups: ['shipment:read']))),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth'=>[]]]
    )]
    public function show(string $trackingNumber): JsonResponse
    {
        $shipment = $this->em->getRepository(Shipment::class)
                             ->findOneBy(['trackingNumber'=>$trackingNumber]);
        if (!$shipment) {
            return new JsonResponse(['error'=>'Shipment not found'], Response::HTTP_NOT_FOUND);
        }

        // Labels frisch erzeugen
        $total = \count($shipment->getPackages());
        foreach ($shipment->getPackages() as $i=>$pkg) {
            $pkg->setLabelBase64(
                base64_encode($this->labelService->generatePackagePdf($pkg, $i+1, $total))
            );
        }

        return new JsonResponse(
            $this->serializer->serialize($shipment,'json',['groups'=>['shipment:read']]),
            Response::HTTP_OK,
            [],
            true
        );
    }
    /* ======================================================================
     * SHIPMENT – UPDATE (PUT|PATCH)
     * ==================================================================== */
    #[Route('/api/shipments/{trackingNumber}', name: 'api_shipments_update', methods: ['PUT','PATCH'])]
    #[OA\Put(
        summary: 'Sendung aktualisieren',
        parameters: [
            new OA\Parameter(name: 'trackingNumber', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: Shipment::class, groups: ['shipment:write']))
        ),
        responses: [
            new OA\Response(response: 200, description: 'Aktualisierte Sendung',
                content: new OA\JsonContent(ref: new Model(type: Shipment::class, groups: ['shipment:read']))),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth'=>[]]]
    )]
    #[OA\Patch(
        summary: 'Sendung patchen',
        parameters: [
            new OA\Parameter(name: 'trackingNumber', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: Shipment::class, groups: ['shipment:write']))
        ),
        responses: [
            new OA\Response(response: 200, description: 'Aktualisierte Sendung',
                content: new OA\JsonContent(ref: new Model(type: Shipment::class, groups: ['shipment:read']))),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth'=>[]]]
    )]
    public function update(string $trackingNumber, Request $request): JsonResponse
    {
        $shipment = $this->em->getRepository(Shipment::class)
                             ->findOneBy(['trackingNumber'=>$trackingNumber]);
        if (!$shipment) {
            return new JsonResponse(['error'=>'Shipment not found'], Response::HTTP_NOT_FOUND);
        }

        $this->serializer->deserialize(
            $request->getContent(),
            Shipment::class,
            'json',
            ['object_to_populate'=>$shipment,'groups'=>['shipment:write']]
        );

        /* Partner-Routing / Overrides wie in create() */
        $payload = json_decode($request->getContent(), true) ?: [];
        $pickupStation   = $payload['pickup_partner_id']   ?? null;
        $deliveryStation = $payload['delivery_partner_id'] ?? null;

        $pickupPartner   = $this->partnerByStation($pickupStation);
        $deliveryPartner = $this->partnerByStation($deliveryStation);

        if ($pickupStation && !$pickupPartner) {
            return new JsonResponse(['error'=>'pickup_partner_id ungültig'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($deliveryStation && !$deliveryPartner) {
            return new JsonResponse(['error'=>'delivery_partner_id ungültig'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $bookingPartner = $this->getUser()?->getPartner();
        $this->routing->assignPartners($shipment, $bookingPartner);
        if ($pickupPartner)   { $shipment->setPickupPartner($pickupPartner); }
        if ($deliveryPartner) { $shipment->setDeliveryPartner($deliveryPartner); }
        if ($bookingPartner)  { $shipment->setPayerPartner($bookingPartner); }

        /* Validierung */
        $violations = $this->validator->validate($shipment);
        if (\count($violations) > 0) {
            throw new ValidationException($violations);
        }

        /* Labels refresh */
        $total = \count($shipment->getPackages());
        foreach ($shipment->getPackages() as $i=>$pkg) {
            $pkg->setLabelBase64(
                base64_encode($this->labelService->generatePackagePdf($pkg, $i+1, $total))
            );
        }
        $shipment->setLabelBase64($this->labelService->generateLabel($shipment))
                 ->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return new JsonResponse(
            $this->serializer->serialize($shipment,'json',['groups'=>['shipment:read']]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    /* ======================================================================
     * SHIPMENT – DELETE
     * ==================================================================== */
    #[Route('/api/shipments/{trackingNumber}', name: 'api_shipments_delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Sendung stornieren (Soft-Delete)',
        parameters: [
            new OA\Parameter(name: 'trackingNumber', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],    
        responses: [
            new OA\Response(response: 204, description: 'Gelöscht'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth'=>[]]]
    )]
    public function delete(string $trackingNumber): JsonResponse
    {
        $shipment = $this->em->getRepository(Shipment::class)
                             ->findOneBy(['trackingNumber'=>$trackingNumber]);
        if (!$shipment) {
            return new JsonResponse(['error'=>'Shipment not found'], Response::HTTP_NOT_FOUND);
        }

        $shipment->setCancelledAt(new \DateTimeImmutable());
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /* ======================================================================
     * PACKAGE – SHOW
     * ==================================================================== */
    #[Route('/api/packages/{packageNumber}', name: 'api_package_show', methods: ['GET'])]
    #[OA\Get(
        summary: 'Einzelnes Paket abrufen',
        parameters: [
            new OA\Parameter(name: 'packageNumber', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paket',
                content: new OA\JsonContent(ref: new Model(type: Package::class, groups: ['shipment:read']))),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth'=>[]]]
    )]
    public function showPackage(int $packageNumber): JsonResponse
    {
        $pkg = $this->em->getRepository(Package::class)
                        ->findOneBy(['packageNumber'=>$packageNumber]);
        if (!$pkg) {
            return new JsonResponse(['error'=>'Package not found'], Response::HTTP_NOT_FOUND);
        }

        $pkg->setLabelBase64(
            base64_encode($this->labelService->generatePackagePdf($pkg, 1, 1))
        );

        return new JsonResponse(
            $this->serializer->serialize($pkg,'json',['groups'=>['shipment:read','shipment:write']]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    /* ======================================================================
     * PACKAGE – UPDATE (PUT|PATCH)
     * ==================================================================== */
    #[Route('/api/packages/{packageNumber}', name: 'api_package_update', methods: ['PUT','PATCH'])]
    #[OA\Put(
        summary: 'Paket ersetzen / aktualisieren',
        parameters: [
            new OA\Parameter(name: 'packageNumber', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: Package::class, groups: ['shipment:write']))
        ),
        responses: [
            new OA\Response(response: 200, description: 'Aktualisiertes Paket',
                content: new OA\JsonContent(ref: new Model(type: Package::class, groups: ['shipment:read']))),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth'=>[]]]
    )]
    #[OA\Patch(
        summary: 'Paket patchen',
        parameters: [
            new OA\Parameter(name: 'packageNumber', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: Package::class, groups: ['shipment:write']))
        ),
        responses: [
            new OA\Response(response: 200, description: 'Aktualisiertes Paket',
                content: new OA\JsonContent(ref: new Model(type: Package::class, groups: ['shipment:read']))),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth'=>[]]]
    )]
    public function updatePackage(int $packageNumber, Request $request): JsonResponse
    {
        $pkg = $this->em->getRepository(Package::class)
                        ->findOneBy(['packageNumber'=>$packageNumber]);
        if (!$pkg) {
            return new JsonResponse(['error'=>'Package not found'], Response::HTTP_NOT_FOUND);
        }

        $this->serializer->deserialize(
            $request->getContent(),
            Package::class,
            'json',
            ['object_to_populate'=>$pkg,'groups'=>['shipment:write']]
        );

        $violations = $this->validator->validate($pkg);
        if (\count($violations) > 0) {
            throw new ValidationException($violations);
        }

        $pkg->setLabelBase64(
            base64_encode($this->labelService->generatePackagePdf($pkg, 1, 1))
        )->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return new JsonResponse(
            $this->serializer->serialize($pkg,'json',['groups'=>['shipment:read','shipment:write']]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    /* ======================================================================
     * PACKAGE – DELETE
     * ==================================================================== */
    #[Route('/api/packages/{packageNumber}', name: 'api_package_delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Paket löschen (Soft-Delete)',
        parameters: [
            new OA\Parameter(name: 'packageNumber', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Gelöscht'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth'=>[]]]
    )]
    public function deletePackage(int $packageNumber): JsonResponse
    {
        $pkg = $this->em->getRepository(Package::class)
                        ->findOneBy(['packageNumber'=>$packageNumber]);
        if (!$pkg) {
            return new JsonResponse(['error'=>'Package not found'], Response::HTTP_NOT_FOUND);
        }

        $pkg->setCancelledAt(new \DateTimeImmutable());
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /* ======================================================================
     * SHIPMENT – ADD PACKAGE
     * ==================================================================== */
    #[Route('/api/shipments/{trackingNumber}/packages', name: 'api_shipment_add_package', methods: ['POST'])]
    #[OA\Post(
        summary: 'Neues Paket zu bestehender Sendung hinzufügen',
        parameters: [
            new OA\Parameter(name: 'trackingNumber', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: Package::class, groups: ['shipment:write']))
        ),
        responses: [
            new OA\Response(response: 201, description: 'Paket angelegt',
                content: new OA\JsonContent(ref: new Model(type: Package::class, groups: ['shipment:read']))),
            new OA\Response(response: 404, description: 'Shipment not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth'=>[]]]
    )]
    public function addPackage(string $trackingNumber, Request $request): JsonResponse
    {
        $shipment = $this->em->getRepository(Shipment::class)
                             ->findOneBy(['trackingNumber'=>$trackingNumber]);
        if (!$shipment) {
            return new JsonResponse(['error'=>'Shipment not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var Package $newPkg */
        $newPkg = $this->serializer->deserialize(
            $request->getContent(),
            Package::class,
            'json',
            ['groups'=>['shipment:write']]
        );

        $violations = $this->validator->validate($newPkg);
        if (\count($violations) > 0) {
            throw new ValidationException($violations);
        }

        $newPkg->setShipment($shipment)
               ->setPackageNumber($this->tnGen->getNextShipmentNumber());

        $total = \count($shipment->getPackages()) + 1;
        $newPkg->setLabelBase64(
            base64_encode($this->labelService->generatePackagePdf($newPkg, $total, $total))
        );

        $this->em->persist($newPkg);
        $shipment->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        return new JsonResponse(
            $this->serializer->serialize($newPkg,'json',['groups'=>['shipment:read','shipment:write']]),
            Response::HTTP_CREATED,
            [],
            true
        );
    }
}
