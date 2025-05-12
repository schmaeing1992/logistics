<?php
// src/Entity/ApiKey.php

namespace App\Entity;

use App\Repository\ApiKeyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Uid\Uuid;

/**
 * Ein API-Key gehört immer genau **einem** Partner.
 */
#[ORM\Entity(repositoryClass: ApiKeyRepository::class)]
#[ORM\Table(name: 'api_key')]
class ApiKey
{
    /* ===================== Primärschlüssel ===================== */

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /* 64-stelliger SHA-256-Hash des Klartext-Tokens */
    #[ORM\Column(type: 'string', length: 64, unique: true)]
    #[Ignore]                                // nie serialisieren
    private string $tokenHash;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    /* ===================== Beziehung zu Partner ================ */

    #[ORM\ManyToOne(inversedBy: 'apiKeys')]
    #[ORM\JoinColumn(nullable: false)]
    private Partner $partner;

    /* ===================== Konstruktor ========================= */

    public function __construct(string $rawToken, Partner $partner)
    {
        $this->tokenHash = hash('sha256', $rawToken);
        $this->createdAt = new \DateTimeImmutable();
        $this->partner   = $partner;
    }

    /* ===================== Getter / Setter ===================== */

    public function getId(): ?int                     { return $this->id; }
    public function getTokenHash(): string            { return $this->tokenHash; }

    public function isActive(): bool                  { return $this->isActive; }
    public function setActive(bool $active): static   { $this->isActive = $active; return $this; }

    public function getCreatedAt(): \DateTimeInterface{ return $this->createdAt; }

    public function getPartner(): Partner             { return $this->partner; }
    public function setPartner(Partner $p): static    { $this->partner = $p; return $this; }
}
