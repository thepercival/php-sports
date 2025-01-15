<?php

declare(strict_types=1);

namespace Sports\Round\Number;

use DateTimeImmutable;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Game\Together as TogetherGame;
use Sports\Planning\Config as PlanningConfig;
use Sports\Poule;
use Sports\Round\Number as RoundNumber;
use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use SportsPlanning\Game\AgainstGame as AgainstPlanningGame;
use SportsPlanning\Game\AgainstGamePlace as AgainstPlanningGamePlace;
use SportsPlanning\Game\TogetherGamePlace as TogetherPlanningGamePlace;
use SportsPlanning\Game\TogetherGame as TogetherPlanningGame;
use SportsPlanning\Planning;

class PlanningAssigner
{
    public function __construct(protected PlanningScheduler $scheduler)
    {
    }

    public function assignPlanningToRoundNumber(RoundNumber $roundNumber, Planning $planning): void
    {
        $firstBatch = $planning->createFirstBatch();
        $mapper = new PlanningMapper($roundNumber, $planning);
        $defaultStartDateTime = $roundNumber->getCompetition()->getStartDateTime();
        $gameStartDateTime = $this->scheduler->calculateStartDateTimeFromPrevious($roundNumber, $defaultStartDateTime);
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
            $minutesDelta = $planningConfig->getMaxNrOfMinutesPerGame() + $planningConfig->getMinutesBetweenGames();
            $nextGameStartDateTime = $gameStartDateTime->add(new \DateInterval('PT' . $minutesDelta . 'M'));
            $nextGamePeriod = $this->scheduler->createGamePeriod($nextGameStartDateTime, $planningConfig);

            $nextGameStartDateTime = $this->scheduler->moveToFirstAvailableSlot($nextGamePeriod)->getStartDate();

            $this->assignPlanningBatch($nextBatch, $planningConfig, $nextGameStartDateTime, $mapper);
        }
    }

    protected function assignPlanningBatchGames(
        Batch|SelfRefereeBatch $batch,
        DateTimeImmutable $gameStartDateTime,
        PlanningMapper $mapper
    ): void {
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
    ): AgainstGame {
        $game = new AgainstGame(
            $poule,
            $planningGame->getBatchNr(),
            $startDateTime,
            $competitionSport,
            $planningGame->getGameRoundNumber()
        );
        foreach ($planningGame->getPlaces() as $planningGamePlace) {
            $this->createAgainstGamePlace($game, $planningGamePlace, $mapper);
        }
        return $game;
    }

    protected function createAgainstGamePlace(
        AgainstGame $game,
        AgainstPlanningGamePlace $planningGamePlace,
        PlanningMapper $mapper
    ): void {
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
    ): TogetherGame {
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
