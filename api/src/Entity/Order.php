<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['order:read', 'order:write'])]
    private string $trackingNumber;

    #[ORM\Column(length: 50)]
    #[Groups(['order:read', 'order:write'])]
    private string $status;

    #[ORM\Column(length: 255)]
    #[Groups(['order:read', 'order:write'])]
    private string $recipientName;

    #[ORM\Column(type: 'text')]
    #[Groups(['order:read', 'order:write'])]
    private string $recipientAddress;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['order:read', 'order:write'])]
    private \DateTimeInterface $scheduledDelivery;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['order:read', 'order:write'])]
    private ?\DateTimeInterface $deliveredAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['order:read'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['order:read', 'order:write'])]
    private \DateTimeInterface $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrackingNumber(): string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(string $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getRecipientName(): string
    {
        return $this->recipientName;
    }

    public function setRecipientName(string $recipientName): static
    {
        $this->recipientName = $recipientName;
        return $this;
    }

    public function getRecipientAddress(): string
    {
        return $this->recipientAddress;
    }

    public function setRecipientAddress(string $recipientAddress): static
    {
        $this->recipientAddress = $recipientAddress;
        return $this;
    }

    public function getScheduledDelivery(): \DateTimeInterface
    {
        return $this->scheduledDelivery;
    }

    public function setScheduledDelivery(\DateTimeInterface $scheduledDelivery): static
    {
        $this->scheduledDelivery = $scheduledDelivery;
        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeInterface
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeInterface $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
