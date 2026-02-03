<?php

declare(strict_types=1);

namespace Sports\Tests\Structure;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sports\Output\StructureOutput;
use Sports\Qualify\Distribution;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Rule\Creator\Vertical;
use Sports\Qualify\Rule\Horizontal\Single as HorizontalSingleQualifyRule;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Structure;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\StructureEditorCreator;
use Sports\Qualify\Rule\Vertical\Single as VerticalSingleQualifyRule;
use Sports\Qualify\Rule\Vertical\Multiple as VerticalMultipleQualifyRule;
use SportsHelpers\PlaceRanges;
use SportsHelpers\PouleStructures\BalancedPouleStructure;
use SportsHelpers\Sport\Variant\MinNrOfPlacesCalculator;

final class EditorTest extends TestCase
{
    use CompetitionCreator;
    use StructureEditorCreator;

    public function testAddChildRound1(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        self::assertSame($firstRoundNumber, $rootRound->getNumber());
        self::assertSame($firstRoundNumber, $structure->getLastRoundNumber());

        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        // (new StructureOutput($this->getLogger()))->output($structure);

        self::assertSame($structure->getLastRoundNumber(), $rootRound->getNumber()->getNext());
        self::assertCount(2, $structure->getRoundNumbers());
        self::assertSame($firstRoundNumber, $structure->getRoundNumber(1));
        self::assertSame($firstRoundNumber->getNext(), $structure->getRoundNumber(2));
        self::assertNull($structure->getRoundNumber(3));
        self::assertNull($structure->getRoundNumber(0));
    }

    // when losersChildRound is present
    public function testAddPlaceToRootRound1(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        self::assertSame($firstRoundNumber, $rootRound->getNumber());
        self::assertSame($firstRoundNumber, $structure->getLastRoundNumber());

        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2, 2]);
        $structureEditor->addPlaceToRootRound($rootRound);
        // (new StructureOutput($this->getLogger()))->output($structure);

        $qualifyGroup = $losersRound->getParentQualifyGroup();
        self::assertInstanceOf(QualifyGroup::class, $qualifyGroup);

        $fromPlace = $qualifyGroup->getFromPlace($losersRound->getPoule(1)->getPlace(1));
        self::assertNotNull($fromPlace);

        self::assertSame(3, $fromPlace->getPouleNr());
        self::assertSame(4, $fromPlace->getPlaceNr());
        self::assertSame(17, $rootRound->getNrOfPlaces());
    }

    // when losersChildRound is present with enough places
    public function testRemovePlaceFromRootRound1(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        self::assertSame($firstRoundNumber, $rootRound->getNumber());
        self::assertSame($firstRoundNumber, $structure->getLastRoundNumber());

        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2, 2]);
        // (new StructureOutput($this->getLogger()))->output($structure);
        $structureEditor->removePlaceFromRootRound($rootRound);
        // (new StructureOutput($this->getLogger()))->output($structure);

        $qualifyGroup = $losersRound->getParentQualifyGroup();
        self::assertNotNull($qualifyGroup);
        $fromPlace = $qualifyGroup->getFromPlace($losersRound->getPoule(1)->getPlace(1));
        self::assertNotNull($fromPlace);
        self::assertSame(1, $fromPlace->getPouleNr());
        self::assertSame(4, $fromPlace->getPlaceNr());
        self::assertSame(15, $rootRound->getNrOfPlaces());
    }

    // when all places are qualified
    public function testRemovePlaceFromRootRound2(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        self::assertSame($firstRoundNumber, $rootRound->getNumber());
        self::assertSame($firstRoundNumber, $structure->getLastRoundNumber());

        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [4]);
        $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [4]);
        // (new StructureOutput($this->getLogger()))->output($structure);
        self::expectException(Exception::class);
        $structureEditor->removePlaceFromRootRound($rootRound);
    }

    public function testAddPouleToRootRound1(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 2]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $structureEditor->addPouleToRootRound($rootRound);
        // (new StructureOutput($this->getLogger()))->output($structure);

        self::assertSame(3, $rootRound->getLastPoule()->getNumber());
        self::assertCount(2, $rootRound->getLastPoule()->getPlaces());
        self::assertSame(7, $rootRound->getNrOfPlaces());
    }

    // addPouleToRootRound 4,3 with childplaces
    public function testAddPouleToRootRound2(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [3]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [3]);
        // (new StructureOutput($this->getLogger()))->output($structure);

        $structureEditor->addPouleToRootRound($rootRound);
        // (new StructureOutput($this->getLogger()))->output($structure);

        $qualifyGroup = $losersRound->getParentQualifyGroup();
        self::assertNotNull($qualifyGroup);

        $fromPlace = $qualifyGroup->getFromPlace($losersRound->getPoule(1)->getPlace(1));
        self::assertNotNull($fromPlace);

        self::assertSame(2, $fromPlace->getPouleNr());
        self::assertSame(3, $fromPlace->getPlaceNr());
    }

    // 4,3 with childplaces
    public function testAddPouleToRootRoundWithSecondsRoundNoCrossFinals(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $firstPlacesRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $secondPlacesRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);

//         (new StructureOutput())->output($structure);

        $structureEditor->addPouleToRootRound($rootRound);

//        (new StructureOutput())->output($structure);

        $firstPlaces = $firstPlacesRound->getPoule(1)->getPlaces();
        self::assertCount(3, $firstPlaces);

        $secondPlaces = $secondPlacesRound->getPoule(1)->getPlaces();
        self::assertCount(3, $secondPlaces);
    }

    // 4,3 with childplaces
    public function testAddPouleToRootRoundWithSecondsRoundNoCrossFinalsWithLosers(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $lastPlacesRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);
        $secondLastPlacesRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);

//        (new StructureOutput())->output($structure);
//
        $structureEditor->addPouleToRootRound($rootRound);

//        (new StructureOutput())->output($structure);

        $lastPlaces = $lastPlacesRound->getPoule(1)->getPlaces();
        self::assertCount(3, $lastPlaces);

        $secondLastPlaces = $secondLastPlacesRound->getPoule(1)->getPlaces();
        self::assertCount(3, $secondLastPlaces);
    }

    public function testIncNrOfPoulesWithThirdRoundsNoCrossFinals(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5, 5]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [4, 3]);

        $structureEditor->addChildRound($winnersRound, QualifyTarget::Winners, [2]);
        $structureEditor->addChildRound($winnersRound, QualifyTarget::Winners, [2]);
        $structureEditor->addChildRound($winnersRound, QualifyTarget::Winners, [2]);

//        (new StructureOutput())->output($structure);
//
        $structureEditor->incrementNrOfPoules($winnersRound);

//        (new StructureOutput())->output($structure);

        self::assertCount(2, $winnersRound->getQualifyGroups());

        $firstQualifyGroup = $winnersRound->getQualifyGroups()->first();
        $lastQualifyGroup = $winnersRound->getQualifyGroups()->last();
        self::assertNotFalse($firstQualifyGroup);
        self::assertNotFalse($lastQualifyGroup);
        self::assertEquals(3, $firstQualifyGroup->getRulesNrOfToPlaces());
        self::assertEquals(3, $lastQualifyGroup->getRulesNrOfToPlaces());
    }

    public function testAddQualifierWithThirdRoundsNoCrossFinals(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [6, 6, 6, 6]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [4, 4, 3, 3]);

        $structureEditor->addChildRound($winnersRound, QualifyTarget::Winners, [4]);
        $structureEditor->addChildRound($winnersRound, QualifyTarget::Winners, [4]);

//        (new StructureOutput())->output($structure);

        $structureEditor->addQualifiers($rootRound, QualifyTarget::Winners, 1, Distribution::HorizontalSnake, 3);

//        (new StructureOutput())->output($structure);

        $firstQualifyGroup = $winnersRound->getQualifyGroups()->first();
        $lastQualifyGroup = $winnersRound->getQualifyGroups()->last();
        self::assertNotFalse($firstQualifyGroup);
        self::assertNotFalse($lastQualifyGroup);
        self::assertEquals(5, $firstQualifyGroup->getRulesNrOfToPlaces());
        self::assertEquals(5, $lastQualifyGroup->getRulesNrOfToPlaces());
    }

    public function testVerticalDistribution(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [4, 4, 4], Distribution::Vertical);
        //(new StructureOutput()).toConsole(structure, console);

        $ruleOne = $winnersRound->getParentQualifyGroup()?->getFirstSingleRule();
        self::assertInstanceOf(VerticalSingleQualifyRule::class, $ruleOne);
        self::assertSame(3, $ruleOne->getNrOfMappings());

        $ruleTwo = $ruleOne->getNext();
        self::assertInstanceOf(VerticalSingleQualifyRule::class, $ruleTwo);
        self::assertSame(3, $ruleTwo->getNrOfMappings());

        $ruleThree = $ruleTwo->getNext();
        self::assertInstanceOf(VerticalSingleQualifyRule::class, $ruleThree);
        self::assertSame(3, $ruleThree->getNrOfMappings());

        $ruleFour = $ruleThree->getNext();
        self::assertInstanceOf(VerticalSingleQualifyRule::class, $ruleFour);
        self::assertSame(3, $ruleFour->getNrOfMappings());
    }

    public function testRemovePouleFromRootRound1(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        self::expectException(Exception::class);
        $structureEditor->removePouleFromRootRound($rootRound);
    }

    // with too much places to next round
    public function testRemovePouleFromRootRound2(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3, 3, 3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [5, 5]);

        self::expectException(Exception::class);
        $structureEditor->removePouleFromRootRound($rootRound);
    }

    // with childRounds
    public function testRemovePouleFromRootRound3(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3, 3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [3]);
        $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [3]);

        $structureEditor->removePouleFromRootRound($rootRound);
        // (new StructureOutput($this->getLogger()))->output($structure);

        self::assertCount(2, $rootRound->getChildren());
    }

    // 4,3 with childplaces
    public function testRemovePouleFromRootRoundWithSecondRoundNoCrossFinals(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3, 3, 3, 3, 3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $firstPlacesRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [3, 3]);
        $secondPlacesRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [3, 3]);

        $structureEditor->addChildRound($firstPlacesRound, QualifyTarget::Winners, [2]);
        $structureEditor->addChildRound($firstPlacesRound, QualifyTarget::Winners, [2]);

//         (new StructureOutput())->output($structure);

        $structureEditor->removePouleFromRootRound($rootRound);

//        (new StructureOutput())->output($structure);

        self::assertCount(2, $firstPlacesRound->getTargetQualifyGroups(QualifyTarget::Winners));
        self::assertCount(3, $firstPlacesRound->getPoule(1)->getPlaces());
        self::assertCount(2, $firstPlacesRound->getPoule(2)->getPlaces());

        self::assertCount(3, $secondPlacesRound->getPoule(1)->getPlaces());
        self::assertCount(2, $secondPlacesRound->getPoule(2)->getPlaces());

        $structureEditor->removePouleFromRootRound($rootRound);
    }

    public function testRemovePouleFromRootRoundRemoveLastRoundNumber(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $firstPlacesRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [3]);
        $secondPlacesRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [3]);

        $structureEditor->addChildRound($firstPlacesRound, QualifyTarget::Winners, [2]);
        $structureEditor->addChildRound($secondPlacesRound, QualifyTarget::Winners, [2]);

//         (new StructureOutput())->output($structure);

        $structureEditor->removePouleFromRootRound($rootRound);

//        (new StructureOutput())->output($structure);
//
        self::assertCount(0, $firstPlacesRound->getChildren());
        self::assertCount(0, $secondPlacesRound->getChildren());
    }

    // too little placesperpoule
    public function testIncrementNrOfPoules1(): void
    {
        $competition = $this->createCompetition();
        $sportVariants = $competition->createSportVariants();
        $minNrOfPlacesPerPoule = (new MinNrOfPlacesCalculator())->getMinNrOfPlacesPerPoule($sportVariants);
        $maxNrOfPlacesPerPoule = 10;
        $minNrOfPlacesPerRound = $minNrOfPlacesPerPoule;
        $maxNrOfPlacesPerRound = 100;
        $placeRanges = new PlaceRanges(
            $minNrOfPlacesPerPoule,
            $maxNrOfPlacesPerPoule,
            null,
            $minNrOfPlacesPerRound,
            $maxNrOfPlacesPerRound,
            null
        );

        $structureEditor = $this->createStructureEditor($placeRanges);
        $structure = $structureEditor->create($competition, [3, 2]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        self::expectException(Exception::class);
        $structureEditor->incrementNrOfPoules($rootRound);
    }

    // middleRound
    public function testIncrementNrOfPoules2(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5, 5]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [3, 3]);
        $structureEditor->addChildRound($winnersRound, QualifyTarget::Winners, [4]);
        // (new StructureOutput($this->getLogger()))->output($structure);

        $structureEditor->incrementNrOfPoules($winnersRound);
        // (new StructureOutput($this->getLogger()))->output($structure);

        self::assertSame(6, $winnersRound->getNrOfPlaces());
    }

    // too little poules
    public function testDecrementNrOfPoules1(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        self::expectException(Exception::class);
        $structureEditor->decrementNrOfPoules($rootRound);
    }

    public function testDecrementNrOfPoules2(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3, 2]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $structureEditor->decrementNrOfPoules($rootRound);

        self::assertCount(2, $rootRound->getPoules());
        self::assertCount(4, $rootRound->getFirstPoule()->getPlaces());
        self::assertCount(4, $rootRound->getLastPoule()->getPlaces());
    }

    public function testUpdateDistribution(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [4, 4, 4]);

//        (new StructureOutput($this->getLogger()))->output($structure);

        $parentQualifyGroup = $winnersRound->getParentQualifyGroup();
        self::assertInstanceOf(QualifyGroup::class, $parentQualifyGroup);
        $structureEditor->updateDistribution($parentQualifyGroup, Distribution::Vertical);

//        (new StructureOutput($this->getLogger()))->output($structure);

        self::assertSame(Distribution::Vertical, $parentQualifyGroup->getDistribution());

        self::assertInstanceOf(VerticalSingleQualifyRule::class, $parentQualifyGroup->getFirstSingleRule());
        // $structureEditor->addQualifiers($rootRound, QualifyTarget::Winners, 1, Distribution::HorizontalSnake);
    }

    // new Round too little qualifiers
    public function testAddQualifiers1(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        self::expectException(Exception::class);
        $structureEditor->addQualifiers($rootRound, QualifyTarget::Winners, 1, Distribution::HorizontalSnake);
    }

    // out of range
    public function testAddQualifiers2(): void
    {
        $competition = $this->createCompetition();
        $minNrOfPlacesPerPoule = (new MinNrOfPlacesCalculator())->getMinNrOfPlacesPerPoule($competition->createSportVariants());
        $maxNrOfPlacesPerPoule = 4;
        $minNrOfPlacesPerRound = $minNrOfPlacesPerPoule;
        $maxNrOfPlacesPerRound = 6;
        $placeRanges = new PlaceRanges(
            $minNrOfPlacesPerPoule,
            $maxNrOfPlacesPerPoule,
            null,
            $minNrOfPlacesPerRound,
            $maxNrOfPlacesPerRound,
            null
        );
        $structureEditor = $this->createStructureEditor($placeRanges);
        $structure = $structureEditor->create($competition, [3, 3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [4]);

        self::expectException(Exception::class);
        $structureEditor->addQualifiers($rootRound, QualifyTarget::Winners, 1, Distribution::HorizontalSnake);
    }

    // new Round
    public function testAddQualifiers3(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $structureEditor->addQualifiers($rootRound, QualifyTarget::Winners, 2, Distribution::HorizontalSnake);

        $winnersRound = $rootRound->getChild(QualifyTarget::Winners, 1);
        self::assertNotNull($winnersRound);
        self::assertSame(2, $winnersRound->getNrOfPlaces());
    }

    // 3 levels deep
    public function testRemoveQualifiers1(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $quarterFinals = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2, 2, 2]);
        $semiFinals = $structureEditor->addChildRound($quarterFinals, QualifyTarget::Winners, [2, 2]);
        $structureEditor->addChildRound($semiFinals, QualifyTarget::Winners, [2]);

//        (new StructureOutput())->output($structure);
        $structureEditor->removeQualifier($rootRound, QualifyTarget::Winners);
//        (new StructureOutput())->output($structure);
        $structureEditor->removeQualifier($rootRound, QualifyTarget::Winners);
//        (new StructureOutput())->output($structure);
        $structureEditor->removeQualifier($rootRound, QualifyTarget::Winners);
//        (new StructureOutput())->output($structure);
        $structureEditor->removeQualifier($rootRound, QualifyTarget::Winners);
//        (new StructureOutput())->output($structure);
        $structureEditor->removeQualifier($rootRound, QualifyTarget::Winners);
//        (new StructureOutput())->output($structure);

        $newSemiFinals = $rootRound->getChild(QualifyTarget::Winners, 1);
        self::assertNotNull($newSemiFinals);
        $newFinal = $newSemiFinals->getChild(QualifyTarget::Winners, 1);
        self::assertNotNull($newFinal);

        self::assertSame(3, $newSemiFinals->getNrOfPlaces());
        self::assertSame(2, $newFinal->getNrOfPlaces());
    }

    // empty NextRoundNumber
    public function testRemoveQualifiers2(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        self::assertSame($firstRoundNumber, $rootRound->getNumber());
        self::assertSame($firstRoundNumber, $structure->getLastRoundNumber());

        // (new StructureOutput($this->getLogger()))->output($structure);
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        // (new StructureOutput($this->getLogger()))->output($structure);
        $structureEditor->removeQualifier($rootRound, QualifyTarget::Winners);
        // (new StructureOutput($this->getLogger()))->output($structure);

        self::assertCount(0, $rootRound->getTargetQualifyGroups(QualifyTarget::Winners));
        self::assertCount(1, $structure->getRoundNumbers());

        self::assertNull($structure->getRoundNumber(2));
        self::assertNull($structure->getFirstRoundNumber()->getNext());
    }

    // too few places per poule
    public function testSplitQualifyGroupFrom1(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [6]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $firstSix = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [3]);

        $qualifyGroup = $firstSix->getParentQualifyGroup();
        self::assertNotNull($qualifyGroup);

        $firstSingleQualifyRule = $qualifyGroup->getFirstSingleRule();
        self::assertNotNull($firstSingleQualifyRule);
        // (new StructureOutput($this->getLogger()))->output($structure);

        self::expectException(Exception::class);
        $structureEditor->splitQualifyGroupFrom($qualifyGroup, $firstSingleQualifyRule);
    }

    // keep nrOfPoulePlaces
    public function testSplitQualifyGroupFrom2(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3, 3, 3, 3, 3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $firstSix = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [6, 6]);

        $qualifyGroup = $firstSix->getParentQualifyGroup();
        self::assertNotNull($qualifyGroup);

        $firstSingleQualifyRule = $qualifyGroup->getFirstSingleRule();
        self::assertNotNull($firstSingleQualifyRule);

        // (new StructureOutput($this->getLogger()))->output($structure);
        $structureEditor->splitQualifyGroupFrom($qualifyGroup, $firstSingleQualifyRule);
        // (new StructureOutput($this->getLogger()))->output($structure);
    }

    // no
    public function testIsQualifyGroupSplittableAt1(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [8]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $nextRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [3]);
        $qualifyGroup = $nextRound->getParentQualifyGroup();
        self::assertNotNull($qualifyGroup);

        /** @var HorizontalSingleQualifyRule|VerticalSingleQualifyRule|null $singleRule */
        $singleRule = $qualifyGroup->getFirstSingleRule();
        while ($singleRule !== null) {
            self::assertFalse($structureEditor->isQualifyGroupSplittableAt($singleRule));
            $singleRule = $singleRule->getNext();
        }
    }

    // no multiple
    public function testIsQualifyGroupSplittableAt2(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [3]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [3]);
        // (new StructureOutput($this->getLogger()))->output($structure);

        $winnersQualifyGroup = $winnersRound->getParentQualifyGroup();
        $losersQualifyGroup = $losersRound->getParentQualifyGroup();
        self::assertNotNull($winnersQualifyGroup);
        self::assertNotNull($losersQualifyGroup);

        $firstWinnersSingleRule = $winnersQualifyGroup->getFirstSingleRule();
        self::assertNotNull($firstWinnersSingleRule);
        self::assertFalse($structureEditor->isQualifyGroupSplittableAt($firstWinnersSingleRule));

        $firstLosersSingleRule = $losersQualifyGroup->getFirstSingleRule();
        self::assertNotNull($firstLosersSingleRule);
        self::assertFalse($structureEditor->isQualifyGroupSplittableAt($firstLosersSingleRule));
    }

    // yes
    public function testIsQualifyGroupSplittableAt3(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $nextRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [4]);
        // (new StructureOutput($this->getLogger()))->output($structure);

        $qualifyGroup = $nextRound->getParentQualifyGroup();
        self::assertNotNull($qualifyGroup);

        $singleRule = $qualifyGroup->getFirstSingleRule();
        self::assertNotNull($singleRule);
        self::assertTrue($structureEditor->isQualifyGroupSplittableAt($singleRule));
    }

    public function testAreQualifyGroupsMergable(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $firstSecond = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $thirdFourth = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $fifthSixth = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);

        $firstSecondQualifyGroup = $firstSecond->getParentQualifyGroup();
        $thirdFourthQualifyGroup = $thirdFourth->getParentQualifyGroup();
        $fifthSixthQualifyGroup = $fifthSixth->getParentQualifyGroup();
        $losersQualifyGroup = $losersRound->getParentQualifyGroup();
        self::assertNotNull($firstSecondQualifyGroup);
        self::assertNotNull($thirdFourthQualifyGroup);
        self::assertNotNull($fifthSixthQualifyGroup);
        self::assertNotNull($losersQualifyGroup);

        self::assertTrue($structureEditor->areQualifyGroupsMergable($firstSecondQualifyGroup, $thirdFourthQualifyGroup));
        self::assertTrue($structureEditor->areQualifyGroupsMergable($thirdFourthQualifyGroup, $fifthSixthQualifyGroup));
        self::assertFalse($structureEditor->areQualifyGroupsMergable($firstSecondQualifyGroup, $fifthSixthQualifyGroup));
        self::assertFalse($structureEditor->areQualifyGroupsMergable($losersQualifyGroup, $thirdFourthQualifyGroup));
    }

    // W1/2 W3/4
    public function testMergeQualifyGroups1(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5, 5]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $firstSecond = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $thirdFourth = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);
        $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);

        $firstSecondQualifyGroup = $firstSecond->getParentQualifyGroup();
        $thirdFourthQualifyGroup = $thirdFourth->getParentQualifyGroup();
        self::assertNotNull($firstSecondQualifyGroup);
        self::assertNotNull($thirdFourthQualifyGroup);

        $structureEditor->mergeQualifyGroups($firstSecondQualifyGroup, $thirdFourthQualifyGroup);
        self::assertSame(4, $firstSecond->getNrOfPlaces());
    }

    // W3/4 W5/6
    public function testMergeQualifyGroups2(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5, 5]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $thirdFourth = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $fifthSixth = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);
        $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);

        $thirdFourthQualifyGroup = $thirdFourth->getParentQualifyGroup();
        $fifthSixthQualifyGroup = $fifthSixth->getParentQualifyGroup();
        self::assertNotNull($thirdFourthQualifyGroup);
        self::assertNotNull($fifthSixthQualifyGroup);

        $structureEditor->mergeQualifyGroups($thirdFourthQualifyGroup, $fifthSixthQualifyGroup);
        self::assertSame(4, $thirdFourth->getNrOfPlaces());
    }

    // L1/2 L3/4
    public function testMergeQualifyGroups3(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5, 5]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $worstLosersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);

        $worstLosersQualifyGroup = $worstLosersRound->getParentQualifyGroup();
        $losersQualifyGroup = $losersRound->getParentQualifyGroup();
        self::assertNotNull($worstLosersQualifyGroup);
        self::assertNotNull($losersQualifyGroup);

        $structureEditor->mergeQualifyGroups($worstLosersQualifyGroup, $losersQualifyGroup);
        // (new StructureOutput($this->getLogger()))->output($structure);
        self::assertSame(4, $worstLosersRound->getNrOfPlaces());
    }

    public function testAddCategory(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4]);
        $category1 = $structure->getSingleCategory();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        self::assertCount(1, $firstRoundNumber->getGameAmountConfigs());

        $category2 = $structureEditor->addCategory('j78', null, $firstRoundNumber, new BalancedPouleStructure(...[5]));

        /*$newStructure =*/ new Structure(array_values($competition->getCategories()->toArray()), $firstRoundNumber);

        self::assertCount(1, $firstRoundNumber->getGameAmountConfigs());
        self::assertCount(1, $category2->getRootRound()->getAgainstQualifyConfigs());
        self::assertCount(1, $category2->getRootRound()->getScoreConfigs());

        self::assertEquals('1.1.1', $category1->getRootRound()->getPoule(1)->getStructureLocation());
        self::assertEquals('1.1.2', $category1->getRootRound()->getPoule(2)->getStructureLocation());
        self::assertEquals('2.1.1', $category2->getRootRound()->getPoule(1)->getStructureLocation());

        $structureEditor->addChildRound($category1->getRootRound(), QualifyTarget::Winners, [2]);
        $structureEditor->addChildRound($category1->getRootRound(), QualifyTarget::Winners, [2]);

        $lastPlaceRound = $structureEditor->addChildRound($category2->getRootRound(), QualifyTarget::Losers, [3]);
        $structureEditor->addChildRound($category2->getRootRound(), QualifyTarget::Losers, [2]);

        $lastPlaceChildRound = $structureEditor->addChildRound($lastPlaceRound, QualifyTarget::Losers, [2]);
        // (new StructureOutput($this->getLogger()))->output($newStructure);

        self::assertEquals('2.1L1L1.1', $lastPlaceChildRound->getPoule(1)->getStructureLocation());
    }

    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', Level::Info);
        $logger->pushHandler($handler);
        return $logger;
    }
}
