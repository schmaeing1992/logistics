<?php
namespace App\Entity;

use App\Repository\StatusCodeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: StatusCodeRepository::class)]
#[ORM\Table(name: 'status_code')]
class StatusCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['status:read','status:write','shipment:read','shipment:write'])]
    private int $id;

    #[ORM\Column(type: 'string', length: 3, unique: true)]
    #[Groups(['status:read','status:write','shipment:read','shipment:write'])]
    private string $code;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['status:read','status:write','shipment:read','shipment:write'])]
    private string $description;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }
}
