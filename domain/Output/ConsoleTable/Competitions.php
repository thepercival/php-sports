<?php

declare(strict_types=1);

namespace Sports\Output\ConsoleTable;

use DateTimeInterface;
use LucidFrame\Console\ConsoleTable;
use Sports\Competition;

class Competitions
{
    /**
     * @param list<Competition> $competitions
     */
    public function display(array $competitions): void
    {
        $table = new ConsoleTable();
        $table->setHeaders(array('Id', 'league', 'season', 'startdatetime', 'association'));
        usort($competitions, function (Competition $a, Competition $b): int {
            if ($a->getLeague()->getAssociation() === $b->getLeague()->getAssociation()) {
                return $a->getLeague()->getName() < $b->getLeague()->getName() ? -1 : 1;
            }
            return $a->getLeague()->getAssociation()->getName() < $b->getLeague()->getAssociation()->getName() ? -1 : 1;
        });
        foreach ($competitions as $competition) {
            $row = array(
                $competition->getId(),
                $competition->getLeague()->getName(),
                $competition->getSeason()->getName(),
                $competition->getStartDateTime()->format(DateTimeInterface::ATOM),
                $competition->getLeague()->getAssociation()->getName()
            );
            $table->addRow($row);
        }
        $table->display();
    }
}
