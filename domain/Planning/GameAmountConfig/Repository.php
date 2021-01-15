<?php

namespace Sports\Planning\GameAmountConfig;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Round\Number as RoundNumber;

class Repository extends \Sports\Repository
{
    public function addObjects(CompetitionSport $competitionSport, RoundNumber $roundNumber)
    {
        $gameAmountConfig = $roundNumber->getGameAmountConfig($competitionSport);
        if ($gameAmountConfig === null) {
            return;
        }
        $this->save($gameAmountConfig);
        if ($roundNumber->hasNext()) {
            $this->addObjects($competitionSport, $roundNumber->getNext());
        }
    }

    public function removeObjects(CompetitionSport $competitionSport)
    {
        $gameAmountConfigs = $this->findBy( ["competitionSport" => $competitionSport ] );
        foreach ($gameAmountConfigs as $config) {
            $this->remove($config);
        }
    }
}
