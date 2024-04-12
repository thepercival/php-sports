<?php

namespace Sports\Competition\Sport;

use Exception;
use Sports\Competition\Sport as CompetitionSport;

class FromToMapper
{
    /**
     * @var array<string, CompetitionSport>
     */
    private array $map;

    /**
     * @param list<CompetitionSport> $fromCompetitionSports
     * @param list<CompetitionSport> $toCompetitionSports
     * @param FromToMapStrategy $fromToMapStrategy
     */
    public function __construct(
        array             $fromCompetitionSports,
        array             $toCompetitionSports,
        FromToMapStrategy $fromToMapStrategy
    )
    {
        $this->map = [];
        foreach ($fromCompetitionSports as $fromCompetitionSport) {

            $toCompetitionSport = null;
            foreach ($toCompetitionSports as $toCompetitionSportIt) {
                if ($fromToMapStrategy === FromToMapStrategy::ById) {
                    if ($fromCompetitionSport->getId() == $toCompetitionSportIt->getId()) {
                        $toCompetitionSport = $toCompetitionSportIt;
                        break;
                    }
                } else /*if ($fromToMapStrategy === FromToMapStrategy::ByProperties)*/ {
                    if ($fromCompetitionSport->equals($toCompetitionSportIt)) {
                        $toCompetitionSport = $toCompetitionSportIt;
                        break;
                    }
                }
            }

            if ($toCompetitionSport === null) {
                throw new Exception("een competitiesport kon niet gevonden worden(fromToMapper)", E_ERROR);
            }

            $key = $this->getFromCompetitionSportKey($fromCompetitionSport);
            $this->map[$key] = $toCompetitionSport;

            // remove from $toCompetitionSports
            $idx = array_search($toCompetitionSport, $toCompetitionSports, true);
            if ($idx === false) {
                throw new Exception("een competitiesport kon niet gevonden worden(fromToMapper)", E_ERROR);
            }
            array_splice($toCompetitionSports, $idx, 1);
        }
    }

    public function getToCompetitionSport(CompetitionSport $fromCompetitionSport): CompetitionSport
    {
        $key = $this->getFromCompetitionSportKey($fromCompetitionSport);
        if (!array_key_exists($key, $this->map)) {
            throw new Exception("een competitiesport kon niet gevonden worden(fromToMapper)", E_ERROR);
        }
        return $this->map[$key];
    }

    public function getFromCompetitionSportKey(CompetitionSport $fromCompetitionSport): string
    {
        return spl_object_hash($fromCompetitionSport);
    }
}
