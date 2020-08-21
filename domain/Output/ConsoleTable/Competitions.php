<?php

namespace Sports\Output\ConsoleTable;

use LucidFrame\Console\ConsoleTable;
use Sports\Competition;

class Competitions
{
    /**
     * @param array|Competition[] $competitions
     */
    public function display( array $competitions ) {
        $table = new ConsoleTable();
        $table->setHeaders(array('Id', 'league', 'season', 'startdatetime', 'association'));
        uasort( $competitions, function( Competition $a, Competition $b ): int {
            if( $a->getLeague()->getAssociation() === $b->getLeague()->getAssociation() ) {
                return $a->getLeague()->getName() < $b->getLeague()->getName() ? -1 : 1;
            }
            return $a->getLeague()->getAssociation()->getName() < $b->getLeague()->getAssociation()->getName() ? -1 : 1;
        });
        foreach( $competitions as $competition ) {
            $row = array(
                $competition->getId(),
                $competition->getLeague()->getName(),
                $competition->getSeason()->getName(),
                $competition->getStartDateTime()->format( \DateTime::ATOM ),
                $competition->getLeague()->getAssociation()->getName()
            );
            $table->addRow( $row );
        }
        $table->display();
    }
}
