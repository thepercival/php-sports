<?php
declare(strict_types=1);

namespace Sports\TestHelper;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\Planning\GameCreator;

class PlanningCreator
{
    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', LOG_INFO);
        $logger->pushHandler($handler);
        return $logger;
    }

    public function createPlanning(Input $input, SportRange $range = null): Planning
    {
        if ($range === null) {
            $range = new SportRange(1, 1);
        }
        $planning = new Planning($input, $range, 0);
        $gameCreator = new GameCreator($this->getLogger());
        // CDK TODO //
        $gameCreator->disableThrowOnTimeout();
        $gameCreator->createAssignedGames($planning);
        if (Planning::STATE_SUCCEEDED !== $planning->getState()) {
            throw new Exception("planning could not be created", E_ERROR);
        }
        return $planning;
    }
}
