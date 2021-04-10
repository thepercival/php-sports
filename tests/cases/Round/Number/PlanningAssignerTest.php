<?php
declare(strict_types=1);

namespace Sports\Tests\Round\Number;

use Sports\Qualify\Target as QualifyTarget;
use PHPUnit\Framework\TestCase;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Round\Number\GamesValidator;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\Structure\Editor as StructureService;
use Sports\TestHelper\StructureEditorCreator;
use SportsHelpers\PouleStructure;
use SportsHelpers\SelfReferee;

final class PlanningAssignerTest extends TestCase
{
    use CompetitionCreator, StructureEditorCreator;

    public function testValid(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [2,2]);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        (new GamesCreator())->createGames($firstRoundNumber);


        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        self::expectNotToPerformAssertions();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testWithRefereePlaces(): void
    {
        $competition = $this->createCompetition();
        $competition->getReferees()->clear();

        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [4]);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        (new GamesCreator())->createGames($firstRoundNumber);

        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        self::expectNotToPerformAssertions();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testDifferentPouleSizes(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [6,5]);

        $rootRound = $structure->getRootRound();
        $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [7]);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $firstRoundNumber->getValidPlanningConfig()->setSelfReferee(SelfReferee::SAMEPOULE);
        $secondRoundNumber = $firstRoundNumber->getNext();
        self::assertNotNull($secondRoundNumber);

        (new GamesCreator())->createStructureGames($structure);

        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        // self::expectNotToPerformAssertions();
        $gamesValidator->validate($secondRoundNumber, $nrOfReferees);
    }
}
