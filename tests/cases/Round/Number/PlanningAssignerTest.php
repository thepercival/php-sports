<?php

namespace Sports\Tests\Round\Number;

use Sports\Round\Number\PlanningAssigner;
use Sports\Round\Number\PlanningScheduler;
use Sports\TestHelper\PlanningCreator;
use SportsPlanning\Input;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Round\Number\GamesValidator;
use Sports\Round\Number as RoundNumber;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\Structure\Service as StructureService;
use SportsPlanning\SelfReferee;

class PlanningAssignerTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator;

    public function testValid()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 4, 2);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        (new GamesCreator())->createGames($firstRoundNumber);


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

        (new GamesCreator())->createGames($firstRoundNumber);

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
        $firstRoundNumber->getPlanningConfig()->setSelfReferee(SelfReferee::SAMEPOULE);
        $secondRoundNumber = $firstRoundNumber->getNext();

        (new GamesCreator())->createStructureGames($structure);

        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        self::assertNull($gamesValidator->validate($secondRoundNumber, $nrOfReferees));
    }
}
