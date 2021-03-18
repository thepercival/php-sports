<?php

namespace Sports\Output\ConsoleTable;

use LucidFrame\Console\ConsoleTable;
use Sports\Competitor\Team as TeamCompetitor;

class TeamCompetitors
{
    /**
     * @param array|TeamCompetitor[] $teamCompetitors
     *
     * @return void
     */
    public function display( array $teamCompetitors ): void {
        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'league', 'season', 'pouleNr', 'placeNr', 'team'));
        uasort( $teamCompetitors, function( TeamCompetitor $a, TeamCompetitor $b ): int {
            if( $a->getPouleNr() === $b->getPouleNr() ) {
                return $a->getPlaceNr() < $b->getPlaceNr() ? -1 : 1;
            }
            return $a->getPouleNr() < $b->getPouleNr() ? -1 : 1;
        });
        foreach( $teamCompetitors as $teamCompetitor ) {
            $row = array(
                $teamCompetitor->getId(),
                $teamCompetitor->getCompetition()->getLeague()->getName(),
                $teamCompetitor->getCompetition()->getSeason()->getName(),
                $teamCompetitor->getPouleNr(),
                $teamCompetitor->getPlaceNr(),
                $teamCompetitor->getTeam()->getName()
            );
            $table->addRow( $row );
        }
        $table->display();
    }
}
