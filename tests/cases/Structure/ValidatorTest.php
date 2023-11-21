<?php

declare(strict_types=1);

namespace Sports\Tests\Structure;

use Exception;
use PHPUnit\Framework\TestCase;
use Sports\Category;
use Sports\Output\StructureOutput;
use Sports\Place;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Structure;
use Sports\Structure\Validator as StructureValidator;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\StructureEditorCreator;
use SportsHelpers\PlaceRanges;

final class ValidatorTest extends TestCase
{
    use CompetitionCreator;
    use StructureEditorCreator;

    public function testNoStructure(): void
    {
        $competition = $this->createCompetition();

        $structureValidator = new StructureValidator();

        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, null, null);
    }

    public function testRoundNumberNoRounds(): void
    {
        $competition = $this->createCompetition();

        // $structureEditor = new StructureService([]);

        $category = new Category($competition, Category::DEFAULTNAME);
        $firstRoundNumber = new RoundNumber($competition);
        $structureCell = new Structure\Cell($category, $firstRoundNumber);
        new Round($structureCell);
        $structureCell->getRounds()->clear();
        $structure = new Structure([$category], $firstRoundNumber);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure, null);
    }

    public function testRoundNumberNoValidScoreConfig(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4]);

        $rootRound = $structure->getSingleCategory()->getRootRound();
        $rootRound->getScoreConfigs()->clear();

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure, null);
    }

    public function testRoundNoPoules(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4]);

        $rootRound = $structure->getSingleCategory()->getRootRound();

        $rootRound->getPoules()->clear();

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure, null);
    }

    public function testPouleNoPlaces(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4]);

        $rootRound = $structure->getSingleCategory()->getRootRound();

        $rootRound->getPoule(1)->getPlaces()->clear();

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure, null);
    }

    public function testRoundNrOfPlaces(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3]);

        (new GamesCreator())->createStructureGames($structure);

        $rootRound = $structure->getSingleCategory()->getRootRound();

        $firstPoule = $rootRound->getPoule(1);
        new Place($firstPoule);
        new Place($firstPoule);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure, null);
    }

    public function testWithPlaceRanges(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(2, 2, 2, 3)
        ];
        $competition = $this->createCompetition($sportVariantsWithFields);

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4]);

        // (new GamesCreator())->createStructureGames($structure);

        $maxNrOfPlacesPerPoule = 3;
        $placeRanges = new PlaceRanges(
            $structureEditor->getMinPlacesPerPouleSmall(),
            $maxNrOfPlacesPerPoule,
            null,
            $structureEditor->getMinPlacesPerPouleSmall(),
            40,
            null
        );

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure, $placeRanges);
    }

    public function testQualifyGroupsNumberGap(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);

        (new GamesCreator())->createStructureGames($structure);

        $winnersQualifyGroup = $rootRound->getQualifyGroup(QualifyTarget::Winners, 1);
        self::assertNotNull($winnersQualifyGroup);
        $winnersQualifyGroup->setNumber(0);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure, null);
    }

    public function testPoulesNumberGap(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3]);

        $rootRound = $structure->getSingleCategory()->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $poule = $rootRound->getPoule(1);
        $refCl = new \ReflectionClass($poule);
        $refClPropNumber = $refCl->getProperty("number");
        $refClPropNumber->setAccessible(true);
        $refClPropNumber->setValue($poule, 0);
        $refClPropNumber->setAccessible(false);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure, null);
    }

    public function testPlacesNumberGap(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3]);

        $rootRound = $structure->getSingleCategory()->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $place = $rootRound->getPoule(1)->getPlace(1);
        $refCl = new \ReflectionClass($place);
        $refClPropNumber = $refCl->getProperty("placeNr");
        $refClPropNumber->setAccessible(true);
        $refClPropNumber->setValue($place, 0);
        $refClPropNumber->setAccessible(false);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure, null);
    }

    public function testNextRoundNumberExists(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3]);

        $category = $structure->getSingleCategory();
        $rootRound = $category->getRootRound();

        $secondRoundNumber = new RoundNumber($competition);
        $nextStructureCell = new Structure\Cell($category, $secondRoundNumber);

        new QualifyGroup($rootRound, QualifyTarget::Winners, $nextStructureCell);

        (new GamesCreator())->createStructureGames($structure);

        $structureValidator = new StructureValidator();
        self::expectException(Exception::class);
        $structureValidator->checkValidity($competition, $structure, null);
    }

    public function testValid(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3]);

        $rootRound = $structure->getSingleCategory()->getRootRound();

        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);

        (new GamesCreator())->createStructureGames($structure);

        $structureValidator = new StructureValidator();
        self::expectNotToPerformAssertions();
        $structureValidator->checkValidity($competition, $structure, null);
    }

    public function testQualifyRuleOneTimeUsage(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();

        $structure = $structureEditor->create($competition, [5,5,5,5,5,5,5,5]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2, 2, 2, 2, 2, 2, 2]);
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2, 2, 2, 2, 2, 2, 2]);

        (new GamesCreator())->createStructureGames($structure);

        // (new StructureOutput())->output($structure);

        $structureValidator = new StructureValidator();
        self::expectNotToPerformAssertions();
        $structureValidator->checkHorizontalPouleOneTimeUse($rootRound);
    }
}
