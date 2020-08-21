<?php

namespace Sports\Output\ConsoleTable;

use LucidFrame\Console\ConsoleTable;
use Sports\Competition;
use Sports\NameService;
use Sports\Place\Location\Map as PlaceLocationMap;
use Sports\Structure as StructureBase;
use Sports\Competitor\Team as TeamCompetitor;

class Structure
{
    /**
     * @param StructureBase $structure
     * @param array|TeamCompetitor[] $teamCompetitors
     * @param Competition $competition
     */
    public function display( Competition $competition, StructureBase $structure, array $teamCompetitors ) {
        $table = new ConsoleTable();
        $table->setHeaders(array('league', 'season', 'pouleNr', 'placeNr', 'team'));

        $nameService = new NameService( new PlaceLocationMap( $teamCompetitors ) );

        foreach( $structure->getRootRound()->getPoules() as $poule ) {
            foreach( $poule->getPlaces() as $place ) {
                $row = array(
                    $competition->getLeague()->getName(),
                    $competition->getSeason()->getName(),
                    $place->getPouleNr(),
                    $place->getPlaceNr(),
                    $nameService->getPlaceName( $place, true )
                );
                $table->addRow($row);
            }
        }
        $table->display();
    }
}
