<?php

declare(strict_types=1);

namespace Sports\Round\Number;

use Exception;
use Sports\Competition;
use Sports\Competition\Field;
use Sports\Competition\Referee;
use Sports\Competition\CompetitionSport as CompetitionSport;
use Sports\Place;
use Sports\Poule;
use Sports\Qualify\RoundRank\Service as RoundRankService;
use Sports\Round\Number as RoundNumber;
use Sports\Structure\PouleStructureNumberMap;
use SportsPlanning\Field as PlanningField;
use SportsPlanning\Game\GameAbstract as PlanningGameAbstract;
use SportsPlanning\Game\AgainstGame as PlanningAgainstGame;
use SportsPlanning\Game\TogetherGame as PlanningTogetherGame;
use SportsPlanning\Place as PlanningPlace;
use SportsPlanning\Planning;
use SportsPlanning\PlanningWithMeta;
use SportsPlanning\Poule as PlanningPoule;
use SportsPlanning\Referee as PlanningReferee;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstOneVsOneWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstOneVsTwoWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\AgainstTwoVsTwoWithNrAndFields;
use SportsPlanning\Sports\SportsWithNrAndFields\TogetherSportWithNrAndFields;

final class PlanningMapper
{
    /**
     * @var array<int, Poule>
     */
    protected array $pouleMap;
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

    public function __construct(RoundNumber $roundNumber, Planning $planning)
    {
        $this->init($roundNumber, $planning);
    }

    private function init(RoundNumber $roundNumber, Planning $planning): void
    {
        $planningGames = $this->getGamesForInit($roundNumber, $planning);

        $this->initPoules($roundNumber, $planningGames);

        $this->initCompetitionSportsMap($roundNumber, $planning);
        $this->initFields($roundNumber, $planningGames);

        $this->refereeMap = [];
        if (count($planning->referees) > 0) {
            $this->initReferees($roundNumber, $planningGames);
        }
    }

    /**
     * @param RoundNumber $roundNumber
     * @param Planning $planning
     * @return list<PlanningAgainstGame|PlanningTogetherGame>
     */
    private function getGamesForInit(RoundNumber $roundNumber, Planning $planning): array
    {
        $planningGames = $planning->getGames(PlanningWithMeta::ORDER_GAMES_BY_BATCH);
        if (!$roundNumber->isFirst()) {
            return array_reverse($planningGames);
        }
        return $planningGames;
    }

    /**
     * @param RoundNumber $roundNumber
     * @param list<PlanningAgainstGame|PlanningTogetherGame> $planningGames
     * @throws Exception
     */
    private function initPoules(RoundNumber $roundNumber, array $planningGames): void
    {
        $poulesNrOfPlacesMap = $this->getPoulesNrOfPlacesMap($roundNumber);

        $this->pouleMap = [];
        $planningGame = array_shift($planningGames);
        while (count($poulesNrOfPlacesMap) > 0 && $planningGame !== null) {
            $planningPoule = $planningGame->getPoule();
            if (isset($this->pouleMap[$planningPoule->getNumber()])) {
                $planningGame = array_shift($planningGames);
                continue;
            }
            $nrOfPlaces = $planningPoule->getPlaces()->count();
            $poule = array_shift($poulesNrOfPlacesMap[$nrOfPlaces]);
            if ($poule !== null) {
                if (count($poulesNrOfPlacesMap[$nrOfPlaces]) === 0) {
                    unset($poulesNrOfPlacesMap[$nrOfPlaces]);
                }
                $this->pouleMap[$planningPoule->getNumber()] = $poule;
            }
            $planningGame = array_shift($planningGames);
        }
    }

    /**
     * @param RoundNumber $roundNumber
     * @return array<int, list<Poule>>
     * @throws Exception
     */
    protected function getPoulesNrOfPlacesMap(RoundNumber $roundNumber): array
    {
        $poules = $this->getSortedPoules($roundNumber);

        $poulesNrOfPlacesMap = [];
        foreach ($poules as $poule) {
            $nrOfPlaces = $poule->getPlaces()->count();
            if (!isset($poulesNrOfPlacesMap[$nrOfPlaces])) {
                $poulesNrOfPlacesMap[$nrOfPlaces] = [];
            }
            $poulesNrOfPlacesMap[$nrOfPlaces][] = $poule;
        }
        return $poulesNrOfPlacesMap;
    }

    /**
     * @param RoundNumber $roundNumber
     * @return list<Poule>
     * @throws Exception
     */
    protected function getSortedPoules(RoundNumber $roundNumber): array
    {
        $poules = $roundNumber->getPoules();
        $fncBaseSort = function (Poule $pouleA, Poule $pouleB): int {
            if ($pouleA->getPlaces()->count() !== $pouleB->getPlaces()->count()) {
                return $pouleB->getPlaces()->count() - $pouleA->getPlaces()->count();
            }
            $nrOfQualifyGroupsA = count($pouleA->getRound()->getQualifyGroups());
            $nrOfQualifyGroupsB = count($pouleB->getRound()->getQualifyGroups());
            if( $nrOfQualifyGroupsA !== $nrOfQualifyGroupsB) {
                return $nrOfQualifyGroupsA - $nrOfQualifyGroupsB;
            }
            return $pouleA->getRound()->getNrOfPlacesChildren() - $pouleB->getRound()->getNrOfPlacesChildren();
        };

        if ($roundNumber->isFirst()) {
            usort($poules, $fncBaseSort);
        } else {
            $bestLast = $roundNumber->getValidPlanningConfig()->getBestLast();
            $pouleStructureNumberMap = new PouleStructureNumberMap($roundNumber, new RoundRankService());
            usort(
                $poules,
                function (Poule $pouleA, Poule $pouleB) use ($fncBaseSort, $pouleStructureNumberMap, $bestLast): int {
                    if( !$bestLast ) {
                        $baseSort = $fncBaseSort($pouleA, $pouleB);
                        if ($baseSort !== 0) {
                            return $baseSort;
                        }
                    }
                    $pouleAStructureNumber = $pouleStructureNumberMap->get($pouleA);
                    $pouleBStructureNumber = $pouleStructureNumberMap->get($pouleB);
                    return $pouleAStructureNumber < $pouleBStructureNumber ? -1 : 1;
                }
            );
        }
        return $poules;
    }

    /**
     * @param RoundNumber $roundNumber
     * @param list<TogetherSportWithNrAndFields|AgainstOneVsOneWithNrAndFields|AgainstOneVsTwoWithNrAndFields|AgainstTwoVsTwoWithNrAndFields> $sportsWithNrAndFields
     * @throws Exception
     */
    protected function initCompetitionSportsMap(RoundNumber $roundNumber, array $sportsWithNrAndFields): void
    {
        $this->competitionSportMap = [];
        $competitionSports = array_values($roundNumber->getCompetitionSports()->toArray());
        usort(
            $competitionSports,
            function (CompetitionSport $competitionSportA, CompetitionSport $competitionSportB): int {
                return count($competitionSportB->getFields()) - count($competitionSportA->getFields());
            }
        );
        usort(
            $sportsWithNrAndFields,
            function (PlanningSport $planningSportA, PlanningSport $planningSportB): int {
                return count($planningSportB->getFields()) - count($planningSportA->getFields());
            }
        );
        foreach ($sportsWithNrAndFields as $sportWithNrAndFields) {
            $removedCompetitionSport = $this->removeCompetitionSport(
                $competitionSports,
                $sportWithNrAndFields,
                $roundNumber
            );
            $this->competitionSportMap[$sportWithNrAndFields->sportNr] = $removedCompetitionSport;
        }
    }

    /**
     * @param list<CompetitionSport> $p_competitionSports
     * @param TogetherSportWithNrAndFields|AgainstOneVsOneWithNrAndFields|AgainstOneVsTwoWithNrAndFields|AgainstTwoVsTwoWithNrAndFields $sportWithNrAndFields
     * @param RoundNumber $roundNumber
     * @return CompetitionSport
     * @throws Exception
     */
    protected function removeCompetitionSport(
        array &$p_competitionSports,
        TogetherSportWithNrAndFields|AgainstOneVsOneWithNrAndFields|AgainstOneVsTwoWithNrAndFields|AgainstTwoVsTwoWithNrAndFields $sportWithNrAndFields,
        RoundNumber $roundNumber
    ): CompetitionSport {
        $competitionSports = $p_competitionSports;
        $sameCompetitionSports = array_filter(
            $competitionSports,
            function (CompetitionSport $competitionSport) use ($roundNumber, $sportWithNrAndFields): bool {
                $sport = $roundNumber->getValidGameAmountConfig($competitionSport)->createSport();
                return $sportWithNrAndFields->sport == $sport;
            }
        );
        $sameCompetitionSportsAndFields = array_filter(
            $sameCompetitionSports,
            function (CompetitionSport $competitionSport) use ($sportWithNrAndFields): bool {
                return count($competitionSport->getFields()) >= count($sportWithNrAndFields->fields);
            }
        );

        $competitionSport = array_shift($sameCompetitionSportsAndFields);
        if ($competitionSport === null) {
            throw new Exception("competitionsport could not be found", E_ERROR);
        }
        $idx = array_search($competitionSport, $p_competitionSports, true);
        if ($idx === false) {
            throw new Exception("competitionsport could not be found", E_ERROR);
        }
        $removedCompetitionSports = array_splice($p_competitionSports, $idx, 1);
        if (count($removedCompetitionSports) === 0) {
            throw new Exception("competitionsport could not be found", E_ERROR);
        }
        return $competitionSport;
    }

    /**
     * @param RoundNumber $roundNumber
     * @param list<PlanningAgainstGame|PlanningTogetherGame> $planningGames
     * @throws Exception
     */
    private function initFields(RoundNumber $roundNumber, array $planningGames): void
    {
        $competitionSportsFieldMap = $this->getCompetitionSportsFieldMap($roundNumber->getCompetition());
        $this->fieldMap = [];

        $planningGame = array_shift($planningGames);
        while (count($competitionSportsFieldMap) > 0 && $planningGame !== null) {
            $planningField = $planningGame->getField();
            $planningSportNr = $planningField->getSport()->getNumber();
            if (isset($this->fieldMap[$planningField->getUniqueIndex()])
                || !isset($competitionSportsFieldMap[$planningSportNr])) {
                $planningGame = array_shift($planningGames);
                continue;
            }

            $field = array_shift($competitionSportsFieldMap[$planningSportNr]);
            if ($field !== null) {
                if (count($competitionSportsFieldMap[$planningSportNr]) === 0) {
                    unset($competitionSportsFieldMap[$planningSportNr]);
                }
                $this->fieldMap[$planningField->getUniqueIndex()] = $field;
            }

            $planningGame = array_shift($planningGames);
        }
    }

    /**
     * @param Competition $competition
     * @return array<int, list<Field>>
     * @throws Exception
     */
    protected function getCompetitionSportsFieldMap(Competition $competition): array
    {
        $competitionSportsFieldMap = [];
        foreach ($this->competitionSportMap as $inputSportNumber => $competitionSport) {
            $competitionSportsFieldMap[$inputSportNumber] = array_values($competitionSport->getFields()->toArray());
        }
        return $competitionSportsFieldMap;
    }

    /**
     * @param RoundNumber $roundNumber
     * @param list<PlanningAgainstGame|PlanningTogetherGame> $planningGames
     * @throws Exception
     */
    private function initReferees(RoundNumber $roundNumber, array $planningGames): void
    {
        $referees = $this->getSortedReferees($roundNumber->getCompetition());

        $planningGame = array_shift($planningGames);
        while (count($referees) > 0 && $planningGame !== null) {
            $planningReferee = $planningGame->getReferee();
            if ($planningReferee !== null && !isset($this->refereeMap[$planningReferee->getUniqueIndex()])) {
                $this->refereeMap[$planningReferee->getUniqueIndex()] = array_shift($referees);
            }
            $planningGame = array_shift($planningGames);
        }
    }

    /**
     * @param Competition $competition
     * @return list<Referee>
     */
    protected function getSortedReferees(Competition $competition): array
    {
        return array_values($competition->getReferees()->toArray());
    }

    public function getPoule(PlanningPoule $poule): Poule
    {
        if (!array_key_exists($poule->getNumber(), $this->pouleMap)) {
            throw new Exception('de poule kan niet gevonden worden', E_ERROR);
        }
        return $this->pouleMap[$poule->getNumber()];
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
        return $poule->getPlace($planningPlace->getPlaceNr());
    }
}
