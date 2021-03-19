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
    protected PlanningMapper $mapper;

    public function __construct(protected PlanningScheduler $scheduler)
    {
    }

    public function assignPlanningToRoundNumber(RoundNumber $roundNumber, Planning $planning): void
    {
        $this->mapper = new PlanningMapper($roundNumber, $planning);
        $firstBatch = $planning->createFirstBatch();
        $gameStartDateTime = $this->scheduler->getRoundNumberStartDateTime($roundNumber);
        $planningConfig = $roundNumber->getValidPlanningConfig();
        $this->assignPlanningBatch($firstBatch, $planningConfig, $gameStartDateTime);
    }

    protected function assignPlanningBatch(
        Batch|SelfRefereeBatch $batch,
        PlanningConfig $planningConfig,
        DateTimeImmutable $gameStartDateTime
    ): void {
        $this->assignPlanningBatchGames($batch, $gameStartDateTime);
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            $nextGameStartDateTime = $this->scheduler->getNextGameStartDateTime($planningConfig, $gameStartDateTime);
            $this->assignPlanningBatch($nextBatch, $planningConfig, $nextGameStartDateTime);
        }
    }

    protected function assignPlanningBatchGames(Batch|SelfRefereeBatch $batch, DateTimeImmutable $gameStartDateTime): void
    {
        foreach ($batch->getGames() as $planningGame) {
            $game = $this->createGameFromPlanningGame($planningGame, $gameStartDateTime);
            $game->setField($this->mapper->getField($planningGame->getField()));
            $game->setReferee($this->mapper->getReferee($planningGame->getReferee()));
            $game->setRefereePlace($this->mapper->getRefereePlace($planningGame->getRefereePlace()));
        }
    }

    protected function createGameFromPlanningGame(
        AgainstPlanningGame|TogetherPlanningGame $planningGame,
        DateTimeImmutable $gameStartDateTime
    ): AgainstGame|TogetherGame {
        $poule = $this->mapper->getPoule($planningGame->getPoule());
        $competitionSport = $this->mapper->getCompetitionSport($planningGame->getSport());
        if ($planningGame instanceof AgainstPlanningGame) {
            return $this->createAgainstGame($poule, $planningGame, $gameStartDateTime, $competitionSport);
        }
        return $this->createTogetherGame($poule, $planningGame, $gameStartDateTime, $competitionSport);
    }

    protected function createAgainstGame(
        Poule $poule,
        AgainstPlanningGame $planningGame,
        DateTimeImmutable $startDateTime,
        CompetitionSport $competitionSport
    ): AgainstGame
    {
        $game = new AgainstGame($poule, $planningGame->getBatchNr(), $startDateTime, $competitionSport);
        foreach ($planningGame->getPlaces() as $planningGamePlace) {
            $this->createAgainstGamePlace($game, $planningGamePlace);
        }
        return $game;
    }

    protected function createAgainstGamePlace(
        AgainstGame $game,
        AgainstPlanningGamePlace $planningGamePlace
    ): void
    {
        new AgainstGamePlace(
            $game,
            $this->mapper->getPlace($planningGamePlace->getPlace()),
            $planningGamePlace->getSide()
        );
    }

    protected function createTogetherGame(
        Poule $poule,
        TogetherPlanningGame $planningGame,
        DateTimeImmutable $startDateTime,
        CompetitionSport $competitionSport
    ): TogetherGame
    {
        $game = new TogetherGame($poule, $planningGame->getBatchNr(), $startDateTime, $competitionSport);
        foreach ($planningGame->getPlaces() as $planningGamePlace) {
            $this->createTogetherGamePlace($game, $planningGamePlace);
        }
        return $game;
    }

    protected function createTogetherGamePlace(
        TogetherGame $game,
        TogetherPlanningGamePlace $planningGamePlace
    ) {
        new TogetherGamePlace(
            $game,
            $this->mapper->getPlace($planningGamePlace->getPlace()),
            $planningGamePlace->getGameRoundNumber()
        );
    }
}
