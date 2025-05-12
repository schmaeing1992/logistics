<?php
// src/Security/ApiKeyUser.php
namespace App\Security;

use App\Entity\ApiKey;
use App\Entity\Partner;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Sehr leichtgewichtiges User-Objekt für die API-Key-Authentifizierung.
 * Enthält den ApiKey-Datensatz und damit indirekt auch den Partner.
 */
final class ApiKeyUser implements UserInterface
{
    public function __construct(private readonly ApiKey $apiKey) {}

    /* ----------------- Komfort-Shortcut ----------------- */

    /**
     * Der Partner, dem dieser API-Key gehört.
     */
    public function getPartner(): Partner
    {
        return $this->apiKey->getPartner();
    }

    /**
     * Der zugrundeliegende ApiKey-Entity.
     */
    public function getApiKey(): ApiKey
    {
        return $this->apiKey;
    }

    /* ----------------- UserInterface ----------------- */

    /**
     * Eindeutiger Identifier für Symfony (z.B. für Logs).
     */
    public function getUserIdentifier(): string
    {
        return 'apikey_'.$this->apiKey->getId();
    }

    public function getRoles(): array
    {
        return ['ROLE_API'];
    }

    public function getPassword(): ?string
    {
        // Kein Passwort für API-User
        return null;
    }

    public function eraseCredentials(): void
    {
        // keine sensiblen Daten gespeichert
    }
}
