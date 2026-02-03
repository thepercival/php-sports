<?php

declare(strict_types=1);

namespace Sports\Output\ConsoleTable;

use LucidFrame\Console\ConsoleTable;
use Sports\Team;

final class Teams
{
    /**
     * @param list<Team> $teams
     */
    public function display(array $teams): void
    {
        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name', 'abbreviation', 'competition'));
        foreach ($teams as $team) {
            $row = array(
                $team->id,
                $team->getName(),
                $team->getAbbreviation(),
                $team->getName()
            );
            $table->addRow($row);
        }
        $table->display();
    }
}
