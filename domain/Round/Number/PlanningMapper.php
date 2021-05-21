<?php
declare(strict_types=1);

namespace Sports\Round\Number;

use Exception;
use Sports\Ranking\Map\PouleStructureNumber as PouleStructureNumberMap;
use Sports\Ranking\Map\PreviousNrOfDropouts as PreviousNrOfDropoutsMap;
use Sports\Round\Number as RoundNumber;
use SportsPlanning\Input;
use Sports\Poule;
use SportsPlanning\Poule as PlanningPoule;
use Sports\Place;
use SportsPlanning\Place as PlanningPlace;
use Sports\Competition\Field;
use SportsPlanning\Field as PlanningField;
use Sports\Competition\Sport as CompetitionSport;
use SportsPlanning\Sport as PlanningSport;
use Sports\Competition\Referee;
use SportsPlanning\Referee as PlanningReferee;

class PlanningMapper
{
    /**
     * @var list<Poule>
     */
    protected array $poules;
    /**
     * @var array<string, Referee>
     */
    protected array $refereeMap;
    /**
     * @var array<int, CompetitionSport>
     */
    protected array $competitionSportMap;
    /**
     * @var array<string, Field>
     */
    protected array $fieldMap;

    public function __construct(RoundNumber $roundNumber, Input $input)
    {
        $this->initPoules($roundNumber);
        $this->initCompetitionSports($roundNumber, $input);
        $this->initReferees($roundNumber, $input);
    }

    protected function initPoules(RoundNumber $roundNumber): void
    {
        $poules = $roundNumber->getPoules();
        if ($roundNumber->isFirst()) {
            usort($poules, function (Poule $pouleA, Poule $pouleB) {
                return $pouleA->getPlaces()->count() >= $pouleB->getPlaces()->count() ? -1 : 1;
            });
        } else {
            $someRound = $roundNumber->getRounds()->first();
            if ($someRound === false) {
                throw new Exception("rondenummer heeft geen rondes", E_ERROR);
            }
            $previousNrOfDropoutsMap = new PreviousNrOfDropoutsMap($someRound);
            $pouleStructureNumberMap = new PouleStructureNumberMap($roundNumber, $previousNrOfDropoutsMap);
            usort(
                $poules,
                function (Poule $pouleA, Poule $pouleB) use ($pouleStructureNumberMap) : int {
                    if ($pouleA->getPlaces()->count() === $pouleB->getPlaces()->count()) {
                        $pouleAStructureNumber = $pouleStructureNumberMap->get($pouleA);
                        $pouleBStructureNumber = $pouleStructureNumberMap->get($pouleB);
                        return $pouleAStructureNumber >= $pouleBStructureNumber ? -1 : 1;
                    }
                    return $pouleA->getPlaces()->count() >= $pouleB->getPlaces()->count() ? -1 : 1;
                }
            );
        }
        $this->poules = $poules;
    }

    protected function initCompetitionSports(RoundNumber $roundNumber, Input $input): void
    {
        $maxNrOfFields = $input->getMaxNrOfBatchGames();
        $this->competitionSportMap = [];
        $competitionSports = array_values($roundNumber->getCompetitionSports()->toArray());
        foreach ($input->getSports() as $sport) {
            $filtered = array_filter($competitionSports, function (CompetitionSport $competitionSport) use ($sport, $maxNrOfFields): bool {
                return ($competitionSport->getFields()->count() === $sport->getFields()->count()
                        || $competitionSport->getFields()->count() > $maxNrOfFields)
                    && $competitionSport->getNrOfHomePlaces() === $sport->getNrOfHomePlaces()
                    && $competitionSport->getNrOfAwayPlaces() === $sport->getNrOfAwayPlaces()
                    && $competitionSport->getNrOfGamePlaces() === $sport->getNrOfGamePlaces()
                    && $competitionSport->getNrOfH2H() === $sport->getNrOfH2H()
                    && $competitionSport->getNrOfGamesPerPlace() === $sport->getNrOfGamesPerPlace();
            });

            $filteredCompetitionSport = reset($filtered);
            if ($filteredCompetitionSport === false) {
                throw new Exception("competitionsport could not be found", E_ERROR);
            }
            $idx = array_search($filteredCompetitionSport, $competitionSports, true);
            if ($idx === false) {
                throw new Exception("competitionsport could not be found", E_ERROR);
            }
            array_splice($competitionSports, $idx, 1);
            $this->competitionSportMap[$sport->getNumber()] = $filteredCompetitionSport;
        }
        $this->initFields($input);
    }

    protected function initFields(Input $input): void
    {
        $this->fieldMap = [];
        foreach ($input->getSports() as $planningSport) {
            $competitionSport = $this->getCompetitionSport($planningSport);
            foreach ($planningSport->getFields() as $planningField) {
                $field = $competitionSport->getField($planningField->getNumber());
                $this->fieldMap[$planningField->getUniqueIndex()] = $field;
            }
        }
    }

    protected function initReferees(RoundNumber $roundNumber, Input $input): void
    {
        $this->refereeMap = [];
        foreach ($input->getReferees() as $planningReferee) {
            $referee = $roundNumber->getCompetition()->getReferee($planningReferee->getNumber());
            $this->refereeMap[$planningReferee->getUniqueIndex()] = $referee;
        }
    }

    public function getPoule(PlanningPoule $poule): Poule
    {
        if (!array_key_exists($poule->getNumber() - 1, $this->poules)) {
            throw new Exception('de poule kan niet gevonden worden', E_ERROR);
        }
        return $this->poules[$poule->getNumber() - 1];
    }

    public function getCompetitionSport(PlanningSport $planningSport): CompetitionSport
    {
        if (!array_key_exists($planningSport->getNumber(), $this->competitionSportMap)) {
            throw new Exception('de sport kan niet gevonden worden', E_ERROR);
        }
        return $this->competitionSportMap[$planningSport->getNumber()];
    }

    public function getField(PlanningField|null $planningField): Field|null
    {
        if ($planningField === null) {
            return null;
        }
        if (!array_key_exists($planningField->getUniqueIndex(), $this->fieldMap)) {
            throw new Exception('het veld kan niet gevonden worden', E_ERROR);
        }
        return $this->fieldMap[$planningField->getUniqueIndex()];
    }

    public function getReferee(PlanningReferee|null $planningReferee): Referee|null
    {
        if ($planningReferee === null) {
            return null;
        }
        if (!array_key_exists($planningReferee->getUniqueIndex(), $this->refereeMap)) {
            throw new Exception('de scheidsrechter kan niet gevonden worden', E_ERROR);
        }
        return $this->refereeMap[$planningReferee->getUniqueIndex()];
    }

    public function getRefereePlace(PlanningPlace $planningPlace = null): Place|null
    {
        if ($planningPlace === null) {
            return null;
        }
        return $this->getPlace($planningPlace);
    }

    public function getPlace(PlanningPlace $planningPlace): Place
    {
        $poule = $this->getPoule($planningPlace->getPoule());
        return $poule->getPlace($planningPlace->getNumber());
    }
}
