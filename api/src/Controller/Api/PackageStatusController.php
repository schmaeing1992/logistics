<?php
// src/Controller/Api/PackageStatusController.php

namespace App\Controller\Api;

use App\Entity\Package;
use App\Entity\PackageStatus;
use App\Repository\PackageRepository;
use App\Repository\StatusCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/packages', name: 'api_package_statuses_')]
class PackageStatusController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PackageRepository       $packageRepo,
        private StatusCodeRepository    $statusCodeRepo,
        private SerializerInterface     $serializer,
        private ValidatorInterface      $validator
    ) {}

    // -------------------------
    // 1) Routen mit UUID
    // -------------------------

    #[Route('/{packageId}/statuses', name: 'list_by_id', methods: ['GET'])]
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
    public function createById(string $packageId, Request $request): JsonResponse
    {
        $package = $this->packageRepo->find($packageId);
        if (!$package) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        return $this->handleCreate($package, $request);
    }

    // -------------------------
    // 2) Routen mit Business-Nummer
    // -------------------------

    #[Route('/number/{packageNumber}/statuses', name: 'list_by_number', methods: ['GET'])]
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
    public function createByNumber(int $packageNumber, Request $request): JsonResponse
    {
        $package = $this->packageRepo->findOneByPackageNumber($packageNumber);
        if (!$package) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        return $this->handleCreate($package, $request);
    }

    // -------------------------
    // Gemeinsame Hilfsmethode
    // -------------------------

    private function handleCreate(Package $package, Request $request): JsonResponse
    {
        // Rohdaten auslesen
        $data = json_decode($request->getContent(), true);
        if (!isset($data['statusCode'])) {
            return new JsonResponse(['error'=>'statusCode fehlt'], Response::HTTP_BAD_REQUEST);
        }

        // Bestehenden StatusCode anhand des codes holen
        $statusCode = $this->statusCodeRepo->findOneBy(['code' => (string)$data['statusCode']]);
        if (!$statusCode) {
            return new JsonResponse(['error'=>'Ungültiger statusCode'], Response::HTTP_BAD_REQUEST);
        }

        // Neues PackageStatus-Objekt befüllen
        $status = new PackageStatus();
        $status->setPackage($package);
        $status->setStatusCode($statusCode);
        if (isset($data['note'])) {
            $status->setNote((string)$data['note']);
        }

        // Validierung
        $errors = $this->validator->validate($status);
        if (count($errors) > 0) {
            $errData = [];
            foreach ($errors as $e) {
                $errData[$e->getPropertyPath()] = $e->getMessage();
            }
            return new JsonResponse(['errors' => $errData], Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($status);
        $this->em->flush();

        $json = $this->serializer->serialize(
            $status,
            'json',
            ['groups' => ['status:read']]
        );

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }
}
