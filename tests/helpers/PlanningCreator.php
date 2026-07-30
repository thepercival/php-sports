<?php

declare(strict_types=1);

namespace Sports\TestHelper;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsPlanning\PlanningRefereeInfo;
use SportsScheduler\Game\Assigner as GameAssigner;
use SportsScheduler\Game\GameCreatorFromSchedule;
use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\Planning\State as PlanningState;
use SportsScheduler\Schedule\ScheduleCreator;
use SportsPlanning\Output\ScheduleOutput;

final class PlanningCreator
{
    use GppMarginCalculator;

    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        //        $processor = new UidProcessor();
        //        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', Level::Info);
        $logger->pushHandler($handler);
        return $logger;
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @param PlanningRefereeInfo $refereeInfo
     * @return Input
     */
    public function createInput(
        PouleStructure $pouleStructure,
        array $sportVariantsWithFields,
        PlanningRefereeInfo $refereeInfo,
        bool $perPoule = false
    ) {
        //        if ($sportVariantsWithFields === null) {
        //            $sportVariantsWithFields = [$this->getAgainstH2hSportVariantWithFields(2)];
        //        }
        //        if ($refereeInfo === null) {
        //            $refereeInfo = new RefereeInfo($this->getDefaultNrOfReferees());
        //        }
        $input = new Input(
            new Input\Configuration(
                $pouleStructure,
                $sportVariantsWithFields,
                $refereeInfo,
                $perPoule
            )
        );

        return $input;
    }

    public function createPlanning(Input $input, SportRange|null $batchGamesRange = null, int|null $allowedGppMargin = null): Planning
    {
        if ($batchGamesRange === null) {
            $batchGamesRange = new SportRange(1, 1);
        }
        $planning = new Planning($input, $batchGamesRange, 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        if ($allowedGppMargin === null) {
            $allowedGppMargin = $this->getMaxGppMargin($input->getPoule(1), $this->getLogger());
        }

        $sports = $input->configuration->createSportVariants();
        $sportVariantsWithNr = $scheduleCreator->createSportVariantsWithNr($sports);

        $schedules = $scheduleCreator->createFromPouleStructureAndSports(
            $input->createPouleStructure(),
            $sportVariantsWithNr,
            $allowedGppMargin
        );
        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreatorFromSchedule($this->getLogger());
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
