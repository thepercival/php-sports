<?php

namespace Sports;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

class Repository extends EntityRepository
{
    public function __construct(EntityManagerInterface $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    public function save($object)
    {
        try {
            $this->_em->persist($object);
            $this->_em->flush();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), E_ERROR);
        }

        return $object;
    }

    public function remove($object)
    {
        $this->_em->remove($object);
        $this->_em->flush();
    }

    public function getEM()
    {
        return $this->getEntityManager();
    }
}
