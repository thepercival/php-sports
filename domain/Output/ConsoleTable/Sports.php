<?php

declare(strict_types=1);

namespace Sports\Output\ConsoleTable;

use LucidFrame\Console\ConsoleTable;
use Sports\Sport;

final class Sports
{
    /**
     * @param list<Sport> $sports
     */
    public function display(array $sports): void
    {
        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name'));
        foreach ($sports as $sport) {
            $row = array( $sport->getId(), $sport->getName() );
            $table->addRow($row);
        }
        $table->display();
    }
}
