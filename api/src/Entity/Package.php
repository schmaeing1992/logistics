<?php

namespace App\Entity;

use App\Repository\PackageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\Shipment;
use App\Entity\PackageStatus;

#[ORM\Entity(repositoryClass: PackageRepository::class)]
#[ORM\Table(name: 'package')]
#[ORM\HasLifecycleCallbacks]
class Package
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['shipment:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Shipment::class, inversedBy: 'packages')]
    #[ORM\JoinColumn(nullable: false)]
    private Shipment $shipment;

    #[ORM\Column(type: 'bigint')]
    #[Groups(['shipment:read','shipment:write'])]
    private int $packageNumber;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['shipment:read','shipment:write'])]
    private ?string $reference = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\Choice(choices: ['Paket','Europalette','Thermobox','Umschlag','Valoren','Rollen'])]
    #[Groups(['shipment:read','shipment:write'])]
    private string $packagingType;

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive]
    #[Groups(['shipment:read','shipment:write'])]
    private int $lengthCm;

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive]
    #[Groups(['shipment:read','shipment:write'])]
    private int $widthCm;

    #[ORM\Column(type: 'integer')]
    #[Assert\Positive]
    #[Groups(['shipment:read','shipment:write'])]
    private int $heightCm;

    #[ORM\Column(type: 'float')]
    #[Assert\PositiveOrZero]
    #[Groups(['shipment:read','shipment:write'])]
    private float $weightKg;

    #[ORM\Column(type: 'float')]
    #[Groups(['shipment:read'])]
    private float $volumeWeightKg = 0.0;

    #[ORM\Column(type: 'float')]
    #[Groups(['shipment:read'])]
    private float $girthCm = 0.0;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['shipment:read'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['shipment:read'])]
    private \DateTimeInterface $updatedAt;

    /** 
     * Status-Einträge werden **nicht** automatisch serialisiert –
     * die Listen-Ausgabe erfolgt ausschließlich über den PackageStatus-Controller
     */
    #[ORM\OneToMany(mappedBy: 'package', targetEntity: PackageStatus::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $statuses;

    public function __construct()
    {
        $this->id        = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->statuses  = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->recalculate();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->recalculate();
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function recalculate(): void
    {
        $this->volumeWeightKg = ($this->lengthCm * $this->widthCm * $this->heightCm) / 6000;
        $this->girthCm        = (($this->heightCm + $this->widthCm) * 2) + $this->lengthCm;
    }

    // === Getter & Setter ===

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getShipment(): Shipment
    {
        return $this->shipment;
    }

    public function setShipment(Shipment $shipment): self
    {
        $this->shipment = $shipment;
        return $this;
    }

    public function getPackageNumber(): int
    {
        return $this->packageNumber;
    }

    public function setPackageNumber(int $packageNumber): self
    {
        $this->packageNumber = $packageNumber;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function getPackagingType(): string
    {
        return $this->packagingType;
    }

    public function setPackagingType(string $packagingType): self
    {
        $this->packagingType = $packagingType;
        return $this;
    }

    public function getLengthCm(): int
    {
        return $this->lengthCm;
    }

    public function setLengthCm(int $lengthCm): self
    {
        $this->lengthCm = $lengthCm;
        return $this;
    }

    public function getWidthCm(): int
    {
        return $this->widthCm;
    }

    public function setWidthCm(int $widthCm): self
    {
        $this->widthCm = $widthCm;
        return $this;
    }

    public function getHeightCm(): int
    {
        return $this->heightCm;
    }

    public function setHeightCm(int $heightCm): self
    {
        $this->heightCm = $heightCm;
        return $this;
    }

    public function getWeightKg(): float
    {
        return $this->weightKg;
    }

    public function setWeightKg(float $weightKg): self
    {
        $this->weightKg = $weightKg;
        return $this;
    }

    public function getVolumeWeightKg(): float
    {
        return $this->volumeWeightKg;
    }

    public function getGirthCm(): float
    {
        return $this->girthCm;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, PackageStatus>
     */
    public function getStatuses(): Collection
    {
        return $this->statuses;
    }

    public function addStatus(PackageStatus $status): self
    {
        if (!$this->statuses->contains($status)) {
            $this->statuses->add($status);
            $status->setPackage($this);
        }
        return $this;
    }

    public function removeStatus(PackageStatus $status): self
    {
        if ($this->statuses->removeElement($status)) {
            if ($status->getPackage() === $this) {
                $status->setPackage(null);
            }
        }
        return $this;
    }
}
