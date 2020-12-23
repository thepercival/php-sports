<?php

namespace Sports\Sport\ScoreConfig;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Sport;
use Sports\Sport\ConfigDep as SportConfig;
use Sports\Round\Number as RoundNumber;

class Repository extends \Sports\Repository
{
    public function addObjects(CompetitionSport $competitionSport, RoundNumber $roundNumber)
    {
        $sportScoreConfig = $roundNumber->getSportScoreConfig($competitionSport);
        if ($sportScoreConfig === null) {
            return;
        }
        $this->save($sportScoreConfig);
        if ($roundNumber->hasNext()) {
            $this->addObjects($competitionSport, $roundNumber->getNext());
        }
    }

    public function removeObjects(CompetitionSport $competitionSport)
    {
        $sportScoreConfigs = $this->findByCompetitionSport($competitionSport);
        foreach ($sportScoreConfigs as $config) {
            $this->remove($config);
        }
    }

    public function findByCompetitionSport(CompetitionSport $competitionSport)
    {
        $query = $this->createQueryBuilder('ssc')
            ->join("ssc.roundNumber", "rn")
            ->where('ssc.competitionSport = :competitionSport')
        ;
        $query = $query->setParameter('competitionSport', $competitionSport);
        return $query->getQuery()->getResult();
    }
}
