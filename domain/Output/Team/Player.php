<?php

declare(strict_types=1);

namespace Sports\Output\Team;

use Psr\Log\LoggerInterface;
use Sports\Team\Player as TeamPlayer;
use SportsHelpers\Output\OutputAbstract;

final class Player extends OutputAbstract
{
    public function __construct(LoggerInterface $logger)
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
            $teamPlayer->getTeam()->getName() . ' - ' . $teamPlayer->getPeriod()->toIso80000('Y-m-d')
        ;
    }
}
