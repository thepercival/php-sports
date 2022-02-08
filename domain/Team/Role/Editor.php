<?php

namespace Sports\Team\Role;

use League\Period\Period;
use Sports\Person;
use Sports\Season;
use Sports\Sport\FootballLine;
use Sports\Team;
use Sports\Team\Player;

class Editor
{
    private const DELTA = '1 days';

    // protected const MAX_MONTHS_FOR_MERGE = 1;

    public function __construct()
    {
    }

    public function update(
        Season $season,
        Person $person,
        \DateTimeImmutable $gameDateTime,
        Team $newTeam,
        FootballLine $newLine
    ): Player|null {
        $checkPeriod = new Period(
            $season->getPeriod()->getStartDate()->modify('+' . self::DELTA),
            $season->getPeriod()->getEndDate()->modify('-' . self::DELTA)
        );
        if (!$checkPeriod->contains($gameDateTime)) {
            throw new \Exception(
                'gamedatetime should be at least ' . self::DELTA . ' from start and end of season',
                E_ERROR
            );
        }

        // players within season
        $players = $person->getPlayers(null, $season->getPeriod())->toArray();
        uasort($players, function (Player $playerA, Player $playerB): int {
            return $playerA->getStartDateTime() < $playerB->getStartDateTime() ? -1 : 1;
        });
        $players = array_values($players);
        // get overlapping player
        $overlappingPlayer = $this->getOverlapping($players, $gameDateTime);
        if ($overlappingPlayer !== null) {
            if ($overlappingPlayer->getTeam() !== $newTeam) {
                $msg = 'for person "' . $person->getName() . '" ';
                $msg .= 'new team "' . $newTeam->getName() . '" is different from ';
                $msg .= 'overlapping player("' . $overlappingPlayer->getTeam()->getName() . '")';
                throw new \Exception($msg, E_ERROR);
            }
            if ($overlappingPlayer->getLine() !== $newLine->value) {
                throw new \Exception('line is different from overlapping', E_ERROR);
            }
            return null;
        }

        // no overlap

        // check period before gamedatetime
        $firstBefore = $this->getFirstBefore($players, $gameDateTime);
        if ($firstBefore !== null) {
            // try to merge
            if ($firstBefore->getTeam() == $newTeam) {
                if ($firstBefore->getLine() !== $newLine->value) {
                    throw new \Exception('line is different from firstBefore', E_ERROR);
                }
                // merge
                $firstBefore->setEndDateTime($gameDateTime->modify('+' . self::DELTA));
                return $firstBefore;
            }
        }

        // check period after gamedatetime
        $firstAfter = $this->getFirstAfter($players, $gameDateTime);
        if ($firstAfter !== null) {
            // try to merge
            if ($firstAfter->getTeam() == $newTeam) {
                if ($firstAfter->getLine() !== $newLine->value) {
                    throw new \Exception('line is different from firstAfter', E_ERROR);
                }
                // merge
                $firstAfter->setStartDateTime($gameDateTime->modify('-' . self::DELTA));
                return $firstAfter;
            }
        }

        $newPeriod = new Period($gameDateTime->modify('-' . self::DELTA), $gameDateTime->modify('+' . self::DELTA));
//        if (!$this->isPeriodFree($players, $newPeriod)) {
//            throw new \Exception('period already taken', E_ERROR);
//        }
        return new Player($newTeam, $person, $newPeriod, $newLine->value);
    }

    /**
     * @param list<Player> $players
     * @param \DateTimeImmutable $dateTime
     * @return Player|null
     */
    protected function getOverlapping(array $players, \DateTimeImmutable $dateTime): Player|null
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
     * @param \DateTimeImmutable $dateTime
     * @return Player|null
     */
    protected function getFirstBefore(array $players, \DateTimeImmutable $dateTime): Player|null
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
     * @param \DateTimeImmutable $dateTime
     * @return Player|null
     */
    protected function getFirstAfter(array $players, \DateTimeImmutable $dateTime): Player|null
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
