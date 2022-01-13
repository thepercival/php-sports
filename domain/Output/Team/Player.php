<?php

declare(strict_types=1);

namespace Sports\Output\Team;

use Psr\Log\LoggerInterface;
use Sports\Team\Player as TeamPlayer;
use SportsHelpers\Output as OutputBase;

class Player extends OutputBase
{
    public function __construct(LoggerInterface|null $logger= null)
    {
        parent::__construct($logger);
    }

    public function output(TeamPlayer $teamPlayer, string $prefix): void
    {
        $this->logger->info($this->getString($teamPlayer, $prefix));
    }

    public function getString(TeamPlayer $teamPlayer, string $prefix): string
    {
        return $prefix . $teamPlayer->getPerson()->getName() . ' - ' .
            $teamPlayer->getTeam()->getName() . ' - ' . $teamPlayer->getPeriod()->format('Y-m-d')
        ;
    }
}
