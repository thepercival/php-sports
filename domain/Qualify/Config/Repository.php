<?php

namespace Sports\Qualify\Config;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Sport\ConfigDep as SportConfig;
use Sports\Round\Number as RoundNumber;

class Repository extends \Sports\Repository
{
    public function addObjects(CompetitionSport $competitionSport, RoundNumber $roundNumber)
    {
        $qualifyConfig = $roundNumber->getQualifyConfig($competitionSport);
        if ($qualifyConfig === null) {
            return;
        }
        $this->save($qualifyConfig);
    }

    public function removeObjects(CompetitionSport $competitionSport)
    {
        $qualifyConfigs = $this->findByCompetitionSport($competitionSport);
        foreach ($qualifyConfigs as $qualifyConfig) {
            $this->remove($qualifyConfig);
        }
    }

    public function findByCompetitionSport(CompetitionSport $competitionSport)
    {
        $competition = $competitionSport->getCompetition();
        $query = $this->createQueryBuilder('qc')
            ->join("qc.roundNumber", "rn")
            ->where('qc.competitionSport = :competitionSport')
        ;
        $query = $query->setParameter('competitionSport', $competitionSport);
        return $query->getQuery()->getResult();
    }
}
