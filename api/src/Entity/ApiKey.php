<?php
// api/src/Entity/ApiKey.php

namespace App\Entity;

use App\Repository\ApiKeyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiKeyRepository::class)]
class ApiKey
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(type:"string", length:64, unique:true)]
    private string $tokenHash;

    #[ORM\Column(type:"boolean")]
    private bool $isActive = true;

    #[ORM\Column(type:"datetime")]
    private \DateTimeInterface $createdAt;

    public function __construct(string $rawToken)
    {
        $this->tokenHash = hash('sha256', $rawToken);
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getTokenHash(): string { return $this->tokenHash; }
    public function isActive(): bool { return $this->isActive; }
    public function setActive(bool $active): static { $this->isActive = $active; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
