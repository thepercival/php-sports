<?php

declare(strict_types=1);

namespace Sports\Score\Config;

use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Round;
use Sports\Score\Config as ScoreConfig;

/**
 * @template-extends EntityRepository<ScoreConfig>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<ScoreConfig>
     */
    use BaseRepository;

    public function addObjects(CompetitionSport $competitionSport, Round $round): void
    {
        $scoreConfig = $round->getScoreConfig($competitionSport);
        if ($scoreConfig === null) {
            return;
        }
        $this->save($scoreConfig);
        foreach ($round->getChildren() as $childRound) {
            $this->addObjects($competitionSport, $childRound);
        }
    }

    public function removeObjects(CompetitionSport $competitionSport): void
    {
        $scoreConfigs = $this->findBy([ "competitionSport" => $competitionSport ]);
        foreach ($scoreConfigs as $config) {
            $this->remove($config);
        }
    }
}
