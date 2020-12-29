<?php

namespace Sports\Sport\GameAmountConfig;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Sport;
use Sports\Sport\ConfigDep as SportConfig;
use Sports\Round\Number as RoundNumber;

class Repository extends \Sports\Repository
{
    public function addObjects(CompetitionSport $competitionSport, RoundNumber $roundNumber)
    {
        $sportGameAmountConfig = $roundNumber->getSportGameAmountConfig($competitionSport);
        if ($sportGameAmountConfig === null) {
            return;
        }
        $this->save($sportGameAmountConfig);
        if ($roundNumber->hasNext()) {
            $this->addObjects($competitionSport, $roundNumber->getNext());
        }
    }

    public function removeObjects(CompetitionSport $competitionSport)
    {
        $sportGameAmountConfigs = $this->findByCompetitionSport($competitionSport);
        foreach ($sportGameAmountConfigs as $config) {
            $this->remove($config);
        }
    }

    public function findByCompetitionSport(CompetitionSport $competitionSport)
    {
        $query = $this->createQueryBuilder('sgac')
            ->join("sgac.roundNumber", "rn")
            ->where('sgac.competitionSport = :competitionSport')
        ;
        $query = $query->setParameter('competitionSport', $competitionSport);
        return $query->getQuery()->getResult();
    }
}
