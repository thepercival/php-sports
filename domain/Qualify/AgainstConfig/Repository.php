<?php
declare(strict_types=1);

namespace Sports\Qualify\AgainstConfig;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Repository as SportRepository;
use Sports\Round;
use Sports\Round\Number as RoundNumber;

class Repository extends SportRepository
{
    public function addObjects(CompetitionSport $competitionSport, Round $round)
    {
        $qualifyConfig = $round->getQualifyAgainstConfig($competitionSport);
        if ($qualifyConfig === null) {
            return;
        }
        $this->save($qualifyConfig);
    }

    public function removeObjects(CompetitionSport $competitionSport)
    {
        $qualifyConfigs = $this->findBy(["competitionSport" => $competitionSport]);
        foreach ($qualifyConfigs as $qualifyConfig) {
            $this->remove($qualifyConfig);
        }
    }
}
