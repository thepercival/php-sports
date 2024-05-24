<?php

namespace Sports\Team\Role;

use DateTimeImmutable;
use League\Period\Period;
use Psr\Log\LoggerInterface;
use Sports\Person;
use Sports\Season;
use Sports\Sport\FootballLine;
use Sports\Team;
use Sports\Team\Player;

class Editor
{
    private const string DELTAINTERVAL = 'P1D';

    // protected const MAX_MONTHS_FOR_MERGE = 1;

    public function __construct(protected LoggerInterface $logger)
    {
    }

    public function update(
        Season $season,
        Person $person,
        DateTimeImmutable $dateTime,
        Team $newTeam,
        FootballLine $newLine
    ): Player|null {
        $seasonPeriod = $this->checkPeriod($person, $season, $dateTime);

        // players within season
        $players = $person->getPlayers(null, $season->getPeriod())->toArray();
        uasort($players, function (Player $playerA, Player $playerB): int {
            return $playerA->getStartDateTime() < $playerB->getStartDateTime() ? -1 : 1;
        });
        $players = array_values($players);
        // get overlapping player
        $overlappingPlayer = $this->getOverlapping($players, $dateTime);
        if ($overlappingPlayer !== null) {
            if ($overlappingPlayer->getTeam() == $newTeam) {
                return null;
            }
            if ($overlappingPlayer->getLine() !== $newLine->value) {
                $msg = 'for person "' . $person->getName() . '" ';
                $msg .= 'overlapping playerperiod-line  "' . $overlappingPlayer->getLine(
                    ) . '" is different from line "' . $newLine->value . '"';
                throw new \Exception($msg, E_ERROR);
            }
            $startDateTime = $dateTime->sub(new \DateInterval(self::DELTAINTERVAL));
            $endDateTime = $dateTime->sub(new \DateInterval(self::DELTAINTERVAL));
            $overlappingPlayer->setEndDateTime($endDateTime);
            $newPeriod = new Period($startDateTime, $seasonPeriod->getEndDate());
            return new Player($newTeam, $person, $newPeriod, $newLine->value);
//            if ($overlappingPlayer->getTeam() !== $newTeam) {
//                // new maken en oude stoppen
//                $msg = 'for person "' . $person->getName() . '" ';
//                $msg .= 'new team "' . $newTeam->getName() . '" is different from ';
//                $msg .= 'overlapping player("' . $overlappingPlayer->getTeam()->getName() . '")';
//                throw new \Exception($msg, E_ERROR);
//            }
        }

        // no overlap

        // check period before gamedatetime
        $firstBefore = $this->getFirstBefore($players, $dateTime);
        if ($firstBefore !== null) {
            // try to merge
            if ($firstBefore->getTeam() == $newTeam) {
                if ($firstBefore->getLine() !== $newLine->value) {
                    throw new \Exception('line is different from firstBefore', E_ERROR);
                }
                // merge
                $firstBefore->setEndDateTime($dateTime->add(new \DateInterval(self::DELTAINTERVAL)));
                return $firstBefore;
            }
        }

        // check period after gamedatetime
        $firstAfter = $this->getFirstAfter($players, $dateTime);
        if ($firstAfter !== null) {
            // try to merge
            if ($firstAfter->getTeam() == $newTeam) {
                if ($firstAfter->getLine() !== $newLine->value) {
                    throw new \Exception('line is different from firstAfter', E_ERROR);
                }
                $startDateTime = $dateTime->sub(new \DateInterval(self::DELTAINTERVAL));
                // merge
                $firstAfter->setStartDateTime($startDateTime);
                return $firstAfter;
            }
        }

        $startDateTime = $dateTime->sub(new \DateInterval(self::DELTAINTERVAL));
        // $newPeriod = new Period($gameDateTime->modify('-' . self::DELTA), $gameDateTime->modify('+' . self::DELTA));
        $newPeriod = new Period($startDateTime, $seasonPeriod->getEndDate());

//        if (!$this->isPeriodFree($players, $newPeriod)) {
//            throw new \Exception('period already taken', E_ERROR);
//        }
        return new Player($newTeam, $person, $newPeriod, $newLine->value);
    }

    /**
     * @param list<Player> $players
     * @param DateTimeImmutable $dateTime
     * @return Player|null
     */
    protected function getOverlapping(array $players, DateTimeImmutable $dateTime): Player|null
    {
        foreach ($players as $player) {
            if ($player->getPeriod()->contains($dateTime)) {
                return $player;
            }
        }
        return null;
    }

    /**
     * @param list<Player> $players
     * @param DateTimeImmutable $dateTime
     * @return Player|null
     */
    protected function getFirstBefore(array $players, DateTimeImmutable $dateTime): Player|null
    {
        $firstBefore = null;
        $firstBeforeDateTime = null;
        foreach ($players as $player) {
            $playerStart = $player->getPeriod()->getStartDate();
            if ($playerStart < $dateTime) {
                if ($firstBeforeDateTime === null || $playerStart > $firstBeforeDateTime) {
                    $firstBefore = $player;
                    $firstBeforeDateTime = $playerStart;
                }
            }
        }
        return $firstBefore;
    }

    /**
     * @param list<Player> $players
     * @param DateTimeImmutable $dateTime
     * @return Player|null
     */
    protected function getFirstAfter(array $players, DateTimeImmutable $dateTime): Player|null
    {
        $firstAfter = null;
        $firstAfterDateTime = null;
        foreach ($players as $player) {
            $playerStart = $player->getPeriod()->getStartDate();
            if ($playerStart > $dateTime) {
                if ($firstAfterDateTime === null || $playerStart < $firstAfterDateTime) {
                    $firstAfter = $player;
                    $firstAfterDateTime = $playerStart;
                }
            }
        }
        return $firstAfter;
    }

    protected function checkPeriod(Person $person, Season $season, DateTimeImmutable $dateTime): Period
    {
        $periodWithDelta = $this->getPeriodWithDelta($season->getPeriod());
        if (!$this->withInPeriod($season->getPeriod(), $dateTime)) {
            throw new \Exception(
                'roleEditor: for "' . $person->getName() . '" "' . $dateTime->format(
                    \DateTimeInterface::ISO8601
                ) . '" should be in ' . $periodWithDelta->toIso8601(),
                E_ERROR
            );
        }
        return $this->getPeriodWithDelta($season->getPeriod());
    }

    public function getPeriodWithDelta(Period $period): Period
    {
        $endDate = $period->getEndDate()->sub(new \DateInterval(self::DELTAINTERVAL));
        return new Period($period->getStartDate()->add(new \DateInterval(self::DELTAINTERVAL)),$endDate);
    }

    public function withInPeriod(Period $period, DateTimeImmutable $dateTime): bool
    {
        return $this->getPeriodWithDelta($period)->contains($dateTime);
    }

    public function stop(
        Season $season,
        Person $person,
        DateTimeImmutable $dateTime
    ): Player|null {
        $this->checkPeriod($person, $season, $dateTime);

        // players within season
        $players = $person->getPlayers(null, $season->getPeriod())->toArray();
        uasort($players, function (Player $playerA, Player $playerB): int {
            return $playerA->getStartDateTime() < $playerB->getStartDateTime() ? -1 : 1;
        });
        $players = array_values($players);
        // get overlapping player
        $overlappingPlayer = $this->getOverlapping($players, $dateTime);
        if ($overlappingPlayer === null) {
            return null;
        }
        $endDateTime = $dateTime->sub(new \DateInterval(self::DELTAINTERVAL));
        $overlappingPlayer->setEndDateTime($endDateTime);
        return $overlappingPlayer;
    }

//    /**
//     * @param list<Player> $players
//     * @param Period $newPeriod
//     * @return bool
//     */
//    protected function isPeriodFree(array $players, Period $newPeriod): bool
//    {
//        foreach ($players as $player) {
//            if ($player->getPeriod()->overlaps($newPeriod)) {
//                return false;
//            }
//        }
//        return true;
//    }


//    protected function mergeWithPast(Team $newTeam, Period $newPeriod, int $newLine): void
//    {
//        $players = $this->person->getPlayers($newTeam, null, $newLine);
//
//        $sevenMonthsEarlier = $newPeriod->getStartDate()->modify("-". self::MAX_MONTHS_FOR_MERGE ." months");
//        foreach ($players as $player) {
//            if ($player->getPeriod()->contains($newPeriod)) {
//                continue;
//            }
//            if ($player->getPeriod()->getEndDate() < $sevenMonthsEarlier) {
//                continue;
//            }
//            if ($player->getPeriod()->getStartDate() > $newPeriod->getStartDate()) { // future
//                continue;
//            }
//            $player->setEndDateTime($newPeriod->getEndDate());
//        }
//    }
//
//    protected function updateOverlapses(Team $newTeam, Period $newPeriod): void
//    {
//        $team = null;
//        if ($this->mode === self::MODE_MULTIPLE_TEAMS_OF_A_TYPE_AT_THE_SAME_TIME) {
//            $team = $newTeam;
//        }
//        $playerOverlapses = $this->person->getPlayers($team, $newPeriod);
//        foreach ($playerOverlapses as $playerOverlaps) {
//            if ($playerOverlaps->getPeriod()->contains($newPeriod)
//                && ($this->mode === self::MODE_MULTIPLE_TEAMS_OF_A_TYPE_AT_THE_SAME_TIME
//                    || $newTeam === $playerOverlaps->getTeam())) {
//                continue;
//            }
//            if ($playerOverlaps->getPeriod()->getStartDate() > $newPeriod->getStartDate()) { // future
//                if ($playerOverlaps->getPeriod()->getEndDate()->getTimestamp()
//                    <= $newPeriod->getEndDate()->getTimestamp()) {
//                    $playerOverlaps->setStartDateTime($newPeriod->getStartDate());
//                }
//                continue;
//            }
//            $playerOverlaps->setEndDateTime($newPeriod->getStartDate());
//        }
//    }
//
//    protected function hasOverlapses(Team $newTeam, Period $newPeriod): bool
//    {
//        $team = null;
//        if ($this->mode === self::MODE_MULTIPLE_TEAMS_OF_A_TYPE_AT_THE_SAME_TIME) {
//            $team = $newTeam;
//        }
//        $playerOverlapses = $this->person->getPlayers($team, $newPeriod);
//        return $playerOverlapses->count() > 0;
//    }
//
//    protected function hasLater(Team $newTeam, Period $newPeriod): bool
//    {
//        $team = null;
//        if ($this->mode === self::MODE_MULTIPLE_TEAMS_OF_A_TYPE_AT_THE_SAME_TIME) {
//            $team = $newTeam;
//        }
//
//        $players = $this->person->getPlayers($team);
//        return $players->filter(function (Player $player) use ($newPeriod): bool {
//            return $player->getPeriod()->getStartDate() > $newPeriod->getStartDate();
//        })->count() > 0;
//    }
}
