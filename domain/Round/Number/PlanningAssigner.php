<?php

namespace Sports\Round\Number;

use Sports\Game;
use SportsPlanning\Batch;
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
use Sports\Field;
use SportsPlanning\Field as PlanningField;
use Sports\Referee;
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

    protected function createBatchGames(Batch $batch, PlanningConfig $planningConfig, \DateTimeImmutable $gameStartDateTime)
    {
        /** @var PlanningGame $planningGame */
        foreach ($batch->getGames() as $planningGame) {
            $poule = $this->getPoule($planningGame->getPoule());
            $game = new Game($poule, $planningGame->getBatchNr(), $gameStartDateTime);
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
        $this->initFieldsAndReferees($roundNumber, $planning);
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


    protected function initFieldsAndReferees(RoundNumber $roundNumber, Planning $planning)
    {
        $games = $planning->getGames(Game::ORDER_BY_BATCH);
        if (!$roundNumber->isFirst()) {
            $games = array_reverse($games);
        }
        $this->initFields($games, $roundNumber->getCompetition()->getFields());
        $this->initReferees($games, $roundNumber->getCompetition()->getReferees()->toArray());
    }

    protected function initFields(array $games, array $fields)
    {
        $this->fieldMap = [];
        foreach ($games as $game) {
            if (array_key_exists($game->getField()->getNumber(), $this->fieldMap)) {
                continue;
            }
            $this->fieldMap[$game->getField()->getNumber()] = array_shift($fields);
            if (count($fields) === 0) {
                break;
            }
        }
    }

    /**
     * @param array|PlanningGame[] $games
     * @param array|Referee[] $referees
     */
    protected function initReferees(array $games, array $referees)
    {
        $this->refereeMap = [];
        if (count($referees) === 0) {
            return;
        }
        foreach ($games as $game) {
            if ($game->getReferee() === null) {
                return;
            }
            if (array_key_exists($game->getReferee()->getNumber(), $this->refereeMap)) {
                continue;
            }
            $this->refereeMap[$game->getReferee()->getNumber()] = array_shift($referees);
            if (count($referees) === 0) {
                break;
            }
        }
    }

    protected function getPoule(PlanningPoule $poule): Poule
    {
        return $this->poules[$poule->getNumber() - 1];
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
