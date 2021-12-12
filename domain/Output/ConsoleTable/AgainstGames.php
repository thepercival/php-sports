<?php

declare(strict_types=1);

namespace Sports\Output\ConsoleTable;

use DateTimeInterface;
use LucidFrame\Console\ConsoleTable;
use Sports\Competition;
use Sports\Competitor\Map as CompetitorMap;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game\Against as AgainstGame;
use Sports\NameService;
use Sports\Score\Against as AgainstScore;
use Sports\State;
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

        $nameService = new NameService(new CompetitorMap($teamCompetitors));

        foreach ($games as $game) {
            $row = array(
                $competition->getLeague()->getName(),
                $competition->getSeason()->getName(),
                $game->getGameRoundNumber(),
                $game->getBatchNr(),
                $game->getId(),
                $game->getStartDateTime()->format(DateTimeInterface::ATOM),
                (new State($game->getState()))->getDescription(),
                $nameService->getPlacesFromName($game->getSidePlaces(AgainstSide::Home), true, true),
                $this->getScore($game),
                $nameService->getPlacesFromName($game->getSidePlaces(AgainstSide::Away), true, true),
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
