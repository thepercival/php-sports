<?php
declare(strict_types=1);

namespace Sports\Output\ConsoleTable;

use DateTimeInterface;
use LucidFrame\Console\ConsoleTable;
use Sports\Season;

class Seasons
{
    /**
     * @param list<Season> $seasons
     */
    public function display(array $seasons): void
    {
        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name', 'start', 'end'));
        foreach ($seasons as $season) {
            $row = array(
                $season->getId(),
                $season->getName(),
                $season->getStartDateTime()->format(DateTimeInterface::ATOM),
                $season->getEndDateTime()->format(DateTimeInterface::ATOM)
            );
            $table->addRow($row);
        }
        $table->display();
    }
}
