<?php

namespace App\Entity;

use App\Repository\PackageStatusRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\Package;
use App\Entity\StatusCode;

#[ORM\Entity(repositoryClass: PackageStatusRepository::class)]
#[ORM\Table(name: 'package_status')]
#[ORM\HasLifecycleCallbacks]
class PackageStatus
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['status:read','status:write','shipment:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Package::class, inversedBy: 'statuses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['status:read','status:write','shipment:read'])]
    private Package $package;

    #[ORM\ManyToOne(targetEntity: StatusCode::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['status:read','status:write','shipment:read'])]
    private StatusCode $statusCode;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['status:read','status:write','shipment:read'])]
    private \DateTimeInterface $occurredAt;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['status:read','status:write','shipment:read'])]
    private ?string $note = null;

    public function __construct()
    {
        $this->id         = Uuid::v4();
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPackage(): Package
    {
        return $this->package;
    }

    public function setPackage(Package $package): self
    {
        $this->package = $package;
        return $this;
    }

    public function getStatusCode(): StatusCode
    {
        return $this->statusCode;
    }

    public function setStatusCode(StatusCode $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getOccurredAt(): \DateTimeInterface
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(\DateTimeInterface $occurredAt): self
    {
        $this->occurredAt = $occurredAt;
        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }
}
