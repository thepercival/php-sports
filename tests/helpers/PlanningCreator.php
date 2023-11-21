<?php

declare(strict_types=1);

namespace Sports\TestHelper;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsScheduler\Game\Assigner as GameAssigner;
use SportsScheduler\Game\Creator as GameCreator;
use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\Planning\State as PlanningState;
use SportsScheduler\Schedule\Creator as ScheduleCreator;
use SportsPlanning\Output\Schedule as ScheduleOutput;

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

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @param RefereeInfo $refereeInfo
     * @return Input
     */
    public function createInput(
        PouleStructure $pouleStructure,
        array $sportVariantsWithFields,
        RefereeInfo $refereeInfo,
        bool $perPoule = false
    ) {
//        if ($sportVariantsWithFields === null) {
//            $sportVariantsWithFields = [$this->getAgainstH2hSportVariantWithFields(2)];
//        }
//        if ($refereeInfo === null) {
//            $refereeInfo = new RefereeInfo($this->getDefaultNrOfReferees());
//        }
        $input = new Input(
            $pouleStructure,
            $sportVariantsWithFields,
            $refereeInfo,
            $perPoule
        );

        return $input;
    }

    public function createPlanning(Input $input, SportRange $batchGamesRange = null, int|null $allowedGppMargin = null): Planning
    {
        if ($batchGamesRange === null) {
            $batchGamesRange = new SportRange(1, 1);
        }
        $planning = new Planning($input, $batchGamesRange, 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        if( $allowedGppMargin === null) {
            $allowedGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        }
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
