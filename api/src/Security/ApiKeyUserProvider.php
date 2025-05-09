<?php
// src/Security/ApiKeyUserProvider.php

namespace App\Security;

use App\Repository\ApiKeyRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\InMemoryUser;

class ApiKeyUserProvider implements UserProviderInterface
{
    public function __construct(private ApiKeyRepository $repo) {}

    /**
     * Für Symfony ≥6 erforderlich: lädt den User anhand des Identifiers.
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $entity = $this->repo->findOneBy([
            'tokenHash' => $identifier,
            'isActive'  => true,
        ]);

        if (!$entity) {
            throw new \Exception('API Key not found');
        }

        return new InMemoryUser('api_client', null, ['ROLE_API']);
    }

    /**
     * Alias für ältere Symfony-Versionen.
     */
    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * Muss das Interface erfüllen und den gleichen Rückgabetyp haben.
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        // Stateless API, kein Refresh
        throw new UnsupportedUserException('API users cannot be refreshed.');
    }

    public function supportsClass(string $class): bool
    {
        return InMemoryUser::class === $class;
    }
}
