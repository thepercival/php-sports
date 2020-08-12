<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-2-17
 * Time: 22:02
 */

namespace Sports;

use Doctrine\ORM\EntityManager;
use Sports\Repository as VoetbalRepository;

class Service
{
    /**
     * @var EntityManager
     */
    protected $entitymanager;

    /**
     * Service constructor.
     * @param EntityManager $entitymanager
     */
    public function __construct(EntityManager $entitymanager)
    {
        $this->entitymanager = $entitymanager;
    }

    /**
     * @param string $classname
     * @return mixed
     */
    public function getRepository(string $classname)
    {
        return $this->getEntityManager()->getRepository($classname);
    }

    public function getStructureRepository()
    {
        return new Structure\Repository($this->getEntityManager());
    }

    public function getService($classname)
    {
        if ($classname === Association::class) {
            return new Association\Service();
        } elseif ($classname === Competition::class) {
            return new Competition\Service();
        } elseif ($classname === Game::class) {
            return new Game\Service();
        } elseif ($classname === Sport\Config::class) {
            return new Sport\Config\Service();
        }
        throw new \Exception("class " . $classname . " not supported to create service", E_ERROR);
    }

    public function getEntityManager()
    {
        return $this->entitymanager;
    }
}
