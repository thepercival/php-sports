<?php

namespace Sports\Output\ConsoleTable;

use LucidFrame\Console\ConsoleTable;
use Sports\Team;

class Teams
{
    /**
     * @param array|Team[] $teams
     */
    public function display( array $teams ) {
        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name', 'abbreviation', 'competition'));
        foreach( $teams as $team ) {
            $row = array(
                $team->getId(),
                $team->getName(),
                $team->getAbbreviation(),
                $team->getName()
            );
            $table->addRow( $row );
        }
        $table->display();
    }
}
