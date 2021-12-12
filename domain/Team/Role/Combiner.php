<?php


namespace Sports\Team\Role;


use League\Period\Period;
use Sports\Person;
use Sports\Team;
use Sports\Team\Player;

class Combiner
{
    protected Person $person;

    protected int $mode;

    public const MODE_ONE_TEAM_OF_A_TYPE_AT_THE_SAME_TIME = 1;
    public const MODE_MULTIPLE_TEAMS_OF_A_TYPE_AT_THE_SAME_TIME = 2;

    protected const MAX_MONTHS_FOR_MERGE = 7;

    public function __construct( Person $person, int $mode = null )
    {
        $this->mode = $mode !== null ? $mode : self::MODE_ONE_TEAM_OF_A_TYPE_AT_THE_SAME_TIME;
        $this->person = $person;
    }

    public function combineWithPast( Team $newTeam, Period $newPeriod, int $newLine ): void {

        $this->mergeWithPast( $newTeam, $newPeriod, $newLine );
        $this->updateOverlapses( $newTeam, $newPeriod );
        if( !$this->hasOverlapses( $newTeam, $newPeriod ) && !$this->hasLater( $newTeam, $newPeriod ) ) {
            new Player( $newTeam, $this->person, $newPeriod, $newLine );
        }
    }

    protected function mergeWithPast( Team $newTeam, Period $newPeriod, int $newLine ): void {
        $players = $this->person->getPlayers( $newTeam, null, $newLine );

        $sevenMonthsEarlier = $newPeriod->getStartDate()->modify("-". self::MAX_MONTHS_FOR_MERGE ." months");
        foreach( $players as $player ) {
            if( $player->getPeriod()->contains( $newPeriod ) ) {
                continue;
            }
            if( $player->getPeriod()->getEndDate() < $sevenMonthsEarlier ) {
                continue;
            }
            if( $player->getPeriod()->getStartDate() > $newPeriod->getStartDate() ) { // future
                continue;
            }
            $player->setEndDateTime( $newPeriod->getEndDate() );
        }
    }

    protected function updateOverlapses( Team $newTeam, Period $newPeriod ): void {

        $team = null;
        if( $this->mode === self::MODE_MULTIPLE_TEAMS_OF_A_TYPE_AT_THE_SAME_TIME ) {
            $team = $newTeam;
        }
        $playerOverlapses = $this->person->getPlayers( $team, $newPeriod );
        foreach( $playerOverlapses as $playerOverlaps ) {
            if( $playerOverlaps->getPeriod()->contains( $newPeriod )
                && ( $this->mode === self::MODE_MULTIPLE_TEAMS_OF_A_TYPE_AT_THE_SAME_TIME
                || $newTeam === $playerOverlaps->getTeam() ) ) {
                continue;
            }
            if( $playerOverlaps->getPeriod()->getStartDate() > $newPeriod->getStartDate() ) { // future
                if( $playerOverlaps->getPeriod()->getEndDate()->getTimestamp()
                    <= $newPeriod->getEndDate()->getTimestamp() ) {
                    $playerOverlaps->setStartDateTime( $newPeriod->getStartDate() );
                }
                continue;
            }
            $playerOverlaps->setEndDateTime( $newPeriod->getStartDate() );
        }
    }

    protected function hasOverlapses( Team $newTeam, Period $newPeriod ): bool {

        $team = null;
        if( $this->mode === self::MODE_MULTIPLE_TEAMS_OF_A_TYPE_AT_THE_SAME_TIME ) {
            $team = $newTeam;
        }
        $playerOverlapses = $this->person->getPlayers( $team, $newPeriod );
        return $playerOverlapses->count() > 0;
    }

    protected function hasLater( Team $newTeam, Period $newPeriod ): bool {

        $team = null;
        if( $this->mode === self::MODE_MULTIPLE_TEAMS_OF_A_TYPE_AT_THE_SAME_TIME ) {
            $team = $newTeam;
        }

        $players = $this->person->getPlayers( $team );
        return $players->filter( function( Player $player ) use ($newPeriod): bool {
                return $player->getPeriod()->getStartDate() > $newPeriod->getStartDate();
            })->count() > 0;
    }
}