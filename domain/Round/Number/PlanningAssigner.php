<?php

namespace Sports\Round\Number;

use DateTimeImmutable;
use Exception;
use SportsHelpers\SportConfig;
use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use Sports\Planning\Config as PlanningConfig;
use Sports\Round\Number as RoundNumber;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsPlanning\Planning;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Place\Together as TogetherGamePlace;
use SportsPlanning\Game\Against as AgainstPlanningGame;
use SportsPlanning\Game\Place\Against as AgainstPlanningGamePlace;
use SportsPlanning\Game\Together as TogetherPlanningGame;
use SportsPlanning\Game\Place\Together as TogetherPlanningGamePlace;
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

class PlanningAssigner
{
    /**
     * @var array|Poule[]
     */
    protected $poules;
    /**
     * @var array|Field[]
     */
    protected $fieldMap;
    /**
     * @var array|Referee[]
     */
    protected $refereeMap;
    /**
     * @var array|CompetitionSport[]
     */
    protected $competitionSportMap;
    /**
     * @var PlanningScheduler
     */
    protected $scheduleService;

    public function __construct(PlanningScheduler $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function createGames(RoundNumber $roundNumber, Planning $planning)
    {
        $this->initResources($roundNumber, $planning);
        $firstBatch = $planning->createFirstBatch();
        $gameStartDateTime = $this->scheduleService->getRoundNumberStartDateTime($roundNumber);
        $planningConfig = $roundNumber->getValidPlanningConfig();
        $this->createBatchGames($firstBatch, $planningConfig, $gameStartDateTime);
    }

    /**
     * @param Batch|SelfRefereeBatch $batch
     * @param PlanningConfig $planningConfig
     * @param DateTimeImmutable $gameStartDateTime
     */
    protected function createBatchGames($batch, PlanningConfig $planningConfig, DateTimeImmutable $gameStartDateTime)
    {
        if( $planningConfig->getGameMode() === SportConfig::GAMEMODE_AGAINST ) {
            $this->createAgainstBatchGames($batch, $gameStartDateTime);
        } else {
            $this->createTogetherBatchGames($batch, $gameStartDateTime);
        }
        if ($batch->hasNext()) {
            $nextGameStartDateTime = $this->scheduleService->getNextGameStartDateTime($planningConfig, $gameStartDateTime);
            $this->createBatchGames($batch->getNext(), $planningConfig, $nextGameStartDateTime);
        }
    }

    /**
     * @param Batch|SelfRefereeBatch $batch
     * @param DateTimeImmutable $gameStartDateTime
     */
    protected function createAgainstBatchGames($batch, DateTimeImmutable $gameStartDateTime)
    {
        /** @var AgainstPlanningGame $planningGame */
        foreach ($batch->getGames() as $planningGame) {
            $poule = $this->getPoule($planningGame->getPoule());
            $competitionSport = $this->getCompetitionSport($planningGame->getField()->getSport() );
            $game = new AgainstGame($poule, $planningGame->getBatchNr(), $gameStartDateTime, $competitionSport);
            $game->setField($this->getField($planningGame->getField()));
            $game->setReferee($this->getReferee($planningGame->getReferee()));
            $game->setRefereePlace($this->getPlace($planningGame->getRefereePlace()));
            /** @var AgainstPlanningGamePlace $againstPlanningGamePlace */
            foreach ($planningGame->getPlaces() as $againstPlanningGamePlace) {
                new AgainstGamePlace(
                    $game, $this->getPlace($againstPlanningGamePlace->getPlace()), $againstPlanningGamePlace->getHomeaway()
                );
            }
        }
    }

    /**
     * @param Batch|SelfRefereeBatch $batch
     * @param DateTimeImmutable $gameStartDateTime
     */
    protected function createTogetherBatchGames($batch, DateTimeImmutable $gameStartDateTime)
    {
        /** @var TogetherPlanningGame $planningGame */
        foreach ($batch->getGames() as $planningGame) {
            $poule = $this->getPoule($planningGame->getPoule());
            $competitionSport = $this->getCompetitionSport($planningGame->getField()->getSport() );
            $game = new TogetherGame($poule, $planningGame->getBatchNr(), $gameStartDateTime, $competitionSport);
            $game->setField($this->getField($planningGame->getField()));
            $game->setReferee($this->getReferee($planningGame->getReferee()));
            $game->setRefereePlace($this->getPlace($planningGame->getRefereePlace()));
            /** @var TogetherPlanningGamePlace $togetherPlanningGamePlace */
            foreach ($planningGame->getPlaces() as $togetherPlanningGamePlace) {
                new TogetherGamePlace(
                    $game, $this->getPlace($togetherPlanningGamePlace->getPlace()), $togetherPlanningGamePlace->getGameRoundNumber()
                );
            }
        }
    }

    protected function initResources(RoundNumber $roundNumber, Planning $planning)
    {
        $this->initPoules($roundNumber);
        $this->initCompetitionSports($roundNumber, $planning);
        $this->initReferees($roundNumber, $planning);
    }

    protected function initPoules(RoundNumber $roundNumber)
    {
        $poules = $roundNumber->getPoules();
        if ($roundNumber->isFirst()) {
            uasort($poules, function (Poule $pouleA, Poule $pouleB) {
                return $pouleA->getPlaces()->count() >= $pouleB->getPlaces()->count() ? -1 : 1;
            });
        } else {
            uasort(
                $poules,
                function (Poule $pouleA, Poule $pouleB) {
                    if ($pouleA->getPlaces()->count() === $pouleB->getPlaces()->count()) {
                        return $pouleA->getStructureNumber() >= $pouleB->getStructureNumber() ? -1 : 1;
                    }
                    return $pouleA->getPlaces()->count() >= $pouleB->getPlaces()->count() ? -1 : 1;
                }
            );
        }
        $this->poules = array_values($poules);
    }

    protected function initCompetitionSports(RoundNumber $roundNumber, Planning $planning) {
        $maxNrOfFields = $planning->getInput()->getMaxNrOfBatchGames();
        $this->competitionSportMap = [];
        $competitionSports = $roundNumber->getCompetitionSports()->toArray();
        foreach( $planning->getSports() as $sport ) {
            $filtered = array_filter( $competitionSports, function( CompetitionSport $competitionSport) use($sport, $maxNrOfFields): bool {
                return ( $competitionSport->getFields()->count() === $sport->getFields()->count()
                        || $competitionSport->getFields()->count() > $maxNrOfFields )
                    && $competitionSport->getSport()->getNrOfGamePlaces() === $sport->getNrOfGamePlaces();
            });
            $filteredCompetitionSport = reset($filtered);
            if( $filteredCompetitionSport === false ) {
                throw new Exception("competitionsport could not be found", E_ERROR );
            }
            array_splice($competitionSports, array_search($filteredCompetitionSport, $competitionSports, true ) );
            $this->competitionSportMap[$sport->getNumber()] = $filteredCompetitionSport;
        }
        $this->initFields($planning);
    }

    protected function initFields(Planning $planning)
    {
        $planningFields = $planning->getFields();
        $this->fieldMap = [];
        foreach ($planningFields as $planningField) {
            $sportConfig = $this->getCompetitionSport($planningField->getSport());
            $field = $sportConfig->getField($planningField->getNumber());
            $this->fieldMap[$planningField->getNumber()] = $field;
        }
    }

    protected function initReferees(RoundNumber $roundNumber, Planning $planning)
    {
        $this->refereeMap = [];
        foreach ($planning->getReferees() as $planningReferee) {
            $referee = $roundNumber->getCompetition()->getReferee($planningReferee->getPriority());
            $this->refereeMap[$planningReferee->getNumber()] = $referee;
        }
    }

    protected function getPoule(PlanningPoule $poule): Poule
    {
        return $this->poules[$poule->getNumber() - 1];
    }

    protected function getCompetitionSport(PlanningSport $planningSport ): CompetitionSport {
        return $this->competitionSportMap[$planningSport->getNumber()];
    }

    protected function getField(PlanningField $field): Field
    {
        return $this->fieldMap[$field->getNumber()];
    }

    protected function getReferee(PlanningReferee $referee = null): ?Referee
    {
        if ($referee === null) {
            return null;
        }
        return $this->refereeMap[$referee->getNumber()];
    }

    protected function getPlace(PlanningPlace $planningPlace = null): ?Place
    {
        if ($planningPlace === null) {
            return null;
        }
        $poule = $this->getPoule($planningPlace->getPoule());
        return $poule->getPlace($planningPlace->getNumber());
    }
}
