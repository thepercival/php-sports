<?php
declare(strict_types=1);

namespace Sports;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;

class Service
{
    public function __construct(protected EntityManager $entitymanager)
    {
    }

    public function getStructureRepository(): Structure\Repository
    {
        return new Structure\Repository($this->getEntityManager());
    }

    public function getService($classname): Competition\Service|Association\Service
    {
        if ($classname === Association::class) {
            return new Association\Service();
        } elseif ($classname === Competition::class) {
            return new Competition\Service();
        }
        throw new \Exception("class " . $classname . " not supported to create service", E_ERROR);
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entitymanager;
    }
}
