<?php
// src/Controller/Api/StatusCodeController.php

namespace App\Controller\Api;

use App\Entity\StatusCode;
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

#[Route('/api/status_codes', name: 'api_status_codes_')]
#[OA\Tag(name: 'Status-Codes')]
final class StatusCodeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private StatusCodeRepository   $repo,
        private SerializerInterface    $serializer,
        private ValidatorInterface     $validator
    ) {}

    /* ---------------------------------------------------------------------
     * LIST
     * ------------------------------------------------------------------ */

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        summary: 'Alle verfügbaren Status-Codes abrufen',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste der Codes',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: StatusCode::class, groups: ['status:read']))
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [ ['ApiKeyAuth'=>[]] ]
    )]
    public function list(): JsonResponse
    {
        $codes = $this->repo->findAll();

        $json  = $this->serializer->serialize(
            $codes,
            'json',
            ['groups' => ['status:read']]
        );

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /* ---------------------------------------------------------------------
     * CREATE
     * ------------------------------------------------------------------ */

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Neuen Status-Code anlegen',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: StatusCode::class, groups: ['status:write'])
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Status-Code erstellt',
                content: new OA\JsonContent(ref: new Model(type: StatusCode::class, groups: ['status:read']))
            ),
            new OA\Response(response: 400, description: 'Validation failed'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [ ['ApiKeyAuth'=>[]] ]
    )]
    public function create(Request $request): JsonResponse
    {
        /** @var StatusCode $code */
        $code = $this->serializer->deserialize(
            $request->getContent(),
            StatusCode::class,
            'json',
            ['groups' => ['status:write']]
        );

        $errors = $this->validator->validate($code);
        if (\count($errors) > 0) {
            $errorData = [];
            foreach ($errors as $err) {
                $errorData[$err->getPropertyPath()] = $err->getMessage();
            }
            return new JsonResponse(['errors' => $errorData], Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($code);
        $this->em->flush();

        $json = $this->serializer->serialize(
            $code,
            'json',
            ['groups' => ['status:read']]
        );

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    /* ---------------------------------------------------------------------
     * SHOW
     * ------------------------------------------------------------------ */

    #[Route('/{code}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        summary: 'Einen Status-Code abrufen',
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Gefundener Code',
                content: new OA\JsonContent(ref: new Model(type: StatusCode::class, groups: ['status:read']))
            ),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ],
        security: [ ['ApiKeyAuth'=>[]] ]
    )]
    public function show(string $code): JsonResponse
    {
        $statusCode = $this->repo->findOneBy(['code' => $code]);
        if (!$statusCode) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize(
            $statusCode,
            'json',
            ['groups' => ['status:read']]
        );

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
