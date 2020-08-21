<?php

namespace Sports\Output\ConsoleTable;

use LucidFrame\Console\ConsoleTable;
use Sports\League;

class Leagues
{
    /**
     * @param array|League[] $leagues
     */
    public function display( array $leagues ) {
        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name', 'association'));
        uasort( $leagues, function( League $a, League $b ): int {
            if( $a->getAssociation() === $b->getAssociation() ) {
                return $a->getName() < $b->getName() ? -1 : 1;
            }
            return $a->getAssociation()->getName() < $b->getAssociation()->getName() ? -1 : 1;
        });
        foreach( $leagues as $league ) {
            $row = array(
                $league->getId(),
                $league->getName(),
                $league->getAssociation()->getName()
            );
            $table->addRow( $row );
        }
        $table->display();
    }
}
