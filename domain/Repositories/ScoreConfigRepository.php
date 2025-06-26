<?php

declare(strict_types=1);

namespace Sports\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\Competition\CompetitionSport as CompetitionSport;
use Sports\Round;
use Sports\Score\ScoreConfig as ScoreConfig;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<ScoreConfig>
 */
class ScoreConfigRepository extends EntityRepository
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
