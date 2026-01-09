<?php

declare(strict_types=1);

namespace Sports;

use Doctrine\ORM\EntityManager;
use Sports\Association\AssociationService;
use Sports\Competition\CompetitionService;

final class Service
{
    public function __construct(protected EntityManager $entitymanager)
    {
    }

//    public function getStructureRepository(): \FCToernooi\Repositories\Structure\StructureRepository
//    {
//        return new \FCToernooi\Repositories\Structure\StructureRepository(
//            $this->getEntityManager(),
//            new HorizontalPouleCreator(),
//            new QualifyRuleCreator()
//        );
//    }

    /**
     * @template T
     * @param class-string<T> $className
     * @return object
     * @throws \Exception
     */
    public function getService(string $className): object
    {
        if ($className === Association::class) {
            return new AssociationService();
        } elseif ($className === Competition::class) {
            return new CompetitionService();
        }
        throw new \Exception("class " . $className . " not supported to create service", E_ERROR);
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entitymanager;
    }
}
