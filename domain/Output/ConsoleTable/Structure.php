<?php

declare(strict_types=1);

namespace Sports\Output\ConsoleTable;

use LucidFrame\Console\ConsoleTable;
use Sports\Competition;
use Sports\Competitor\StartLocationMap;
use Sports\Structure\NameService as StructureNameService;
use Sports\Structure as StructureBase;
use Sports\Competitor\Team as TeamCompetitor;

final class Structure
{
    /**
     * @param StructureBase $structure
     * @param list<TeamCompetitor> $teamCompetitors
     * @param Competition $competition
     */
    public function display(Competition $competition, StructureBase $structure, array $teamCompetitors): void
    {
        $table = new ConsoleTable();
        $table->setHeaders(array('league', 'season', 'pouleNr', 'placeNr', 'team'));

        $structureNameService = new StructureNameService(new StartLocationMap($teamCompetitors));

        foreach ($structure->getRootRounds() as $rootRound) {
            foreach ($rootRound->getPoules() as $poule) {
                foreach ($poule->getPlaces() as $place) {
                    $row = array(
                        $competition->getLeague()->getName(),
                        $competition->getSeason()->getName(),
                        $place->getPouleNr(),
                        $place->getPlaceNr(),
                        $structureNameService->getPlaceName($place, true)
                    );
                    $table->addRow($row);
                }
            }
        }
        $table->display();
    }
}
