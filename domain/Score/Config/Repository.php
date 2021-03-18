<?php
declare(strict_types=1);

namespace Sports\Score\Config;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Round;
use Sports\Sport;
use Sports\Sport\ConfigDep as SportConfig;
use Sports\Round\Number as RoundNumber;

class Repository extends \Sports\Repository
{
    /**
     * @return void
     */
    public function addObjects(CompetitionSport $competitionSport, Round $round)
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
