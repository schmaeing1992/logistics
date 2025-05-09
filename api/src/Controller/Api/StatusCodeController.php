<?php
// src/Controller/Api/StatusCodeController.php

namespace App\Controller\Api;

use App\Entity\StatusCode;
use App\Repository\StatusCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/status_codes', name: 'api_status_codes_')]
class StatusCodeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface    $em,
        private StatusCodeRepository      $repo,
        private SerializerInterface       $serializer,
        private ValidatorInterface        $validator
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
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

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var StatusCode $code */
        $code = $this->serializer->deserialize(
            $request->getContent(),
            StatusCode::class,
            'json',
            ['groups' => ['status:write']]
        );

        // Validierung
        $errors = $this->validator->validate($code);
        if (count($errors) > 0) {
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

    #[Route('/{code}', name: 'show', methods: ['GET'])]
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
