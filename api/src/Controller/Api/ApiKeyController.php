<?php
// src/Controller/Api/ApiKeyController.php

namespace App\Controller\Api;

use App\Entity\ApiKey;
use App\Repository\PartnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/api_keys', name: 'api_keys_')]
#[OA\Tag(name: 'API-Keys')]
final class ApiKeyController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PartnerRepository      $partnerRepo,
        private SerializerInterface    $serializer,
        private ValidatorInterface     $validator,
    ) {}

    /* ===========================================================
     * 1) API-Key anlegen
     * ========================================================= */
    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        summary:   'Neuen API-Key für einen Partner ausstellen',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['partnerStation'],
                properties: [
                    new OA\Property(
                        property: 'partnerStation',
                        description: 'Stationsnummer (Partner, 3-stellig)',
                        type: 'integer',
                        minimum: 100,
                        maximum: 999,
                        example: 200
                    ),
                    new OA\Property(
                        property: 'rawToken',
                        description: 'Optional eigenes Klartext-Token (mind. 32 Zeichen). Wird sonst zufällig generiert.',
                        type: 'string',
                        nullable: true,
                        example: 'my-custom-api-token-123'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'API-Key erstellt (das Klartext-Token siehst du nur hier einmal!)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'token',   type: 'string'),
                        new OA\Property(property: 'station', type: 'integer'),
                        new OA\Property(property: 'id',      type: 'integer'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Partner nicht gefunden'
            ),
            new OA\Response(
                response: 400,
                description: 'Validierungsfehler'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized'
            ),
        ],
        security: [['ApiKeyAuth'=>[]]]
    )]
    public function create(Request $req): JsonResponse
    {
        $data = json_decode($req->getContent(), true) ?? [];

        // 1) Minimal-Validierung
        $violations = $this->validator->validate(
            $data,
            new Assert\Collection([
                'partnerStation' => [new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\Range(min:100, max:999)],
                'rawToken'       => [new Assert\Optional([new Assert\Length(min:32)])],
            ])
        );

        if ($violations->count() > 0) {
            return new JsonResponse(['errors' => (string)$violations], Response::HTTP_BAD_REQUEST);
        }

        // 2) Partner holen
        $partner = $this->partnerRepo->findOneBy(['stationNumber' => (int)$data['partnerStation']]);
        if (!$partner) {
            return new JsonResponse(['error' => 'Partner not found'], Response::HTTP_NOT_FOUND);
        }

        // 3) Klartext-Token bestimmen
        $rawToken = $data['rawToken'] ?? bin2hex(random_bytes(32));

        // 4) Entity anlegen & speichern
        $apiKey = new ApiKey($rawToken, $partner);
        $this->em->persist($apiKey);
        $this->em->flush();

        // 5) Antwort – Token NUR EINMAL zurückgeben!
        return new JsonResponse(
            [
                'token'   => $rawToken,
                'station' => $partner->getStationNumber(),
                'id'      => $apiKey->getId(),
            ],
            Response::HTTP_CREATED
        );
    }

    /* ===========================================================
     * 2) Key aktivieren/deaktivieren
     *    PUT /api/api_keys/{id}?active=0|1
     * ========================================================= */
    #[Route('/{id}', name: 'toggle', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[OA\Put(
        summary: 'API-Key aktivieren/deaktivieren',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'active',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'boolean')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status geändert',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id',     type: 'integer'),
                        new OA\Property(property: 'active', type: 'boolean'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Key nicht gefunden'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized'
            ),
        ],
        security: [['ApiKeyAuth'=>[]]]
    )]
    public function toggle(int $id, Request $req): JsonResponse
    {
        $key = $this->em->getRepository(ApiKey::class)->find($id);
        if (!$key) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $key->setActive($req->query->getBoolean('active', true));
        $this->em->flush();

        return new JsonResponse(
            ['id' => $id, 'active' => $key->isActive()],
            Response::HTTP_OK
        );
    }
}
