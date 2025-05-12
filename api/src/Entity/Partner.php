<?php

namespace App\Entity;

use App\Repository\PartnerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PartnerRepository::class)]
#[ORM\Table(name: 'partner')]
class Partner
{
    /* -----------------------------------------------------------
     * Primärschlüssel + Stationsnummer
     * --------------------------------------------------------- */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['partner:read', 'shipment:read'])]
    private Uuid $id;

    /** dreistellige interne Nr. (100-999) */
    #[ORM\Column(type: 'integer', unique: true)]
    #[Assert\Range(min: 100, max: 999)]
    #[Groups(['partner:read', 'partner:write', 'shipment:read'])]
    private int $stationNumber;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Groups(['partner:read', 'partner:write', 'shipment:read'])]
    private string $name;

    /* -----------------------------------------------------------
     * Firmen-Sitz
     * --------------------------------------------------------- */
    #[ORM\Column(length: 255)] #[Assert\NotBlank] #[Groups(['partner:read','partner:write'])]
    private string $street;

    #[ORM\Column(length: 20)]  #[Assert\NotBlank] #[Groups(['partner:read','partner:write'])]
    private string $postalCode;

    #[ORM\Column(length: 100)] #[Assert\NotBlank] #[Groups(['partner:read','partner:write'])]
    private string $city;

    #[ORM\Column(length: 10)] #[Assert\NotBlank] #[Groups(['partner:read','partner:write'])]
    private string $houseNumber;

    #[ORM\Column(length: 2)]  #[Assert\Length(exactly: 2)] #[Groups(['partner:read','partner:write'])]
    private string $country = 'DE';

    /* -----------------------------------------------------------
     * Rechnungsadresse
     * --------------------------------------------------------- */
    #[ORM\Column(length: 255, nullable: true)] #[Groups(['partner:read','partner:write'])]
    private ?string $invStreet = null;

    #[ORM\Column(length: 20, nullable: true)]  #[Groups(['partner:read','partner:write'])]
    private ?string $invPostalCode = null;

    #[ORM\Column(length: 100, nullable: true)] #[Groups(['partner:read','partner:write'])]
    private ?string $invCity = null;

    #[ORM\Column(length: 10, nullable: true)]  #[Groups(['partner:read','partner:write'])]
    private ?string $invHouseNumber = null;

    #[ORM\Column(length: 2, nullable: true)]   #[Groups(['partner:read','partner:write'])]
    private ?string $invCountry = null;

    /* -----------------------------------------------------------
     * Ansprechpartner
     * --------------------------------------------------------- */
    #[ORM\Column(length: 100, nullable: true)] #[Groups(['partner:read','partner:write'])]
    private ?string $contactAccountingName = null;

    #[ORM\Column(length: 50, nullable: true)]  #[Groups(['partner:read','partner:write'])]
    private ?string $contactAccountingPhone = null;

    #[ORM\Column(length: 180, nullable: true)] #[Assert\Email] #[Groups(['partner:read','partner:write'])]
    private ?string $contactAccountingEmail = null;

    #[ORM\Column(length: 100, nullable: true)] #[Groups(['partner:read','partner:write'])]
    private ?string $contactDispatchName = null;

    #[ORM\Column(length: 50, nullable: true)]  #[Groups(['partner:read','partner:write'])]
    private ?string $contactDispatchPhone = null;

    #[ORM\Column(length: 180, nullable: true)] #[Assert\Email] #[Groups(['partner:read','partner:write'])]
    private ?string $contactDispatchEmail = null;

    /* -----------------------------------------------------------
     * Kontakte allgemein
     * --------------------------------------------------------- */
    #[ORM\Column(length: 50)]                  #[Groups(['partner:read','partner:write'])]
    private string $phone;

    #[ORM\Column(length: 50, nullable: true)] #[Groups(['partner:read','partner:write'])]
    private ?string $emergencyPhone = null;

    #[ORM\Column(length: 180)] #[Assert\Email] #[Groups(['partner:read','partner:write'])]
    private string $email;

    /* -----------------------------------------------------------
     * Anlieferadresse
     * --------------------------------------------------------- */
    #[ORM\Column(length: 255)] #[Groups(['partner:read','partner:write'])]
    private string $deliveryStreet = '';

    #[ORM\Column(length: 10)]  #[Groups(['partner:read','partner:write'])]
    private string $deliveryHouseNumber = '';

    #[ORM\Column(length: 20)]  #[Groups(['partner:read','partner:write'])]
    private string $deliveryPostalCode = '';

    #[ORM\Column(length: 100)] #[Groups(['partner:read','partner:write'])]
    private string $deliveryCity = '';

    #[ORM\Column(length: 2)]   #[Groups(['partner:read','partner:write'])]
    private string $deliveryCountry = 'DE';

    #[ORM\Column(length: 50, nullable: true)]  #[Groups(['partner:read','partner:write'])]
    private ?string $deliveryPhone = null;

    #[ORM\Column(length: 180, nullable: true)] #[Assert\Email] #[Groups(['partner:read','partner:write'])]
    private ?string $deliveryEmail = null;

    /* -----------------------------------------------------------
     * Öffnungszeiten & Ausstattung
     * --------------------------------------------------------- */
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['partner:read','partner:write'])]
    private ?array $openingHoursWarehouse = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['partner:read','partner:write'])]
    private ?array $openingHoursOffice = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['partner:read','partner:write'])]
    private bool $hasForklift = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['partner:read','partner:write'])]
    private bool $isColoader = false;

    /* -----------------------------------------------------------
     * Beziehungen
     * --------------------------------------------------------- */
    #[ORM\OneToMany(mappedBy: 'partner', targetEntity: PostalCodeRange::class,
        cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['partner:read','partner:write'])]
    private Collection $postalCodeRanges;

    #[ORM\OneToMany(mappedBy: 'partner', targetEntity: LineSchedule::class,
        cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['partner:read','partner:write'])]
    private Collection $lineSchedules;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->postalCodeRanges = new ArrayCollection();
        $this->lineSchedules    = new ArrayCollection();
    }

    /* =========  Getter / Setter (vollständig)  ========= */
    public function getId(): Uuid                              { return $this->id; }
    public function getStationNumber(): int                    { return $this->stationNumber; }
    public function setStationNumber(int $n): self             { $this->stationNumber=$n; return $this; }
    public function getName(): string                          { return $this->name; }
    public function setName(string $n): self                   { $this->name=$n; return $this; }

    public function getStreet(): string                        { return $this->street; }
    public function setStreet(string $s): self                 { $this->street=$s; return $this; }
    public function getHouseNumber(): string                   { return $this->houseNumber; }
    public function setHouseNumber(string $n): self            { $this->houseNumber=$n; return $this; }
    public function getPostalCode(): string                    { return $this->postalCode; }
    public function setPostalCode(string $p): self             { $this->postalCode=$p; return $this; }
    public function getCity(): string                          { return $this->city; }
    public function setCity(string $c): self                   { $this->city=$c; return $this; }
    public function getCountry(): string                       { return $this->country; }
    public function setCountry(string $c): self                { $this->country=$c; return $this; }

    public function getInvStreet(): ?string                    { return $this->invStreet; }
    public function setInvStreet(?string $s): self             { $this->invStreet=$s; return $this; }
    public function getInvHouseNumber(): ?string               { return $this->invHouseNumber; }
    public function setInvHouseNumber(?string $n): self        { $this->invHouseNumber=$n; return $this; }
    public function getInvPostalCode(): ?string                { return $this->invPostalCode; }
    public function setInvPostalCode(?string $p): self         { $this->invPostalCode=$p; return $this; }
    public function getInvCity(): ?string                      { return $this->invCity; }
    public function setInvCity(?string $c): self               { $this->invCity=$c; return $this; }
    public function getInvCountry(): ?string                   { return $this->invCountry; }
    public function setInvCountry(?string $c): self            { $this->invCountry=$c; return $this; }

    public function getContactAccountingName(): ?string        { return $this->contactAccountingName; }
    public function setContactAccountingName(?string $n): self { $this->contactAccountingName=$n; return $this; }
    public function getContactAccountingPhone(): ?string       { return $this->contactAccountingPhone; }
    public function setContactAccountingPhone(?string $p): self{ $this->contactAccountingPhone=$p; return $this; }
    public function getContactAccountingEmail(): ?string       { return $this->contactAccountingEmail; }
    public function setContactAccountingEmail(?string $e): self{ $this->contactAccountingEmail=$e; return $this; }

    public function getContactDispatchName(): ?string          { return $this->contactDispatchName; }
    public function setContactDispatchName(?string $n): self   { $this->contactDispatchName=$n; return $this; }
    public function getContactDispatchPhone(): ?string         { return $this->contactDispatchPhone; }
    public function setContactDispatchPhone(?string $p): self  { $this->contactDispatchPhone=$p; return $this; }
    public function getContactDispatchEmail(): ?string         { return $this->contactDispatchEmail; }
    public function setContactDispatchEmail(?string $e): self  { $this->contactDispatchEmail=$e; return $this; }

    public function getPhone(): string                         { return $this->phone; }
    public function setPhone(string $p): self                  { $this->phone=$p; return $this; }
    public function getEmergencyPhone(): ?string               { return $this->emergencyPhone; }
    public function setEmergencyPhone(?string $p): self        { $this->emergencyPhone=$p; return $this; }
    public function getEmail(): string                         { return $this->email; }
    public function setEmail(string $e): self                  { $this->email=$e; return $this; }

    public function getDeliveryStreet(): string                { return $this->deliveryStreet; }
    public function setDeliveryStreet(string $s): self         { $this->deliveryStreet=$s; return $this; }
    public function getDeliveryHouseNumber(): string           { return $this->deliveryHouseNumber; }
    public function setDeliveryHouseNumber(string $n): self    { $this->deliveryHouseNumber=$n; return $this; }
    public function getDeliveryPostalCode(): string            { return $this->deliveryPostalCode; }
    public function setDeliveryPostalCode(string $p): self     { $this->deliveryPostalCode=$p; return $this; }
    public function getDeliveryCity(): string                  { return $this->deliveryCity; }
    public function setDeliveryCity(string $c): self           { $this->deliveryCity=$c; return $this; }
    public function getDeliveryCountry(): string               { return $this->deliveryCountry; }
    public function setDeliveryCountry(string $c): self        { $this->deliveryCountry=$c; return $this; }
    public function getDeliveryPhone(): ?string                { return $this->deliveryPhone; }
    public function setDeliveryPhone(?string $p): self         { $this->deliveryPhone=$p; return $this; }
    public function getDeliveryEmail(): ?string                { return $this->deliveryEmail; }
    public function setDeliveryEmail(?string $e): self         { $this->deliveryEmail=$e; return $this; }

    public function getOpeningHoursWarehouse(): array          { return $this->openingHoursWarehouse; }
    public function setOpeningHoursWarehouse(array $h): self   { $this->openingHoursWarehouse=$h; return $this; }
    public function getOpeningHoursOffice(): array             { return $this->openingHoursOffice; }
    public function setOpeningHoursOffice(array $h): self      { $this->openingHoursOffice=$h; return $this; }

    public function hasForklift(): bool                        { return $this->hasForklift; }
    public function setHasForklift(bool $v): self              { $this->hasForklift=$v; return $this; }
    public function isColoader(): bool                         { return $this->isColoader; }
    public function setIsColoader(bool $v): self               { $this->isColoader=$v; return $this; }

    /* ---------------- PostalCodeRanges ---------------- */
    public function getPostalCodeRanges(): Collection          { return $this->postalCodeRanges; }
    public function addPostalCodeRange(PostalCodeRange $r): self
    {
        if (!$this->postalCodeRanges->contains($r)) {
            $this->postalCodeRanges->add($r);
            $r->setPartner($this);
        }
        return $this;
    }
    public function removePostalCodeRange(PostalCodeRange $r): self
    {
        if ($this->postalCodeRanges->removeElement($r) && $r->getPartner() === $this) {
            $r->setPartner(null);
        }
        return $this;
    }

    /* ---------------- LineSchedules ------------------- */
    public function getLineSchedules(): Collection            { return $this->lineSchedules; }
    public function addLineSchedule(LineSchedule $l): self
    {
        if (!$this->lineSchedules->contains($l)) {
            $this->lineSchedules->add($l);
            $l->setPartner($this);
        }
        return $this;
    }
    public function removeLineSchedule(LineSchedule $l): self
    {
        if ($this->lineSchedules->removeElement($l) && $l->getPartner() === $this) {
            $l->setPartner(null);
        }
        return $this;
    }
}
