<?php
declare(strict_types=1);

namespace Sports\Tests\Structure;

use Exception;
use PHPUnit\Framework\TestCase;
use Sports\Place;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Structure;
use Sports\TestHelper\CompetitionCreator;
use Sports\Structure\Service as StructureService;
use Sports\Structure\Validator as StructureValidator;
use Sports\TestHelper\GamesCreator;

final class ValidatorTest extends TestCase
{
    use CompetitionCreator;

    public function testNoStructure()
    {
        $competition = $this->createCompetition();

        $structureValidator = new StructureValidator();

        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, null);
    }

    public function testRoundNumberNoRounds()
    {
        $competition = $this->createCompetition();

        // $structureService = new StructureService([]);

        $firstRoundNumber = new RoundNumber($competition);
        $rootRound = new Round($firstRoundNumber);
        $firstRoundNumber->getRounds()->clear();
        $structure = new Structure($firstRoundNumber, $rootRound);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testRoundNumberNoValidScoreConfig()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 4);

        $structure->getRootRound()->getScoreConfigs()->clear();

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testRoundNoPoules()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 4);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $rootRound->getPoules()->clear();

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testPouleNoPlaces()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 4);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $rootRound->getPoule(1)->getPlaces()->clear();

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testRoundNrOfPlaces()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 6, 2);

        (new GamesCreator())->createStructureGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $firstPoule = $rootRound->getPoule(1);
        new Place($firstPoule);
        new Place($firstPoule);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testQualifyGroupsNumberGap()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 6, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();
        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 2);

        (new GamesCreator())->createStructureGames($structure);

        $winnersQualifyGroup = $rootRound->getQualifyGroup(QualifyGroup::WINNERS, 1);
        self::assertNotNull($winnersQualifyGroup);
        $winnersQualifyGroup->setNumber(0);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testPoulesNumberGap()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 6, 2);

        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $rootRound->getPoule(1)->setNumber(0);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testPlacesNumberGap()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 6, 2);

        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $rootRound->getPoule(1)->getPlace(1)->setNumber(0);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testNextRoundNumberExists()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 6, 2);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $anotherRoundNumber = new RoundNumber($competition);

        $qualifyGroup = new QualifyGroup($rootRound, QualifyGroup::WINNERS);
        $secondRound = new Round($anotherRoundNumber, $qualifyGroup);

        (new GamesCreator())->createStructureGames($structure);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testValid()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, 6, 2);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 2);

        (new GamesCreator())->createStructureGames($structure);

        $structureValidator = new StructureValidator();
        self::assertNull($structureValidator->checkValidity($competition, $structure));
    }
}
