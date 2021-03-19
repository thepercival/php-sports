<?php
declare(strict_types=1);

namespace Sports;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;

class Repository extends EntityRepository
{
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    public function save(object $object): object
    {
        try {
            $this->_em->persist($object);
            $this->_em->flush();
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), E_ERROR);
        }

        return $object;
    }

    public function remove(object $object): void
    {
        $this->_em->remove($object);
        $this->_em->flush();
    }

    public function getEM(): \Doctrine\ORM\EntityManager
    {
        return $this->getEntityManager();
    }
}
