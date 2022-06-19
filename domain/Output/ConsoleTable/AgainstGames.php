<?php

declare(strict_types=1);

namespace Sports\Output\ConsoleTable;

use DateTimeInterface;
use LucidFrame\Console\ConsoleTable;
use Sports\Competition;
use Sports\Competitor\StartLocationMap;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game\Against as AgainstGame;
use Sports\Structure\NameService as StructureNameService;
use Sports\Score\Against as AgainstScore;
use SportsHelpers\Against\Side as AgainstSide;

class AgainstGames
{
    /**
     * @param Competition $competition
     * @param list<AgainstGame> $games
     * @param list<TeamCompetitor> $teamCompetitors
     */
    public function display(Competition $competition, array $games, array $teamCompetitors): void
    {
        $table = new ConsoleTable();
        $table->setHeaders(array('league', 'season', 'gameRoundNr', 'batchNr', 'id', 'datetime', 'state', 'home', 'score', 'away' ));

        $structureNameService = new StructureNameService(new StartLocationMap($teamCompetitors));

        foreach ($games as $game) {
            $row = array(
                $competition->getLeague()->getName(),
                $competition->getSeason()->getName(),
                $game->getGameRoundNumber(),
                $game->getBatchNr(),
                $game->getId(),
                $game->getStartDateTime()->format(DateTimeInterface::ATOM),
                $game->getState()->name,
                $structureNameService->getPlacesFromName($game->getSidePlaces(AgainstSide::Home), true, true),
                $this->getScore($game),
                $structureNameService->getPlacesFromName($game->getSidePlaces(AgainstSide::Away), true, true),
            );
            $table->addRow($row);
        }
        $table->display();
    }

    protected function getScore(AgainstGame $game): string
    {
        return join("&", $game->getScores()->map(function (AgainstScore $gameScore): string {
            return $gameScore->getHome() . " - " . $gameScore->getAway() ;
        })->toArray());
    }
}
