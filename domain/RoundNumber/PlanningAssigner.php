<?php

declare(strict_types=1);

namespace Sports\Round\Number;

use DateTimeImmutable;
use Sports\Competition\CompetitionSport as CompetitionSport;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Game\Together as TogetherGame;
use Sports\Planning\PlanningConfig as PlanningConfig;
use Sports\Poule;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\RefereeInfo;
use SportsPlanning\Batches\Batch;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Poule as PlanningPoule;
use SportsPlanning\Game\AgainstGame as AgainstPlanningGame;
use SportsPlanning\Game\AgainstGamePlace as AgainstPlanningGamePlace;
use SportsPlanning\Game\TogetherGamePlace as TogetherPlanningGamePlace;
use SportsPlanning\Game\TogetherGame as TogetherPlanningGame;
use SportsPlanning\Planning;
use SportsPlanning\PlanningWithMeta;

final class PlanningAssigner
{
    public function __construct(protected PlanningScheduler $scheduler)
    {
    }

    public function assignPlanningToRoundNumber(RoundNumber $roundNumber, PlanningWithMeta $planningWithMeta): void
    {
        $firstBatch = $planningWithMeta->createFirstBatch();
        $planning = $planningWithMeta->getPlanning();
        $mapper = new PlanningMapper($roundNumber, $planning);
        $refereeInfo = $roundNumber->getRefereeInfo();
        $defaultStartDateTime = $roundNumber->getCompetition()->getStartDateTime();
        $gameStartDateTime = $this->scheduler->calculateStartDateTimeFromPrevious($roundNumber, $defaultStartDateTime);
        $this->assignPlanningBatch($firstBatch, $roundNumber, $gameStartDateTime, $planning, $mapper);
    }

    protected function assignPlanningBatch(
        SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules|Batch $batch,
        RoundNumber $roundNumber,
        DateTimeImmutable $gameStartDateTime,
        Planning $planning,
        PlanningMapper $mapper,
    ): void {
        $planningConfig = $roundNumber->getValidPlanningConfig();
        $refereeInfo = $roundNumber->getRefereeInfo();
        $this->assignPlanningBatchGames($batch, $gameStartDateTime, $refereeInfo, $planning, $mapper);
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            $minutesDelta = $planningConfig->getMaxNrOfMinutesPerGame() + $planningConfig->getMinutesBetweenGames();
            $nextGameStartDateTime = $gameStartDateTime->add(new \DateInterval('PT' . $minutesDelta . 'M'));
            $nextGamePeriod = $this->scheduler->createGamePeriod($nextGameStartDateTime, $planningConfig);

            $nextGameStartDateTime = $this->scheduler->moveToFirstAvailableSlot($nextGamePeriod)->startDate;

            $this->assignPlanningBatch($nextBatch, $roundNumber, $nextGameStartDateTime, $planning, $mapper);
        }
    }

    protected function assignPlanningBatchGames(
        SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules|Batch $batch,
        DateTimeImmutable $gameStartDateTime,
        RefereeInfo|null $refereeInfo,
        Planning $planning,
        PlanningMapper $mapper
    ): void {
        foreach ($batch->getGames() as $planningGame) {
            $game = $this->createGameFromPlanningGame($planningGame, $gameStartDateTime, $planning, $mapper);
            $game->setField($mapper->getField($planningGame->getField()));
            if( $refereeInfo !== null ) {
                if ( $refereeInfo->nrOfReferees > 0 ) {
                    $refereeNr = $planningGame->getRefereeNr();
                    if( $refereeNr !== null ) {
                        $planningReferee = $planning->getReferee($refereeNr);
                        $game->setReferee($mapper->getReferee($planningReferee));
                    }
                }
                else if( $refereeInfo->selfRefereeInfo !== null ) {
                    $refereePlaceUniqueIndex = $planningGame->getRefereePlaceUniqueIndex();
                    if( $refereePlaceUniqueIndex !== null ) {
                        $planningPlace = $planning->getPlace($refereePlaceUniqueIndex);
                        $game->setRefereePlace($mapper->getRefereePlace($planning, $planningPlace));
                    }
                }
            }
        }
    }

    protected function createGameFromPlanningGame(
        AgainstPlanningGame|TogetherPlanningGame $planningGame,
        DateTimeImmutable $gameStartDateTime,
        Planning $planning,
        PlanningMapper $mapper
    ): AgainstGame|TogetherGame {
        $planningPoule = $planning->getPoule($planningGame->pouleNr);
        $poule = $mapper->getPoule($planningPoule);
        $planningSport = $planning->getSport($planningGame->getField()->sportNr);
        $competitionSport = $mapper->getCompetitionSport($planningSport);
        if ($planningGame instanceof AgainstPlanningGame) {
            return $this->createAgainstGame($poule, $planningGame, $gameStartDateTime, $competitionSport, $planning, $mapper);
        }
        return $this->createTogetherGame($poule, $planningGame, $gameStartDateTime, $competitionSport, $planning, $mapper);
    }

    protected function createAgainstGame(
        Poule $poule,
        AgainstPlanningGame $planningGame,
        DateTimeImmutable $startDateTime,
        CompetitionSport $competitionSport,
        Planning $planning,
        PlanningMapper $mapper
    ): AgainstGame {
        $game = new AgainstGame(
            $poule,
            $planningGame->getBatchNr(),
            $startDateTime,
            $competitionSport,
            $planningGame->cycleNr,
            $planningGame->cyclePartNr,
        );
        $planningPoule = $planning->getPoule($planningGame->pouleNr);
        foreach ($planningGame->getGamePlaces() as $planningGamePlace) {
            $this->createAgainstGamePlace($game, $planning, $planningPoule, $planningGamePlace, $mapper);
        }
        return $game;
    }

    protected function createAgainstGamePlace(
        AgainstGame $game,
        Planning $planning,
        PlanningPoule $planningPoule,
        AgainstPlanningGamePlace $planningGamePlace,
        PlanningMapper $mapper
    ): void {
        $planningPlace = $planningPoule->getPlace($planningGamePlace->placeNr);
        new AgainstGamePlace(
            $game,
            $mapper->getPlace($planning, $planningPlace ),
            $planningGamePlace->side
        );
    }

    protected function createTogetherGame(
        Poule $poule,
        TogetherPlanningGame $planningGame,
        DateTimeImmutable $startDateTime,
        CompetitionSport $competitionSport,
        Planning $planning,
        PlanningMapper $mapper
    ): TogetherGame {
        $game = new TogetherGame($poule, $planningGame->getBatchNr(), $startDateTime, $competitionSport);
        $planningPoule = $planning->getPoule($planningGame->pouleNr);
        foreach ($planningGame->getGamePlaces() as $planningGamePlace) {
            $this->createTogetherGamePlace($game, $planning, $planningPoule, $planningGamePlace, $mapper);
        }
        return $game;
    }

    protected function createTogetherGamePlace(
        TogetherGame $game,
        Planning $planning,
        PlanningPoule $planningPoule,
        TogetherPlanningGamePlace $planningGamePlace,
        PlanningMapper $mapper
    ): TogetherGamePlace {

        $planningPlace = $planningPoule->getPlace($planningGamePlace->placeNr);
        return new TogetherGamePlace(
            $game,
            $mapper->getPlace($planning, $planningPlace ),
            $planningGamePlace->cycleNr
        );
    }
}
