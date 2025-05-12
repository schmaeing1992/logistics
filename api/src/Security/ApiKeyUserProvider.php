<?php
// src/Security/ApiKeyUserProvider.php
namespace App\Security;

use App\Repository\ApiKeyRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class ApiKeyUserProvider implements UserProviderInterface
{
    public function __construct(private readonly ApiKeyRepository $repo) {}

    /**
     * Symfony ≥6: lädt den User anhand des Identifiers (hier SHA-256-Hash).
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $apiKey = $this->repo->findOneBy([
            'tokenHash' => $identifier,
            'isActive'  => true,
        ]);

        if (!$apiKey) {
            throw new UserNotFoundException('API Key not found or inactive.');
        }

        return new ApiKeyUser($apiKey);
    }

    /**
     * Alias für sehr alte Symfony-Versionen (<6).
     */
    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * Stateless API ⇒ kein Refresh.
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        throw new UnsupportedUserException('Stateless authentication – no user refresh.');
    }

    public function supportsClass(string $class): bool
    {
        return $class === ApiKeyUser::class;
    }
}
