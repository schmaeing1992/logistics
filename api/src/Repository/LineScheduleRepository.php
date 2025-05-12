<?php
namespace App\Repository;

use App\Entity\LineSchedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LineScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $reg)
    { parent::__construct($reg, LineSchedule::class); }
}
