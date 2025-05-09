<?php

namespace App\Entity;

use App\Repository\SequenceCounterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SequenceCounterRepository::class)]
#[ORM\Table(name: 'sequence_counters')]
class SequenceCounter
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 50)]
    private string $name;

    #[ORM\Column(type: 'bigint')]
    private int $lastValue = 0;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getLastValue(): int
    {
        return $this->lastValue;
    }

    public function setLastValue(int $lastValue): self
    {
        $this->lastValue = $lastValue;
        return $this;
    }
}
