<?php

namespace Sports\Output\ConsoleTable;

use LucidFrame\Console\ConsoleTable;
use Sports\Sport;

class Sports
{
    /**
     * @param array|Sport[] $sports
     *
     * @return void
     */
    public function display( array $sports ): void {
        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name'));
        foreach( $sports as $sport ) {
            $row = array( $sport->getId(), $sport->getName() );
            $table->addRow( $row );
        }
        $table->display();
    }
}
