<?php
// src/Controller/Api/PostalCodeRangeController.php

namespace App\Controller\Api;

use App\Entity\PostalCodeRange;
use App\Repository\PartnerRepository;
use App\Repository\PostalCodeRangeRepository;
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

#[Route('/api/partners/{stationNumber}/postal_codes', name: 'api_postal_codes_')]
#[OA\Tag(name: "Routing")]
class PostalCodeRangeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface       $em,
        private PartnerRepository            $partnerRepo,
        private PostalCodeRangeRepository    $rangeRepo,
        private SerializerInterface          $serializer,
        private ValidatorInterface           $validator
    ) {}

    /* --------------------------------------------------------------------
     * LIST
     * ------------------------------------------------------------------ */
    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        summary: 'Alle PLZ-Bereiche eines Partners',
        parameters: [
            new OA\Parameter(
                name: 'stationNumber',
                in: 'path',
                required: true,
                description: '3-stellige Stationsnummer des Partners',
                schema: new OA\Schema(type: 'integer', example: 123)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Array von PostalCodeRange-Objekten',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: PostalCodeRange::class, groups: ['partner:read']))
                )
            ),
            new OA\Response(response: 404, description: 'Partner nicht gefunden'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth' => []]]
    )]
    public function list(int $stationNumber): JsonResponse
    {
        $partner = $this->partnerRepo->findOneBy(['stationNumber' => $stationNumber]);
        if (!$partner) {
            return new JsonResponse(['error' => 'Partner not found'], Response::HTTP_NOT_FOUND);
        }

        $ranges = $partner->getPostalCodeRanges()->toArray();
        $json   = $this->serializer->serialize($ranges, 'json', ['groups' => ['partner:read']]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /* --------------------------------------------------------------------
     * CREATE
     * ------------------------------------------------------------------ */
    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Neuen PLZ-Bereich anlegen',
        parameters: [
            new OA\Parameter(
                name: 'stationNumber',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: PostalCodeRange::class, groups: ['partner:write']))
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'PostalCodeRange erstellt',
                content: new OA\JsonContent(ref: new Model(type: PostalCodeRange::class, groups: ['partner:read']))
            ),
            new OA\Response(response: 404, description: 'Partner nicht gefunden'),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth' => []]]
    )]
    public function create(int $stationNumber, Request $req): JsonResponse
    {
        $partner = $this->partnerRepo->findOneBy(['stationNumber' => $stationNumber]);
        if (!$partner) {
            return new JsonResponse(['error' => 'Partner not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var PostalCodeRange $range */
        $range = $this->serializer->deserialize(
            $req->getContent(),
            PostalCodeRange::class,
            'json',
            ['groups' => ['partner:write']]
        );
        $range->setPartner($partner);

        $errors = $this->validator->validate($range);
        if (count($errors) > 0) {
            $err = [];
            foreach ($errors as $e) {
                $err[$e->getPropertyPath()] = $e->getMessage();
            }
            return new JsonResponse(['errors' => $err], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->em->persist($range);
        $this->em->flush();

        $json = $this->serializer->serialize($range, 'json', ['groups' => ['partner:read']]);
        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    /* --------------------------------------------------------------------
     * SHOW
     * ------------------------------------------------------------------ */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        summary: 'Einzelnen PLZ-Bereich abrufen',
        parameters: [
            new OA\Parameter(name: 'stationNumber', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id',            in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'PostalCodeRange',
                content: new OA\JsonContent(ref: new Model(type: PostalCodeRange::class, groups: ['partner:read']))
            ),
            new OA\Response(response: 404, description: 'Nicht gefunden'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth' => []]]
    )]
    public function show(int $stationNumber, string $id): JsonResponse
    {
        $range = $this->rangeRepo->find($id);
        if (!$range || $range->getPartner()->getStationNumber() !== $stationNumber) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($range, 'json', ['groups' => ['partner:read']]);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /* --------------------------------------------------------------------
     * UPDATE
     * ------------------------------------------------------------------ */
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    #[OA\Patch(
        summary: 'PLZ-Bereich ändern',
        parameters: [
            new OA\Parameter(name: 'stationNumber', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id',            in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: PostalCodeRange::class, groups: ['partner:write']))
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Aktualisiert',
                content: new OA\JsonContent(ref: new Model(type: PostalCodeRange::class, groups: ['partner:read']))
            ),
            new OA\Response(response: 404, description: 'Nicht gefunden'),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth' => []]]
    )]
    public function update(int $stationNumber, string $id, Request $req): JsonResponse
    {
        $range = $this->rangeRepo->find($id);
        if (!$range || $range->getPartner()->getStationNumber() !== $stationNumber) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $this->serializer->deserialize(
            $req->getContent(),
            PostalCodeRange::class,
            'json',
            ['object_to_populate' => $range, 'groups' => ['partner:write']]
        );

        $errors = $this->validator->validate($range);
        if (count($errors) > 0) {
            $err = [];
            foreach ($errors as $e) {
                $err[$e->getPropertyPath()] = $e->getMessage();
            }
            return new JsonResponse(['errors' => $err], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->em->flush();

        $json = $this->serializer->serialize($range, 'json', ['groups' => ['partner:read']]);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /* --------------------------------------------------------------------
     * DELETE
     * ------------------------------------------------------------------ */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'PLZ-Bereich löschen',
        parameters: [
            new OA\Parameter(name: 'stationNumber', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id',            in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Gelöscht'),
            new OA\Response(response: 404, description: 'Nicht gefunden'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [['ApiKeyAuth' => []]]
    )]
    public function delete(int $stationNumber, string $id): JsonResponse
    {
        $range = $this->rangeRepo->find($id);
        if (!$range || $range->getPartner()->getStationNumber() !== $stationNumber) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($range);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
