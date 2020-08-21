<?php

namespace Sports\Output\ConsoleTable;

use LucidFrame\Console\ConsoleTable;
use Sports\Season;

class Seasons
{
    /**
     * @param array|Season[] $seasons
     */
    public function display( array $seasons ) {
        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name', 'start', 'end'));
        foreach( $seasons as $season ) {
            $row = array(
                $season->getId(),
                $season->getName(),
                $season->getStartDateTime()->format( \DateTime::ATOM ),
                $season->getEndDateTime()->format( \DateTime::ATOM )
            );
            $table->addRow( $row );
        }
        $table->display();
    }
}
