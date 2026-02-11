<?php

declare(strict_types=1);

namespace Sports;

use Doctrine\ORM\EntityManager;
use Sports\Poule\Horizontal\Creator as HorizontalPouleCreator;
use Sports\Qualify\Rule\Creator as QualifyRuleCreator;

final class Service
{
    public function __construct(protected EntityManager $entitymanager)
    {
    }

    public function getStructureRepository(): Repositories\StructureRepository
    {
        return new Repositories\StructureRepository(
            $this->getEntityManager(),
            new HorizontalPouleCreator(),
            new QualifyRuleCreator()
        );
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @return object
     * @throws \Exception
     */
    public function getService(string $className): object
    {
        if ($className === Association::class) {
            return new Association\Service();
        } elseif ($className === Competition::class) {
            return new Competition\CompetitionEditor();
        }
        throw new \Exception("class " . $className . " not supported to create service", E_ERROR);
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entitymanager;
    }
}
