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
    public function __construct(protected Period|null $blockedPeriod = null)
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
            return $this->addMinutes($startDateTime, 0, $roundNumber->getValidPlanningConfig());
        }
        $previousRoundLastStartDateTime = $previousRoundNumber->getLastStartDateTime();
        $previousPlanningConfig = $previousRoundNumber->getValidPlanningConfig();
        $minutes = $previousPlanningConfig->getMaxNrOfMinutesPerGame() + $previousPlanningConfig->getMinutesAfter();
        return $this->addMinutes($previousRoundLastStartDateTime, $minutes, $previousPlanningConfig);
    }

    public function getNextGameStartDateTime(PlanningConfig $planningConfig, DateTimeImmutable $gameStartDateTime): DateTimeImmutable
    {
        $minutes = $planningConfig->getMaxNrOfMinutesPerGame() + $planningConfig->getMinutesBetweenGames();
        return $this->addMinutes($gameStartDateTime, $minutes, $planningConfig);
    }

    protected function addMinutes(DateTimeImmutable $dateTime, int $minutes, PlanningConfig $planningConfig): DateTimeImmutable
    {
        $newStartDateTime = $dateTime->modify("+" . $minutes . " minutes");
        if ($this->blockedPeriod !== null) {
            $newEndDateTime = $newStartDateTime->modify("+" . $planningConfig->getMaxNrOfMinutesPerGame() . " minutes");
            if ($newStartDateTime < $this->blockedPeriod->getEndDate() && $newEndDateTime > $this->blockedPeriod->getStartDate()) {
                $newStartDateTime = clone $this->blockedPeriod->getEndDate();
            }
        }
        return $newStartDateTime;
    }
}
