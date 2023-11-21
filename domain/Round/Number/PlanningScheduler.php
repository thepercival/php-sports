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
     * @param DateTimeImmutable $competitionStartDateTime
     * @return list<DateTimeImmutable>
     */
    public function rescheduleGames(RoundNumber $roundNumber, DateTimeImmutable $competitionStartDateTime): array
    {
        $gameDates = [];
        $gameStartDateTime = $this->calculateStartDateTimeFromPrevious($roundNumber, $competitionStartDateTime);
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
                $nextGameStartDateTime = $gameStartDateTime->add(new \DateInterval('PT' . $minutesDelta . 'M'));
                $nextGamePeriod = $this->createGamePeriod($nextGameStartDateTime, $planningConfig);
                $gameStartDateTime = $this->moveToFirstAvailableSlot($nextGamePeriod)->getStartDate();
                $gameDates[] = $gameStartDateTime;
                $previousBatchNr = $game->getBatchNr();
            }
            $game->setStartDateTime($gameStartDateTime);
        }
        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null) {
            return array_merge($gameDates, $this->rescheduleGames($nextRoundNumber, $competitionStartDateTime));
        }
        return $gameDates;
    }

    public function calculateStartDateTimeFromPrevious(RoundNumber $roundNumber, DateTimeImmutable $defaultStartDateTime): DateTimeImmutable
    {
        $previousRoundNumber = $roundNumber->getPrevious();
        $planningConfig = $roundNumber->getValidPlanningConfig();
        if ($previousRoundNumber === null) {
            $startDateTime = $defaultStartDateTime;
            $endDateTime = $startDateTime->add(
                new \DateInterval('PT' . $planningConfig->getMaxNrOfMinutesPerGame() . 'M')
            );

            $firstGamePeriod = $this->moveToFirstAvailableSlot(new Period($startDateTime, $endDateTime));
            return $firstGamePeriod->getStartDate();
        }
        $previousRoundLastStartDateTime = $previousRoundNumber->getLastGameStartDateTime();
        $previousPlanningConfig = $previousRoundNumber->getValidPlanningConfig();
        $previousRoundEnd = $previousRoundLastStartDateTime->add(
            new \DateInterval('PT' . $previousPlanningConfig->getMaxNrOfMinutesPerGame() . 'M')
        );
        $roundStartDateTime = $previousRoundEnd->add(
            new \DateInterval('PT' . $previousPlanningConfig->getMinutesAfter() . 'M')
        );
        $gamePeriod = $this->createGamePeriod($roundStartDateTime, $planningConfig);
        $firstGamePeriod = $this->moveToFirstAvailableSlot($gamePeriod);
        return $firstGamePeriod->getStartDate();
    }

    public function createGamePeriod(DateTimeImmutable $startDateTime, PlanningConfig $planningConfig): Period
    {
        return new Period(
            $startDateTime,
            $startDateTime->add(new \DateInterval('PT' . $planningConfig->getMaxNrOfMinutesPerGame() . 'M'))
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
                $blockedPeriod->getEndDate()->add(new \DateInterval('PT' . $gamePeriod->timeDuration() . 'S'))
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
