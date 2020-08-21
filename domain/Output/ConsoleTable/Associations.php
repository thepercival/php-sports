<?php

namespace Sports\Output\ConsoleTable;

use LucidFrame\Console\ConsoleTable;
use Sports\Association;

class Associations
{
    /**
     * @param array|Association[] $associations
     */
    public function display( array $associations ) {
        $table = new ConsoleTable();
        $table->setHeaders(array('id', 'name','parent'));
        uasort( $associations, function( Association $a, Association $b ): int {
            return $a->getName() < $b->getName() ? -1 : 1;
        });
        foreach( $associations as $association ) {
            $row = array( $association->getId(), $association->getName() );
            $parentName = null;
            if( $association->getParent() !== null ) {
                $parentName = $association->getParent()->getName();
            }
            $row[] = $parentName;
            $table->addRow( $row );
        }
        $table->display();
    }
}
