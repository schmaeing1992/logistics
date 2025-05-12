<?php
// src/Controller/Api/PackageStatusController.php

namespace App\Controller\Api;

use App\Entity\Package;
use App\Entity\PackageStatus;
use App\Repository\PackageRepository;
use App\Repository\StatusCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/packages', name: 'api_package_statuses_')]
#[OA\Tag(name: 'Package-Statuses')]
final class PackageStatusController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PackageRepository       $packageRepo,
        private StatusCodeRepository    $statusCodeRepo,
        private SerializerInterface     $serializer,
        private ValidatorInterface      $validator
    ) {}

    /* --------------------------------------------------------------------
     * 1) Routen mit UUID
     * ----------------------------------------------------------------- */

    #[Route('/{packageId}/statuses', name: 'list_by_id', methods: ['GET'])]
    #[OA\Get(
        summary: 'Status-Historie per Paket-UUID abrufen',
        parameters: [
            new OA\Parameter(
                name: 'packageId',
                in: 'path',
                required: true,
                description: 'UUID des Pakets',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste der Status-Einträge',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: PackageStatus::class, groups: ['status:read']))
                )
            ),
            new OA\Response(response: 404, description: 'Package not found'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth' => []]]
    )]
    public function listById(string $packageId): JsonResponse
    {
        $package = $this->packageRepo->find($packageId);
        if (!$package) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize(
            $package->getStatuses()->toArray(),
            'json',
            ['groups' => ['status:read']]
        );

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/{packageId}/statuses', name: 'create_by_id', methods: ['POST'])]
    #[OA\Post(
        summary: 'Neuen Status per Paket-UUID anlegen',
        parameters: [
            new OA\Parameter(
                name: 'packageId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'statusCode', type: 'string', example: 'DELIVERED'),
                    new OA\Property(property: 'note', type: 'string', nullable: true)
                ],
                required: ['statusCode']
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Status angelegt',
                content: new OA\JsonContent(ref: new Model(type: PackageStatus::class, groups: ['status:read']))
            ),
            new OA\Response(response: 400, description: 'Validation / Bad request'),
            new OA\Response(response: 404, description: 'Package not found'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth' => []]]
    )]
    public function createById(string $packageId, Request $request): JsonResponse
    {
        $package = $this->packageRepo->find($packageId);
        if (!$package) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        return $this->handleCreate($package, $request);
    }

    /* --------------------------------------------------------------------
     * 2) Routen mit Business-Package-Number
     * ----------------------------------------------------------------- */

    #[Route('/number/{packageNumber}/statuses', name: 'list_by_number', methods: ['GET'])]
    #[OA\Get(
        summary: 'Status-Historie per Package-Number abrufen',
        parameters: [
            new OA\Parameter(
                name: 'packageNumber',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste der Status-Einträge',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: PackageStatus::class, groups: ['status:read']))
                )
            ),
            new OA\Response(response: 404, description: 'Package not found'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth' => []]]
    )]
    public function listByNumber(int $packageNumber): JsonResponse
    {
        $package = $this->packageRepo->findOneByPackageNumber($packageNumber);
        if (!$package) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializer->serialize(
            $package->getStatuses()->toArray(),
            'json',
            ['groups' => ['status:read']]
        );

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/number/{packageNumber}/statuses', name: 'create_by_number', methods: ['POST'])]
    #[OA\Post(
        summary: 'Neuen Status per Package-Number anlegen',
        parameters: [
            new OA\Parameter(
                name: 'packageNumber',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'statusCode', type: 'string', example: 'IN_TRANSIT'),
                    new OA\Property(property: 'note', type: 'string', nullable: true)
                ],
                required: ['statusCode']
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Status angelegt',
                content: new OA\JsonContent(ref: new Model(type: PackageStatus::class, groups: ['status:read']))
            ),
            new OA\Response(response: 400, description: 'Validation / Bad request'),
            new OA\Response(response: 404, description: 'Package not found'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth' => []]]
    )]
    public function createByNumber(int $packageNumber, Request $request): JsonResponse
    {
        $package = $this->packageRepo->findOneByPackageNumber($packageNumber);
        if (!$package) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        return $this->handleCreate($package, $request);
    }

    /* --------------------------------------------------------------------
     * Gemeinsame Hilfsmethode – Create Status
     * ----------------------------------------------------------------- */
    private function handleCreate(Package $package, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['statusCode'])) {
            return new JsonResponse(['error' => 'statusCode fehlt'], Response::HTTP_BAD_REQUEST);
        }

        $statusCode = $this->statusCodeRepo->findOneBy(['code' => (string)$data['statusCode']]);
        if (!$statusCode) {
            return new JsonResponse(['error' => 'Ungültiger statusCode'], Response::HTTP_BAD_REQUEST);
        }

        $status = new PackageStatus();
        $status->setPackage($package)->setStatusCode($statusCode);
        if (isset($data['note'])) {
            $status->setNote((string)$data['note']);
        }

        $errors = $this->validator->validate($status);
        if (\count($errors) > 0) {
            $errData = [];
            foreach ($errors as $e) {
                $errData[$e->getPropertyPath()] = $e->getMessage();
            }
            return new JsonResponse(['errors' => $errData], Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($status);
        $this->em->flush();

        $json = $this->serializer->serialize($status, 'json', ['groups' => ['status:read']]);
        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }
}
