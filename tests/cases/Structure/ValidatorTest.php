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
use SportsHelpers\PouleStructure;

final class ValidatorTest extends TestCase
{
    use CompetitionCreator;

    public function testNoStructure(): void
    {
        $competition = $this->createCompetition();

        $structureValidator = new StructureValidator();

        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, null);
    }

    public function testRoundNumberNoRounds(): void
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

    public function testRoundNumberNoValidScoreConfig(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, new PouleStructure([4]));

        $structure->getRootRound()->getScoreConfigs()->clear();

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testRoundNoPoules(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, new PouleStructure([4]));

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $rootRound->getPoules()->clear();

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testPouleNoPlaces(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, new PouleStructure([4]));

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $rootRound->getPoule(1)->getPlaces()->clear();

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testRoundNrOfPlaces(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, new PouleStructure([3,3]));

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

    public function testQualifyGroupsNumberGap(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, new PouleStructure([3,3]));
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

    public function testPoulesNumberGap(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, new PouleStructure([3,3]));

        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $poule = $rootRound->getPoule(1);
        $refCl = new \ReflectionClass($poule);
        $refClPropNumber = $refCl->getProperty("number");
        $refClPropNumber->setAccessible(true);
        $refClPropNumber->setValue($poule, 0);
        $refClPropNumber->setAccessible(false);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testPlacesNumberGap(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, new PouleStructure([3,3]));

        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $place = $rootRound->getPoule(1)->getPlace(1);
        $refCl = new \ReflectionClass($place);
        $refClPropNumber = $refCl->getProperty("number");
        $refClPropNumber->setAccessible(true);
        $refClPropNumber->setValue($place, 0);
        $refClPropNumber->setAccessible(false);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testNextRoundNumberExists(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, new PouleStructure([3,3]));

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $secondRoundNumber = new RoundNumber($competition);

        new QualifyGroup($rootRound, QualifyGroup::WINNERS, $secondRoundNumber);

        (new GamesCreator())->createStructureGames($structure);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure);
    }

    public function testValid(): void
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);

        $structure = $structureService->create($competition, new PouleStructure([3,3]));

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 2);

        (new GamesCreator())->createStructureGames($structure);

        $structureValidator = new StructureValidator();
        self::expectNotToPerformAssertions();
        $structureValidator->checkValidity($competition, $structure);
    }
}
