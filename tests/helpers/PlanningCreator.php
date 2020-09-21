<?php

namespace Sports\TestHelper;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use SportsHelpers\Range;
use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\Planning\GameCreator;

class PlanningCreator {

    protected function getLogger(): LoggerInterface {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', LOG_INFO);
        $logger->pushHandler($handler);
        return $logger;
    }

    public function createPlanning( Input $input, Range $range = null ): Planning
    {
        if( $range === null ) {
            $range = new Range( 1, 1 );
        }
        $planning = new Planning( $input, $range, 0 );
        $gameCreator = new GameCreator( $this->getLogger() );
        if (Planning::STATE_SUCCEEDED !== $gameCreator->createGames($planning) ) {
            throw new \Exception("planning could not be created", E_ERROR);
        }
        return $planning;
    }
}

