<?php

declare(strict_types=1);

namespace Sports\Round\Number;

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
        $planningConfig = $roundNumber->getValidPlanningConfig();
        foreach ($games as $game) {
            if ($previousBatchNr !== $game->getBatchNr()) {
                $minutesDelta = $planningConfig->getMaxNrOfMinutesPerGame() + $planningConfig->getMinutesBetweenGames();
                $nextGameStartDateTime = $gameStartDateTime->modify('+' . $minutesDelta . ' minutes');
                $nextGamePeriod = $this->createGamePeriod($nextGameStartDateTime, $planningConfig);
                $gameStartDateTime = $this->moveToFirstAvailableSlot($nextGamePeriod)->getStartDate();
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
        $planningConfig = $roundNumber->getValidPlanningConfig();
        if ($previousRoundNumber === null) {
            $startDateTime = $roundNumber->getCompetition()->getStartDateTime();
            $endDateTime = $startDateTime->modify('+' . $planningConfig->getMaxNrOfMinutesPerGame() . ' minutes');

            $firstGamePeriod = $this->moveToFirstAvailableSlot(new Period($startDateTime, $endDateTime));
            return $firstGamePeriod->getStartDate();
        }
        $previousRoundLastStartDateTime = $previousRoundNumber->getLastStartDateTime();
        $previousPlanningConfig = $previousRoundNumber->getValidPlanningConfig();
        $previousRoundEnd = $previousRoundLastStartDateTime->modify(
            '+' . $previousPlanningConfig->getMaxNrOfMinutesPerGame() . ' minutes'
        );
        $roundStartDateTime = $previousRoundEnd->modify(
            '+' . $previousPlanningConfig->getMinutesAfter() . ' minutes'
        );
        $gamePeriod = $this->createGamePeriod($roundStartDateTime, $planningConfig);
        $firstGamePeriod = $this->moveToFirstAvailableSlot($gamePeriod);
        return $firstGamePeriod->getStartDate();
    }

    public function createGamePeriod(DateTimeImmutable $startDateTime, PlanningConfig $planningConfig): Period
    {
        return new Period(
            $startDateTime,
            $startDateTime->modify(
                '+' . $planningConfig->getMaxNrOfMinutesPerGame() . ' minutes'
            )
        );
    }


//    public function getNextGameStartDateTime(PlanningConfig $planningConfig, DateTimeImmutable $gameStartDateTime): DateTimeImmutable
//    {
//        $minutes = $planningConfig->getMaxNrOfMinutesPerGame() + $planningConfig->getMinutesBetweenGames();
//        return $this->addMinutes($gameStartDateTime, $minutes, $planningConfig);
//    }


    public function moveToFirstAvailableSlot(
        Period $gamePeriod,
    ): Period {
        $blockedPeriod = $this->getOverlapsingBlockedPeriod($gamePeriod);
        if ($blockedPeriod === null) {
            return $gamePeriod;
        }
        return $this->moveToFirstAvailableSlot(
            new Period(
                clone $blockedPeriod->getEndDate(),
                $blockedPeriod->getEndDate()->modify('+' . $gamePeriod->timeDuration() . ' seconds')
            )
        );
    }

    protected function getOverlapsingBlockedPeriod(Period $gamePeriod): Period|null
    {
        foreach ($this->blockedPeriods as $blockedPeriod) {
            if ($gamePeriod->getStartDate() < $blockedPeriod->getEndDate()
                && $gamePeriod->getEndDate() > $blockedPeriod->getStartDate()) {
                return $blockedPeriod;
            }
        }
        return null;
    }
}
