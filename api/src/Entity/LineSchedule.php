<?php
namespace App\Entity;

use App\Repository\LineScheduleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LineScheduleRepository::class)]
#[ORM\Table(name: 'line_schedule')]
class LineSchedule
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column]
    #[Groups(['partner:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy:'lineSchedules')]
    #[ORM\JoinColumn(nullable:false)]
    private Partner $partner;

    /** 1=Mon … 7=Sonntag */
    #[ORM\Column(type:'smallint')]
    #[Assert\Range(min:1,max:7)]
    #[Groups(['partner:read','partner:write'])]
    private int $weekday;

    #[ORM\Column(type:'time')]
    #[Groups(['partner:read','partner:write'])]
    private \DateTimeInterface $arrival;

    #[ORM\Column(type:'time')]
    #[Groups(['partner:read','partner:write'])]
    private \DateTimeInterface $departure;

    /* Getter/Setter */
    public function getId(): ?int                  { return $this->id; }
    public function getWeekday(): int              { return $this->weekday; }
    public function setWeekday(int $w): self       { $this->weekday=$w; return $this; }
    public function getArrival(): \DateTimeInterface    { return $this->arrival; }
    public function setArrival(\DateTimeInterface $a): self { $this->arrival=$a; return $this; }
    public function getDeparture(): \DateTimeInterface   { return $this->departure; }
    public function setDeparture(\DateTimeInterface $d): self { $this->departure=$d; return $this; }

    public function getPartner(): Partner          { return $this->partner; }
    public function setPartner(Partner $p): self   { $this->partner=$p; return $this; }
}
