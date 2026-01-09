<?php

declare(strict_types=1);

namespace Sports\Output\ConsoleTable;

use LucidFrame\Console\ConsoleTable;
use Sports\Association;

final class Associations
{
    /**
     * @param list<Association> $associations
     */
    public function display(array $associations): void
    {
        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name','parent'));
        usort($associations, function (Association $a, Association $b): int {
            return $a->getName() < $b->getName() ? -1 : 1;
        });
        foreach ($associations as $association) {
            $row = array( $association->id, $association->getName() );
            $parent = $association->getParent();
            $parentName = ($parent !== null) ? $parent->getName() : null;
            $row[] = $parentName;
            $table->addRow($row);
        }
        $table->display();
    }
}
