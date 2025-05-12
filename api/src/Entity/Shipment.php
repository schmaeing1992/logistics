<?php

namespace App\Entity;

use App\Repository\ShipmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ShipmentRepository::class)]
#[ORM\Table(name: 'shipment')]
#[ORM\HasLifecycleCallbacks]
class Shipment
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['shipment:read'])]
    private AbstractUid $id;

    #[ORM\Column(type: 'bigint', unique: true)]
    #[Assert\NotNull]
    #[Assert\Positive]
    #[Groups(['shipment:read'])]
    private int $trackingNumber;

    /* ================================ Absender =============================== */

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Groups(['shipment:read','shipment:write'])]
    private string $senderName1;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['shipment:read','shipment:write'])]
    private ?string $senderName2 = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Groups(['shipment:read','shipment:write'])]
    private string $senderStreet;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank]
    #[Groups(['shipment:read','shipment:write'])]
    private string $senderPostalCode;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Groups(['shipment:read','shipment:write'])]
    private string $senderCity;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Groups(['shipment:read','shipment:write'])]
    private string $senderCountry;

    #[ORM\Column(type: 'string', length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['shipment:read','shipment:write'])]
    private string $senderEmail;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Groups(['shipment:read','shipment:write'])]
    private string $senderPhone;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['shipment:read','shipment:write'])]
    private ?string $pickupNote = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull]
    #[Groups(['shipment:read','shipment:write'])]
    private \DateTimeInterface $pickupDate;

    #[ORM\Column(type: 'time')]
    #[Assert\NotNull]
    #[Groups(['shipment:read','shipment:write'])]
    private \DateTimeInterface $pickupTimeFrom;

    #[ORM\Column(type: 'time')]
    #[Assert\NotNull]
    #[Groups(['shipment:read','shipment:write'])]
    private \DateTimeInterface $pickupTimeTo;

    #[ORM\Column(type: 'float')]
    #[Groups(['shipment:read','shipment:write'])]
    private float $pickupExtraFee = 0.0;

    /* =============================== Empfänger ============================== */

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Groups(['shipment:read','shipment:write'])]
    private string $recipientName1;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['shipment:read','shipment:write'])]
    private ?string $recipientName2 = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Groups(['shipment:read','shipment:write'])]
    private string $recipientStreet;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank]
    #[Groups(['shipment:read','shipment:write'])]
    private string $recipientPostalCode;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Groups(['shipment:read','shipment:write'])]
    private string $recipientCity;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Groups(['shipment:read','shipment:write'])]
    private string $recipientCountry;

    #[ORM\Column(type: 'string', length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['shipment:read','shipment:write'])]
    private string $recipientEmail;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Groups(['shipment:read','shipment:write'])]
    private string $recipientPhone;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['shipment:read','shipment:write'])]
    private ?string $deliveryNote = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull]
    #[Groups(['shipment:read','shipment:write'])]
    private \DateTimeInterface $deliveryDate;

    #[ORM\Column(type: 'time')]
    #[Assert\NotNull]
    #[Groups(['shipment:read','shipment:write'])]
    private \DateTimeInterface $deliveryTimeFrom;

    #[ORM\Column(type: 'time')]
    #[Assert\NotNull]
    #[Groups(['shipment:read','shipment:write'])]
    private \DateTimeInterface $deliveryTimeTo;

    #[ORM\Column(type: 'float')]
    #[Groups(['shipment:read','shipment:write'])]
    private float $deliveryExtraFee = 0.0;

    /* =======================================================================
     * Partner-Bezüge
     * ==================================================================== */

    #[ORM\ManyToOne(targetEntity: Partner::class)]
    #[ORM\JoinColumn(name: "booking_partner_id", referencedColumnName: "id", nullable: true)]
    #[Groups(['shipment:read','shipment:write'])]
    private ?Partner $bookingPartner = null;

    #[ORM\ManyToOne(targetEntity: Partner::class)]
    #[ORM\JoinColumn(name: "pickup_partner_id", referencedColumnName: "id", nullable: true)]
    #[Groups(['shipment:read'])]
    private ?Partner $pickupPartner = null;

    #[ORM\ManyToOne(targetEntity: Partner::class)]
    #[ORM\JoinColumn(name: "delivery_partner_id", referencedColumnName: "id", nullable: true)]
    #[Groups(['shipment:read'])]
    private ?Partner $deliveryPartner = null;

    /* ============================== Globale Felder ========================= */

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['shipment:read','shipment:write'])]
    private ?string $customerReference = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['shipment:read','shipment:write'])]
    private ?string $internalDocumentNumber = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['shipment:read','shipment:write'])]
    private ?string $internalNote = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['shipment:read','shipment:write'])]
    private ?string $customerNumber = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(choices: ['Standard', 'Direktfahrt'])]
    #[Groups(['shipment:read','shipment:write'])]
    private string $orderType = 'Standard';

    #[ORM\Column(type: 'float')]
    #[Groups(['shipment:read','shipment:write'])]
    private float $weightTotal = 0.0;

    #[ORM\Column(type: 'float')]
    #[Groups(['shipment:read','shipment:write'])]
    private float $volumeWeightTotal = 0.0;

    #[ORM\Column(type: 'float')]
    #[Groups(['shipment:read','shipment:write'])]
    private float $girthMax = 0.0;

    #[ORM\Column(type: 'float')]
    #[Groups(['shipment:read','shipment:write'])]
    private float $goodsValue = 0.0;

    #[ORM\Column(type: 'float')]
    #[Groups(['shipment:read','shipment:write'])]
    private float $insuranceValue = 0.0;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['shipment:read'])]
    private ?string $labelBase64 = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['shipment:read'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['shipment:read'])]
    private ?\DateTimeInterface $cancelledAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['shipment:read'])]
    private \DateTimeInterface $updatedAt;

    #[ORM\OneToMany(mappedBy: 'shipment', targetEntity: Package::class, cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['shipment:read','shipment:write'])]
    private Collection $packages;

    public function __construct()
    {
        $this->id        = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->packages  = new ArrayCollection();
    }

    #[ORM\PreFlush]
    public function onPreFlush(PreFlushEventArgs $args): void
    {
        $this->recalculateAggregates();
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function recalculateAggregates(): void
    {
        $totalWeight    = 0.0;
        $totalVolWeight = 0.0;
        $maxGirth       = 0.0;

        foreach ($this->packages as $pkg) {
            $totalWeight    += $pkg->getWeightKg();
            $totalVolWeight += $pkg->getVolumeWeightKg();
            $maxGirth       = max($maxGirth, $pkg->getGirthCm());
        }

        $this->weightTotal       = $totalWeight;
        $this->volumeWeightTotal = $totalVolWeight;
        $this->girthMax          = $maxGirth;
    }

    /* ========================== Getter & Setter ============================= */

    public function getId(): AbstractUid
    {
        return $this->id;
    }

    public function getTrackingNumber(): int
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(int $trackingNumber): self
    {
        $this->trackingNumber = $trackingNumber;
        return $this;
    }

    public function getSenderName1(): string
    {
        return $this->senderName1;
    }

    public function setSenderName1(string $senderName1): self
    {
        $this->senderName1 = $senderName1;
        return $this;
    }

    public function getSenderName2(): ?string
    {
        return $this->senderName2;
    }

    public function setSenderName2(?string $senderName2): self
    {
        $this->senderName2 = $senderName2;
        return $this;
    }

    public function getSenderStreet(): string
    {
        return $this->senderStreet;
    }

    public function setSenderStreet(string $senderStreet): self
    {
        $this->senderStreet = $senderStreet;
        return $this;
    }

    public function getSenderPostalCode(): string
    {
        return $this->senderPostalCode;
    }

    public function setSenderPostalCode(string $senderPostalCode): self
    {
        $this->senderPostalCode = $senderPostalCode;
        return $this;
    }

    public function getSenderCity(): string
    {
        return $this->senderCity;
    }

    public function setSenderCity(string $senderCity): self
    {
        $this->senderCity = $senderCity;
        return $this;
    }

    public function getSenderCountry(): string
    {
        return $this->senderCountry;
    }

    public function setSenderCountry(string $senderCountry): self
    {
        $this->senderCountry = $senderCountry;
        return $this;
    }

    public function getSenderEmail(): string
    {
        return $this->senderEmail;
    }

    public function setSenderEmail(string $senderEmail): self
    {
        $this->senderEmail = $senderEmail;
        return $this;
    }

    public function getSenderPhone(): string
    {
        return $this->senderPhone;
    }

    public function setSenderPhone(string $senderPhone): self
    {
        $this->senderPhone = $senderPhone;
        return $this;
    }

    public function getPickupNote(): ?string
    {
        return $this->pickupNote;
    }

    public function setPickupNote(?string $pickupNote): self
    {
        $this->pickupNote = $pickupNote;
        return $this;
    }

    public function getPickupDate(): \DateTimeInterface
    {
        return $this->pickupDate;
    }

    public function setPickupDate(\DateTimeInterface $pickupDate): self
    {
        $this->pickupDate = $pickupDate;
        return $this;
    }

    public function getPickupTimeFrom(): \DateTimeInterface
    {
        return $this->pickupTimeFrom;
    }

    public function setPickupTimeFrom(\DateTimeInterface $pickupTimeFrom): self
    {
        $this->pickupTimeFrom = $pickupTimeFrom;
        return $this;
    }

    public function getPickupTimeTo(): \DateTimeInterface
    {
        return $this->pickupTimeTo;
    }

    public function setPickupTimeTo(\DateTimeInterface $pickupTimeTo): self
    {
        $this->pickupTimeTo = $pickupTimeTo;
        return $this;
    }

    public function getPickupExtraFee(): float
    {
        return $this->pickupExtraFee;
    }

    public function setPickupExtraFee(float $pickupExtraFee): self
    {
        $this->pickupExtraFee = $pickupExtraFee;
        return $this;
    }

    public function getRecipientName1(): string
    {
        return $this->recipientName1;
    }

    public function setRecipientName1(string $recipientName1): self
    {
        $this->recipientName1 = $recipientName1;
        return $this;
    }

    public function getRecipientName2(): ?string
    {
        return $this->recipientName2;
    }

    public function setRecipientName2(?string $recipientName2): self
    {
        $this->recipientName2 = $recipientName2;
        return $this;
    }

    public function getRecipientStreet(): string
    {
        return $this->recipientStreet;
    }

    public function setRecipientStreet(string $recipientStreet): self
    {
        $this->recipientStreet = $recipientStreet;
        return $this;
    }

    public function getRecipientPostalCode(): string
    {
        return $this->recipientPostalCode;
    }

    public function setRecipientPostalCode(string $recipientPostalCode): self
    {
        $this->recipientPostalCode = $recipientPostalCode;
        return $this;
    }

    public function getRecipientCity(): string
    {
        return $this->recipientCity;
    }

    public function setRecipientCity(string $recipientCity): self
    {
        $this->recipientCity = $recipientCity;
        return $this;
    }

    public function getRecipientCountry(): string
    {
        return $this->recipientCountry;
    }

    public function setRecipientCountry(string $recipientCountry): self
    {
        $this->recipientCountry = $recipientCountry;
        return $this;
    }

    public function getRecipientEmail(): string
    {
        return $this->recipientEmail;
    }

    public function setRecipientEmail(string $recipientEmail): self
    {
        $this->recipientEmail = $recipientEmail;
        return $this;
    }

    public function getRecipientPhone(): string
    {
        return $this->recipientPhone;
    }

    public function setRecipientPhone(string $recipientPhone): self
    {
        $this->recipientPhone = $recipientPhone;
        return $this;
    }

    public function getDeliveryNote(): ?string
    {
        return $this->deliveryNote;
    }

    public function setDeliveryNote(?string $deliveryNote): self
    {
        $this->deliveryNote = $deliveryNote;
        return $this;
    }

    public function getDeliveryDate(): \DateTimeInterface
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(\DateTimeInterface $deliveryDate): self
    {
        $this->deliveryDate = $deliveryDate;
        return $this;
    }

    public function getDeliveryTimeFrom(): \DateTimeInterface
    {
        return $this->deliveryTimeFrom;
    }

    public function setDeliveryTimeFrom(\DateTimeInterface $deliveryTimeFrom): self
    {
        $this->deliveryTimeFrom = $deliveryTimeFrom;
        return $this;
    }

    public function getDeliveryTimeTo(): \DateTimeInterface
    {
        return $this->deliveryTimeTo;
    }

    public function setDeliveryTimeTo(\DateTimeInterface $deliveryTimeTo): self
    {
        $this->deliveryTimeTo = $deliveryTimeTo;
        return $this;
    }

    public function getDeliveryExtraFee(): float
    {
        return $this->deliveryExtraFee;
    }

    public function setDeliveryExtraFee(float $deliveryExtraFee): self
    {
        $this->deliveryExtraFee = $deliveryExtraFee;
        return $this;
    }

    public function getCustomerReference(): ?string
    {
        return $this->customerReference;
    }

    public function setCustomerReference(?string $customerReference): self
    {  
        $this->customerReference = $customerReference;
        return $this;
    }

    public function getInternalDocumentNumber(): ?string
    {
        return $this->internalDocumentNumber;
    }

    public function setInternalDocumentNumber(?string $internalDocumentNumber): self
    {
        $this->internalDocumentNumber = $internalDocumentNumber;
        return $this;
    }

    public function getInternalNote(): ?string
    {
        return $this->internalNote;
    }

    public function setInternalNote(?string $internalNote): self
    {
        $this->internalNote = $internalNote;
        return $this;
    }

    public function getCustomerNumber(): ?string
    {
        return $this->customerNumber;
    }

    public function setCustomerNumber(?string $customerNumber): self
    {
        $this->customerNumber = $customerNumber;
        return $this;
    }

    public function getOrderType(): string
    {
        return $this->orderType;
    }

    public function setOrderType(string $orderType): self
    {
        $this->orderType = $orderType;
        return $this;
    }

    public function getWeightTotal(): float
    {
        return $this->weightTotal;
    }

    public function setWeightTotal(float $weightTotal): self
    {
        $this->weightTotal = $weightTotal;
        return $this;
    }

    public function getVolumeWeightTotal(): float
    {
        return $this->volumeWeightTotal;
    }

    public function setVolumeWeightTotal(float $volumeWeightTotal): self
    {
        $this->volumeWeightTotal = $volumeWeightTotal;
        return $this;
    }

    public function getGirthMax(): float
    {
        return $this->girthMax;
    }

    public function setGirthMax(float $girthMax): self
    {
        $this->girthMax = $girthMax;
        return $this;
    }

    public function getGoodsValue(): float
    {
        return $this->goodsValue;
    }

    public function setGoodsValue(float $goodsValue): self
    {
        $this->goodsValue = $goodsValue;
        return $this;
    }

    public function getInsuranceValue(): float
    {
        return $this->insuranceValue;
    }

    public function setInsuranceValue(float $insuranceValue): self
    {
        $this->insuranceValue = $insuranceValue;
        return $this;
    }

    public function getLabelBase64(): ?string
    {
        return $this->labelBase64;
    }

    public function setLabelBase64(?string $labelBase64): self
    {
        $this->labelBase64 = $labelBase64;
        return $this;
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

    public function getCancelledAt(): ?\DateTimeInterface
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeInterface $cancelledAt): self
    {
        $this->cancelledAt = $cancelledAt;
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

     /* ---------------- Partner-Felder ---------------- */
     public function getBookingPartner(): ?Partner
     {
         return $this->bookingPartner;
     }
 
     public function setBookingPartner(?Partner $p): self
     {
         $this->bookingPartner = $p;
         return $this;
     }
 
     public function getPickupPartner(): ?Partner
     {
         return $this->pickupPartner;
     }
 
     public function setPickupPartner(?Partner $p): self
     {
         $this->pickupPartner = $p;
         return $this;
     }
 
     public function getDeliveryPartner(): ?Partner
     {
         return $this->deliveryPartner;
     }
 
     public function setDeliveryPartner(?Partner $p): self
     {
         $this->deliveryPartner = $p;
         return $this;
     }

    /**
     * @return Collection<int, Package>
     */
    public function getPackages(): Collection
    {
        return $this->packages;
    }

    public function addPackage(Package $package): self
    {
        if (!$this->packages->contains($package)) {
            $this->packages->add($package);
            $package->setShipment($this);
        }
        return $this;
    }

    public function removePackage(Package $package): self
    {
        if ($this->packages->removeElement($package)) {
            if ($package->getShipment() === $this) {
                $package->setShipment(null);
            }
        }
        return $this;
    }
}
