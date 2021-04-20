<?php
declare(strict_types=1);

namespace Sports\Round\Number;

use DateTimeImmutable;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Poule;
use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use Sports\Planning\Config as PlanningConfig;
use Sports\Round\Number as RoundNumber;
use SportsPlanning\Planning;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Place\Together as TogetherGamePlace;
use SportsPlanning\Game\Against as AgainstPlanningGame;
use SportsPlanning\Game\Place\Against as AgainstPlanningGamePlace;
use SportsPlanning\Game\Together as TogetherPlanningGame;
use SportsPlanning\Game\Place\Together as TogetherPlanningGamePlace;

class PlanningAssigner
{
    public function __construct(protected PlanningScheduler $scheduler)
    {
    }

    public function assignPlanningToRoundNumber(RoundNumber $roundNumber, Planning $planning): void
    {
        $mapper = new PlanningMapper($roundNumber, $planning->getInput());
        $firstBatch = $planning->createFirstBatch();
        $gameStartDateTime = $this->scheduler->getRoundNumberStartDateTime($roundNumber);
        $planningConfig = $roundNumber->getValidPlanningConfig();
        $this->assignPlanningBatch($firstBatch, $planningConfig, $gameStartDateTime, $mapper);
    }

    protected function assignPlanningBatch(
        Batch|SelfRefereeBatch $batch,
        PlanningConfig $planningConfig,
        DateTimeImmutable $gameStartDateTime,
        PlanningMapper $mapper,
    ): void {
        $this->assignPlanningBatchGames($batch, $gameStartDateTime, $mapper);
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            $nextGameStartDateTime = $this->scheduler->getNextGameStartDateTime($planningConfig, $gameStartDateTime);
            $this->assignPlanningBatch($nextBatch, $planningConfig, $nextGameStartDateTime, $mapper);
        }
    }

    protected function assignPlanningBatchGames(
        Batch|SelfRefereeBatch $batch,
        DateTimeImmutable $gameStartDateTime,
        PlanningMapper $mapper): void
    {
        foreach ($batch->getGames() as $planningGame) {
            $game = $this->createGameFromPlanningGame($planningGame, $gameStartDateTime, $mapper);
            $game->setField($mapper->getField($planningGame->getField()));
            $game->setReferee($mapper->getReferee($planningGame->getReferee()));
            $game->setRefereePlace($mapper->getRefereePlace($planningGame->getRefereePlace()));
        }
    }

    protected function createGameFromPlanningGame(
        AgainstPlanningGame|TogetherPlanningGame $planningGame,
        DateTimeImmutable $gameStartDateTime,
        PlanningMapper $mapper
    ): AgainstGame|TogetherGame {
        $poule = $mapper->getPoule($planningGame->getPoule());
        $competitionSport = $mapper->getCompetitionSport($planningGame->getSport());
        if ($planningGame instanceof AgainstPlanningGame) {
            return $this->createAgainstGame($poule, $planningGame, $gameStartDateTime, $competitionSport, $mapper);
        }
        return $this->createTogetherGame($poule, $planningGame, $gameStartDateTime, $competitionSport, $mapper);
    }

    protected function createAgainstGame(
        Poule $poule,
        AgainstPlanningGame $planningGame,
        DateTimeImmutable $startDateTime,
        CompetitionSport $competitionSport,
        PlanningMapper $mapper
    ): AgainstGame
    {
        $game = new AgainstGame($poule, $planningGame->getBatchNr(), $startDateTime, $competitionSport);
        foreach ($planningGame->getPlaces() as $planningGamePlace) {
            $this->createAgainstGamePlace($game, $planningGamePlace, $mapper);
        }
        return $game;
    }

    protected function createAgainstGamePlace(
        AgainstGame $game,
        AgainstPlanningGamePlace $planningGamePlace,
        PlanningMapper $mapper
    ): void
    {
        new AgainstGamePlace(
            $game,
            $mapper->getPlace($planningGamePlace->getPlace()),
            $planningGamePlace->getSide()
        );
    }

    protected function createTogetherGame(
        Poule $poule,
        TogetherPlanningGame $planningGame,
        DateTimeImmutable $startDateTime,
        CompetitionSport $competitionSport,
        PlanningMapper $mapper
    ): TogetherGame
    {
        $game = new TogetherGame($poule, $planningGame->getBatchNr(), $startDateTime, $competitionSport);
        foreach ($planningGame->getPlaces() as $planningGamePlace) {
            $this->createTogetherGamePlace($game, $planningGamePlace, $mapper);
        }
        return $game;
    }

    protected function createTogetherGamePlace(
        TogetherGame $game,
        TogetherPlanningGamePlace $planningGamePlace,
        PlanningMapper $mapper
    ): TogetherGamePlace {
        return new TogetherGamePlace(
            $game,
            $mapper->getPlace($planningGamePlace->getPlace()),
            $planningGamePlace->getGameRoundNumber()
        );
    }
}
