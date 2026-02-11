<?php

declare(strict_types=1);

namespace Sports\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\Competition\CompetitionSport;
use Sports\Round;
use Sports\Score\Config as ScoreConfig;

/**
 * @template-extends EntityRepository<ScoreConfig>
 */
final class ScoreConfigRepository extends EntityRepository
{

    public function addObjects(CompetitionSport $competitionSport, Round $round): void
    {
        $scoreConfig = $round->getScoreConfig($competitionSport);
        if ($scoreConfig === null) {
            return;
        }
        $this->getEntityManager()->persist($scoreConfig);
        foreach ($round->getChildren() as $childRound) {
            $this->addObjects($competitionSport, $childRound);
        }
    }

    public function removeObjects(CompetitionSport $competitionSport): void
    {
        $scoreConfigs = $this->findBy([ "competitionSport" => $competitionSport ]);
        foreach ($scoreConfigs as $config) {
            $this->getEntityManager()->remove($config);
        }
    }
}
