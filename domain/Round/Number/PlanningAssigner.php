<?php

namespace Sports\Round\Number;

use Sports\Game;
use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use Sports\Planning\Config as PlanningConfig;
use Sports\Round\Number as RoundNumber;
use SportsPlanning\Planning;
use SportsPlanning\Game as PlanningGame;
use Sports\Game\Place as GamePlace;
use SportsPlanning\Game\Place as PlanningGamePlace;
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
use Sports\Competition;
use League\Period\Period;

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
     * @var array|SportConfig[]
     */
    protected $sportConfigMap;
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
     * @param \DateTimeImmutable $gameStartDateTime
     */
    protected function createBatchGames($batch, PlanningConfig $planningConfig, \DateTimeImmutable $gameStartDateTime)
    {
        /** @var PlanningGame $planningGame */
        foreach ($batch->getGames() as $planningGame) {
            $poule = $this->getPoule($planningGame->getPoule());
            $sportConfig = $this->getSportConfig($planningGame->getField()->getSport() );
            $game = new Game($poule, $planningGame->getBatchNr(), $gameStartDateTime, $sportConfig);
            $game->setField($this->getField($planningGame->getField()));
            $game->setReferee($this->getReferee($planningGame->getReferee()));
            $game->setRefereePlace($this->getPlace($planningGame->getRefereePlace()));
            /** @var PlanningGamePlace $planningGamePlace */
            foreach ($planningGame->getPlaces() as $planningGamePlace) {
                new GamePlace(
                    $game, $this->getPlace($planningGamePlace->getPlace()), $planningGamePlace->getHomeaway()
                );
            }
        }
        if ($batch->hasNext()) {
            $nextGameStartDateTime = $this->scheduleService->getNextGameStartDateTime($planningConfig, $gameStartDateTime);
            $this->createBatchGames($batch->getNext(), $planningConfig, $nextGameStartDateTime);
        }
    }

    protected function initResources(RoundNumber $roundNumber, Planning $planning)
    {
        $this->initPoules($roundNumber);
        $this->initSports($roundNumber, $planning);
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

    protected function initSports(RoundNumber $roundNumber, Planning $planning) {
        $this->sportConfigMap = [];
        $sportConfigs = $roundNumber->getSportConfigs();
        foreach( $planning->getSports() as $sport ) {
            $filtered = array_filter( $sportConfigs, function( SportConfig $sportConfig) use($sport): bool {
                return $sportConfig->getFields()->count() === $sport->getFields()->count()
                    && $sportConfig->getNrOfGamePlaces() === $sport->getNrOfGamePlaces()
                    && $sportConfig->getVersusMode() === $sport->getVersusMode();
            });
            $filteredSportConfig = reset($filtered);
            array_splice($sportConfigs, array_search($filteredSportConfig, $sportConfigs, true ) );
            $this->sportConfigMap[$sport->getNumber()] = $filteredSportConfig;
        }
        $this->initFields($planning);
    }

    protected function initFields(Planning $planning)
    {
        $planningFields = $planning->getFields();
        $this->fieldMap = [];
        foreach ($planningFields as $planningField) {
            $sportConfig = $this->getSportConfig($planningField->getSport());
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

    protected function getSportConfig(PlanningSport $planningSport ): SportConfig {
        return $this->sportConfigMap[$planningSport->getNumber()];
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
