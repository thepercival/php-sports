<?php

namespace Sports\Tests\Round\Number;

use Sports\Round\Number\PlanningAssigner;
use Sports\Round\Number\PlanningScheduler;
use Sports\TestHelper\PlanningCreator;
use SportsPlanning\Input;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Round\Number\GamesValidator;
use Sports\TestHelper\CompetitionCreator;
use Sports\Structure\Service as StructureService;

class PlanningAssignerTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, PlanningCreator;

    public function testValid()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 4, 2);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($firstRoundNumber, $options);

        $planningAssigner = new PlanningAssigner(new PlanningScheduler());
        $planningAssigner->createGames($firstRoundNumber, $planning);

        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        self::assertNull($gamesValidator->validate($firstRoundNumber, $nrOfReferees));
    }

    public function testWithRefereePlaces()
    {
        $competition = $this->createCompetition();
        $competition->getReferees()->clear();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 4);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstRoundNumber->getPlanningConfig()->setSelfReferee(Input::SELFREFEREE_SAMEPOULE);
        $options = [];
        $planning = $this->createPlanning($firstRoundNumber, $options);
        $refereePlaceService = new RefereePlaceService($planning);
        $refereePlaceService->assign($planning->createFirstBatch());

        $planningAssigner = new PlanningAssigner(new PlanningScheduler());
        $planningAssigner->createGames($firstRoundNumber, $planning);

        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        self::assertNull($gamesValidator->validate($firstRoundNumber, $nrOfReferees));
    }

    public function testDifferentPouleSizes()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 11);

        $rootRound = $structure->getRootRound();
        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 7);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $firstRoundNumber->getPlanningConfig()->setSelfReferee(Input::SELFREFEREE_SAMEPOULE);
        $secondRoundNumber = $firstRoundNumber->getNext();

        $options = [];
        $firstRoundPlanning = $this->createPlanning($firstRoundNumber, $options);
        $refereePlaceService = new RefereePlaceService($firstRoundPlanning);
        $refereePlaceService->assign($firstRoundPlanning->createFirstBatch());

        $secondRoundPlanning = $this->createPlanning($secondRoundNumber, $options);
        $refereePlaceService = new RefereePlaceService($secondRoundPlanning);
        $refereePlaceService->assign($secondRoundPlanning->createFirstBatch());

        $planningAssigner = new PlanningAssigner(new PlanningScheduler());
        $planningAssigner->createGames($firstRoundNumber, $firstRoundPlanning);
        $planningAssigner->createGames($secondRoundNumber, $secondRoundPlanning);

        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        self::assertNull($gamesValidator->validate($secondRoundNumber, $nrOfReferees));
    }
}
