<?php
// src/Controller/Api/PartnerController.php

namespace App\Controller\Api;

use App\Entity\Partner;
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
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/partners', name: 'api_partners_')]
#[OA\Tag(name: 'Partners')]
class PartnerController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PartnerRepository      $repo,
        private SerializerInterface    $serializer,
        private ValidatorInterface     $validator,
    ) {}

    /* ======================================================================
     * LIST
     * ==================================================================== */
    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        summary: 'Alle Partner auflisten',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Array von Partner-Objekten',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: new Model(type: Partner::class, groups: ['partner:read'])
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth' => []]]
    )]
    public function list(): JsonResponse
    {
        $json = $this->serializer->serialize(
            $this->repo->findAll(),
            'json',
            ['groups' => ['partner:read']]
        );

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /* ======================================================================
     * CREATE
     * ==================================================================== */
    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Neuen Partner anlegen',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: Partner::class, groups: ['partner:write'])
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Partner angelegt',
                content: new OA\JsonContent(
                    ref: new Model(type: Partner::class, groups: ['partner:read'])
                )
            ),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth' => []]]
    )]
    public function create(Request $request): JsonResponse
    {
        /** @var Partner $partner */
        $partner = $this->serializer->deserialize(
            $request->getContent(),
            Partner::class,
            'json',
            ['groups' => ['partner:write']]
        );

        $errors = $this->validator->validate($partner);
        if (\count($errors) > 0) {
            return new JsonResponse(
                ['errors' => (string) $errors],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $this->em->persist($partner);
        $this->em->flush();

        $json = $this->serializer->serialize(
            $partner,
            'json',
            ['groups' => ['partner:read']]
        );

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    /* ======================================================================
     * SHOW
     * ==================================================================== */
    #[Route('/{stationNumber}', name: 'show', methods: ['GET'], requirements: ['stationNumber' => '\d{3}'])]
    #[OA\Get(
        summary: 'Partner per Stationsnummer abrufen',
        parameters: [
            new OA\Parameter(
                name: 'stationNumber',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 100, maximum: 999)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Partner-Objekt',
                content: new OA\JsonContent(
                    ref: new Model(type: Partner::class, groups: ['partner:read'])
                )
            ),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth' => []]]
    )]
    public function show(int $stationNumber): JsonResponse
    {
        $partner = $this->repo->findOneBy(['stationNumber' => $stationNumber]);
        if (!$partner) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(
            $this->serializer->serialize($partner, 'json', ['groups' => ['partner:read']]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    /* ======================================================================
     * UPDATE  (PUT & PATCH)
     * ==================================================================== */
    #[Route('/{stationNumber}', name: 'update', methods: ['PUT', 'PATCH'], requirements: ['stationNumber' => '\d{3}'])]
    #[OA\Put(
        summary: 'Partner vollständig aktualisieren',
        parameters: [
            new OA\Parameter(name: 'stationNumber', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer', minimum: 100, maximum: 999))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: Partner::class, groups: ['partner:write'])
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Aktualisierter Partner',
                content: new OA\JsonContent(
                    ref: new Model(type: Partner::class, groups: ['partner:read'])
                )
            ),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth' => []]]
    )]
    #[OA\Patch(
        summary: 'Partner teilweise aktualisieren',
        parameters: [
            new OA\Parameter(name: 'stationNumber', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer', minimum: 100, maximum: 999))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: Partner::class, groups: ['partner:write'])
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Aktualisierter Partner',
                content: new OA\JsonContent(
                    ref: new Model(type: Partner::class, groups: ['partner:read'])
                )
            ),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth' => []]]
    )]
    public function update(int $stationNumber, Request $request): JsonResponse
    {
        $partner = $this->repo->findOneBy(['stationNumber' => $stationNumber]);
        if (!$partner) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $this->serializer->deserialize(
            $request->getContent(),
            Partner::class,
            'json',
            ['object_to_populate' => $partner, 'groups' => ['partner:write']]
        );

        $errors = $this->validator->validate($partner);
        if (\count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->em->flush();

        return new JsonResponse(
            $this->serializer->serialize($partner, 'json', ['groups' => ['partner:read']]),
            Response::HTTP_OK,
            [],
            true
        );
    }

    /* ======================================================================
     * DELETE
     * ==================================================================== */
    #[Route('/{stationNumber}', name: 'delete', methods: ['DELETE'], requirements: ['stationNumber' => '\d{3}'])]
    #[OA\Delete(
        summary: 'Partner löschen',
        parameters: [
            new OA\Parameter(name: 'stationNumber', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer', minimum: 100, maximum: 999))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Gelöscht'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth' => []]]
    )]
    public function delete(int $stationNumber): JsonResponse
    {
        $partner = $this->repo->findOneBy(['stationNumber' => $stationNumber]);
        if (!$partner) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($partner);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
