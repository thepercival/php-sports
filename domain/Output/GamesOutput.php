<?php

declare(strict_types=1);

namespace Sports\Output;

use Psr\Log\LoggerInterface;
use Sports\Competitor\StartLocationMap;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Order as GameOrder;
use Sports\Output\Game\Against as AgainstGameOutput;
use Sports\Output\Game\Column;
use Sports\Output\Game\Together as TogetherGameOutput;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\Output as OutputBase;

class GamesOutput extends OutputBase
{
    protected AgainstGameOutput $againstOutput;
    protected TogetherGameOutput $togetherOutput;

    public function __construct(StartLocationMap $startLocationMap = null, LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->againstOutput = new AgainstGameOutput($startLocationMap, $logger);
        $this->togetherOutput = new TogetherGameOutput($startLocationMap, $logger);
    }

    /**
     * @param RoundNumber $roundNumber
     * @param list<Column>|null $columns
     */
    public function outputRoundNumber(RoundNumber $roundNumber, array $columns = null): void
    {
        foreach ($roundNumber->getGames(GameOrder::ByBatch) as $game) {
            if ($game instanceof AgainstGame) {
                $this->againstOutput->output($game, null, $columns);
            } else {
                $this->togetherOutput->output($game);
            }
        }
    }
}
