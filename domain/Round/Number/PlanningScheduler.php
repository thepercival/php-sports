<?php

declare(strict_types=1);

namespace Sports\Round\Number;

use Ahamed\JsPhp\JsArray;
use DateTimeImmutable;
use League\Period\Period;
use Sports\Game\Order as GameOrder;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Against as AgainstGame;
use Sports\Planning\Config as PlanningConfig;
use Sports\Round\Number as RoundNumber;

class PlanningScheduler
{
    /**
     * @param list<Period> $blockedPeriods
     */
    public function __construct(protected array $blockedPeriods)
    {
    }

    /**
     * @param RoundNumber $roundNumber
     * @return list<DateTimeImmutable>
     */
    public function rescheduleGames(RoundNumber $roundNumber): array
    {
        $gameDates = [];
        $gameStartDateTime = $this->getRoundNumberStartDateTime($roundNumber);
        $previousBatchNr = 1;
        $gameDates[] = $gameStartDateTime;

        $games = $roundNumber->getGames(GameOrder::ByBatch);
        if (count($games) === 0) {
            throw new \Exception("roundnumber has no games", E_ERROR);
        }
        foreach ($games as $game) {
            if ($previousBatchNr !== $game->getBatchNr()) {
                $gameStartDateTime = $this->getNextGameStartDateTime($roundNumber->getValidPlanningConfig(), $gameStartDateTime);
                $gameDates[] = $gameStartDateTime;
                $previousBatchNr = $game->getBatchNr();
            }
            $game->setStartDateTime($gameStartDateTime);
        }
        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null) {
            return array_merge($gameDates, $this->rescheduleGames($nextRoundNumber));
        }
        return $gameDates;
    }

    public function getRoundNumberStartDateTime(RoundNumber $roundNumber): DateTimeImmutable
    {
        $previousRoundNumber = $roundNumber->getPrevious();
        if ($previousRoundNumber === null) {
            $startDateTime = $roundNumber->getCompetition()->getStartDateTime();
            $maxNrOfMinutesPerGame = $roundNumber->getValidPlanningConfig()->getMaxNrOfMinutesPerGame();
            $gameEndDateTime = $startDateTime->modify('+' . $maxNrOfMinutesPerGame . ' minutes');
            $newGamePeriod = new Period($startDateTime, $gameEndDateTime);
            return $this->calculateGameStartDatetime($newGamePeriod);
        }
        $previousRoundLastStartDateTime = $previousRoundNumber->getLastStartDateTime();
        $previousPlanningConfig = $previousRoundNumber->getValidPlanningConfig();
        $minutes = $previousPlanningConfig->getMaxNrOfMinutesPerGame() + $previousPlanningConfig->getMinutesAfter();
        $startDateTime = $previousRoundLastStartDateTime->modify('+' . $minutes . ' minutes');
        $maxNrOfMinutesPerGame = $roundNumber->getValidPlanningConfig()->getMaxNrOfMinutesPerGame();
        $gameEndDateTime = $startDateTime->modify('+' . $maxNrOfMinutesPerGame . ' minutes');
        $newGamePeriod = new Period($startDateTime, $gameEndDateTime);
        return $this->calculateGameStartDatetime($newGamePeriod);
    }

    public function getNextGameStartDateTime(
        PlanningConfig $planningConfig,
        DateTimeImmutable $gameStartDateTime
    ): DateTimeImmutable {
        $maxNrOfMinutesPerGame = $planningConfig->getMaxNrOfMinutesPerGame();
        $minutes = $maxNrOfMinutesPerGame + $planningConfig->getMinutesBetweenGames();
        $newGameStartDateTime = $gameStartDateTime->modify('+' . $minutes . ' minutes');
        $newGameEndDateTime = $newGameStartDateTime->modify('+' . $maxNrOfMinutesPerGame . ' minutes');
        $newGamePeriod = new Period($newGameStartDateTime, $newGameEndDateTime);
        return $this->calculateGameStartDatetime($newGamePeriod);
    }

//    protected function addMinutes(
//        DateTimeImmutable $dateTime,
//        int $minutes,
//        int $maxNrOfMinutesPerGame
//    ): DateTimeImmutable {
//        $newStartDateTime = $dateTime->modify('+' . $minutes . ' minutes');
//        // $maxNrOfMinutesPerGame = $planningConfig->getMaxNrOfMinutesPerGame();
//        // $newEndDateTime = $newStartDateTime->modify("+" . $maxNrOfMinutesPerGame . " minutes");
//        return $this->calculateNewStartDatetime($newStartDateTime, $maxNrOfMinutesPerGame);
//    }

    protected function calculateGameStartDatetime(Period $gamePeriod): DateTimeImmutable
    {
        // $endDateTime = $startDateTime->modify("+" . $minutesPerGame . " minutes");
        $blockedPeriod = $this->getOverlapsingBlockedPeriod($gamePeriod);
        if ($blockedPeriod === null) {
            return $gamePeriod->getStartDate();
        }

        $startDateTime = $blockedPeriod->getEndDate();
        $endDateTime = $startDateTime->modify("+" . $gamePeriod->timeDuration() . " seconds");
        return $this->calculateGameStartDatetime(new Period($startDateTime, $endDateTime));
    }

    protected function getOverlapsingBlockedPeriod(Period $period): Period|null
    {
        foreach ($this->blockedPeriods as $blockedPeriod) {
            if ($period->overlaps($blockedPeriod)) {
                return $blockedPeriod;
            }
        }
        return null;
    }
}
