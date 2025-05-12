<?php
// src/Entity/PostalCodeRange.php

namespace App\Entity;

use App\Repository\PostalCodeRangeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Uuid;

/**
 * Repräsentiert einen durchgängigen Postleitzahlen-Bereich, der
 * einem Partner (Spedition/Depot) für Abholung oder Zustellung
 * zugeordnet ist.
 */
#[ORM\Entity(repositoryClass: PostalCodeRangeRepository::class)]
#[ORM\Table(name: 'postal_code_range')]
class PostalCodeRange
{
    /* =============================================================
     * Primärschlüssel (UUID)
     * =========================================================== */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['range:read', 'partner:read'])]
    private Uuid $id;

    /* =============================================================
     * Bereichs-Definition
     * =========================================================== */
    #[ORM\Column(length: 2)]
    #[Assert\Length(exactly: 2)]
    #[Groups(['range:read','range:write','partner:read','partner:write'])]
    private string $country = 'DE';

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d{3,10}$/')]
    #[Groups(['range:read','range:write','partner:read','partner:write'])]
    private string $zipFrom;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d{3,10}$/')]
    #[Groups(['range:read','range:write','partner:read','partner:write'])]
    private string $zipTo;

    /** `pickup`  = Abholung beim Versender  
     *  `delivery`= Zustellung beim Empfänger */
    #[ORM\Column(length: 10)]
    #[Assert\Choice(choices: ['pickup','delivery'])]
    #[Groups(['range:read','range:write','partner:read','partner:write'])]
    private string $type = 'delivery';

    /** kleinere Zahl ⇒ höhere Priorität bei Überschneidungen */
    #[ORM\Column(type: 'smallint', name: 'sort_order')]
    #[Assert\PositiveOrZero]
    #[Groups(['range:read','range:write','partner:read','partner:write'])]
    private int $sortOrder = 0;

    /* =============================================================
     * Beziehung zum Partner
     * =========================================================== */
    #[ORM\ManyToOne(inversedBy: 'postalCodeRanges')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['range:read'])]
    private Partner $partner;

    /* =============================================================
     * Konstruktor
     * =========================================================== */
    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    /* =============================================================
     * Getter / Setter
     * =========================================================== */
    public function getId(): Uuid                       { return $this->id; }

    public function getCountry(): string               { return $this->country; }
    public function setCountry(string $c): self        { $this->country = strtoupper($c); return $this; }

    public function getZipFrom(): string               { return $this->zipFrom; }
    public function setZipFrom(string $z): self        { $this->zipFrom = $z; return $this; }

    public function getZipTo(): string                 { return $this->zipTo; }
    public function setZipTo(string $z): self          { $this->zipTo = $z; return $this; }

    public function getType(): string                  { return $this->type; }
    public function setType(string $t): self           { $this->type = $t; return $this; }

    public function getSortOrder(): int                { return $this->sortOrder; }
    public function setSortOrder(int $o): self         { $this->sortOrder = $o; return $this; }

    public function getPartner(): Partner              { return $this->partner; }
    public function setPartner(Partner $p): self       { $this->partner = $p; return $this; }
}
