<?php

declare(strict_types=1);

namespace Sports\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\Competition\CompetitionSport as CompetitionSport;
use Sports\Qualify\AgainstConfig as AgainstQualifyConfig;
use Sports\Round;

/**
 * @template-extends EntityRepository<AgainstQualifyConfig>
 */
final class AgainstQualifyConfigRepository extends EntityRepository
{
    public function addObjects(CompetitionSport $competitionSport, Round $round): void
    {
        $qualifyConfig = $round->getAgainstQualifyConfig($competitionSport);
        if ($qualifyConfig === null) {
            return;
        }
        $this->getEntityManager()->persist($qualifyConfig);
    }

    public function removeObjects(CompetitionSport $competitionSport): void
    {
        $qualifyConfigs = $this->findBy(["competitionSport" => $competitionSport]);
        foreach ($qualifyConfigs as $qualifyConfig) {
            $this->getEntityManager()->remove($qualifyConfig);
            $this->getEntityManager()->flush();
        }
    }
}
