<?php
declare(strict_types=1);

namespace Sports\Qualify\AgainstConfig;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Repository as SportRepository;
use Sports\Round\Number as RoundNumber;

class Repository extends SportRepository
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
        $query = $this->createQueryBuilder('qc')
            ->join("qc.roundNumber", "rn")
            ->where('qc.competitionSport = :competitionSport')
        ;
        $query = $query->setParameter('competitionSport', $competitionSport);
        return $query->getQuery()->getResult();
    }
}
