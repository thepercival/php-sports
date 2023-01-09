<?php

declare(strict_types=1);

namespace Sports\TestHelper;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsPlanning\Game\Assigner as GameAssigner;
use SportsPlanning\Game\Creator as GameCreator;
use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Schedule\Creator as ScheduleCreator;
use SportsPlanning\Schedule\Output as ScheduleOutput;

class PlanningCreator
{
    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
//        $processor = new UidProcessor();
//        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', Logger::INFO);
        $logger->pushHandler($handler);
        return $logger;
    }

    public function createPlanning(Input $input, SportRange $range = null, int $allowedGppMargin = ScheduleCreator::MAX_ALLOWED_GPP_MARGIN): Planning
    {
        if ($range === null) {
            $range = new SportRange(1, 1);
        }
        $planning = new Planning($input, $range, 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input, $allowedGppMargin);
        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        // $gameCreator->disableThrowOnTimeout();
        $gameCreator->createGames($planning, $schedules);

        $gameAssigner = new GameAssigner($this->getLogger());
        $gameAssigner->assignGames($planning);

        if (PlanningState::Succeeded !== $planning->getState()) {
            throw new Exception("planning could not be created", E_ERROR);
        }
        return $planning;
    }
}
