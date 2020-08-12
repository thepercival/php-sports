<?php


namespace Sports\Round\Number;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Game;
use Sports\Planning\Config as PlanningConfig;
use Sports\Round\Number as RoundNumber;

class PlanningScheduler
{
    /**
     * @var Period
     */
    protected $blockedPeriod;

    public function __construct(Period $blockedPeriod = null)
    {
        $this->blockedPeriod = $blockedPeriod;
    }

    /**
     * @param RoundNumber $roundNumber
     * @return array|DateTimeImmutable[]
     */
    public function rescheduleGames(RoundNumber $roundNumber): array
    {
        $gameDates = [];
        $gameStartDateTime = $this->getRoundNumberStartDateTime($roundNumber);
        $previousBatchNr = 1;
        $gameDates[] = $gameStartDateTime;

        $games = $roundNumber->getGames(Game::ORDER_BY_BATCH);
        if (count($games) === 0) {
            throw new \Exception("roundnumber has no games", E_ERROR);
        }
        /** @var Game $game */
        foreach ($games as $game) {
            if ($previousBatchNr !== $game->getBatchNr()) {
                $gameStartDateTime = $this->getNextGameStartDateTime($roundNumber->getValidPlanningConfig(), $gameStartDateTime);
                $gameDates[] = $gameStartDateTime;
                $previousBatchNr = $game->getBatchNr();
            }
            $game->setStartDateTime($gameStartDateTime);
        }
        if ($roundNumber->hasNext()) {
            return array_merge($gameDates, $this->rescheduleGames($roundNumber->getNext()));
        }
        return $gameDates;
    }

    public function getRoundNumberStartDateTime(RoundNumber $roundNumber): DateTimeImmutable
    {
        if ($roundNumber->isFirst()) {
            $startDateTime = $roundNumber->getCompetition()->getStartDateTime();
            return $this->addMinutes($startDateTime, 0, $roundNumber->getValidPlanningConfig());
        }
        $previousRoundLastStartDateTime = $roundNumber->getPrevious()->getLastStartDateTime();
        $previousPlanningConfig = $roundNumber->getPrevious()->getValidPlanningConfig();
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
