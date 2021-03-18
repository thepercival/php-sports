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

    public function save(Competition\Sport|Planning\GameAmountConfig|Qualify\AgainstConfig|Score\Config $object): Score\Config|Qualify\AgainstConfig|Planning\GameAmountConfig|Competition\Sport
    {
        try {
            $this->_em->persist($object);
            $this->_em->flush();
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), E_ERROR);
        }

        return $object;
    }

    public function remove(bool|Competition\Field|Competition\Sport|Sport|Game|Score\Against $object): void
    {
        $this->_em->remove($object);
        $this->_em->flush();
    }

    public function getEM(): \Doctrine\ORM\EntityManager
    {
        return $this->getEntityManager();
    }
}
