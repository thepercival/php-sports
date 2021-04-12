<?php
declare(strict_types=1);

namespace Sports\Tests\Structure;

use Exception;
use PHPUnit\Framework\TestCase;
use Sports\Output\StructureOutput;
use Sports\Place;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Structure;
use Sports\TestHelper\CompetitionCreator;
use Sports\Structure\Editor as StructureService;
use Sports\Structure\Validator as StructureValidator;
use Sports\Qualify\Target as QualifyTarget;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\StructureEditorCreator;
use SportsHelpers\Place\Range as PlaceRange;
use SportsHelpers\SportRange;
use SportsHelpers\PouleStructure;

final class EditorTest extends TestCase
{
    use CompetitionCreator, StructureEditorCreator;

    public function testAddChildRound1(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [4,4,4,4]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        self::assertSame($firstRoundNumber, $rootRound->getNumber());
        self::assertSame($firstRoundNumber, $structure->getLastRoundNumber());

        $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        // (new StructureOutput())->output($structure, console);

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
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [4, 4, 4, 4]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        self::assertSame($firstRoundNumber, $rootRound->getNumber());
        self::assertSame($firstRoundNumber, $structure->getLastRoundNumber());

        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [2, 2]);
        $structureEditor->addPlaceToRootRound($rootRound);
        // (new StructureOutput())->output($structure, console);

        $qualifyGroup = $losersRound->getParentQualifyGroup();
        self::assertNotNull($qualifyGroup);

        $fromPlace = $qualifyGroup->getFromPlace($losersRound->getPoule(1)->getPlace(1));
        self::assertNotNull($fromPlace);

        self::assertSame(2, $fromPlace->getPouleNr());
        self::assertSame(4, $fromPlace->getPlaceNr());
        self::assertSame(17, $rootRound->getNrOfPlaces());
    }

    // when losersChildRound is present with enough places
    public function testRemovePlaceFromRootRound1(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [4, 4, 4, 4]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();
    
        self::assertSame($firstRoundNumber, $rootRound->getNumber());
        self::assertSame($firstRoundNumber, $structure->getLastRoundNumber());
    
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [2, 2]);
        // (new StructureOutput())->output($structure, console);
        $structureEditor->removePlaceFromRootRound($rootRound);
        // (new StructureOutput())->output($structure, console);
    
        $qualifyGroup = $losersRound->getParentQualifyGroup();
        self::assertNotNull($qualifyGroup);
        $fromPlace = $qualifyGroup->getFromPlace($losersRound->getPoule(1)->getPlace(1));
        self::assertNotNull($fromPlace);
        self::assertSame(4, $fromPlace->getPouleNr());
        self::assertSame(3, $fromPlace->getPlaceNr());
        self::assertSame(15, $rootRound->getNrOfPlaces());
    }

    // when all places are qualified
    public function testRemovePlaceFromRootRound2(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [4, 4]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        self::assertSame($firstRoundNumber, $rootRound->getNumber());
        self::assertSame($firstRoundNumber, $structure->getLastRoundNumber());

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [4]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [4]);
        // (new StructureOutput())->output($structure, console);
        self::expectException(Exception::class);
        $structureEditor->removePlaceFromRootRound($rootRound);
    }

    public function testAddPouleToRootRound1(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [3, 2]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $structureEditor->addPouleToRootRound($rootRound);
        // (new StructureOutput())->output($structure, console);

        self::assertSame(3, $rootRound->getLastPoule()->getNumber());
        self::assertCount(2, $rootRound->getLastPoule()->getPlaces());
        self::assertSame(7, $rootRound->getNrOfPlaces());
    }

    // 4,3 with childplaces
    public function testAddPouleToRootRound2(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [4, 3]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [3]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [3]);
        // (new StructureOutput())->output($structure, console);

        $structureEditor->addPouleToRootRound($rootRound);
        // (new StructureOutput())->output($structure, console);

        $qualifyGroup = $losersRound->getParentQualifyGroup();
        self::assertNotNull($qualifyGroup);

        $fromPlace = $qualifyGroup->getFromPlace($losersRound->getPoule(1)->getPlace(1));
        self::assertNotNull($fromPlace);

        self::assertSame(2, $fromPlace->getPouleNr());
        self::assertSame(3, $fromPlace->getPlaceNr());
    }

    public function testRemovePouleFromRootRound1(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [4]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        self::expectException(Exception::class);
        $structureEditor->removePouleFromRootRound($rootRound);
    }

    // with too much places to next round
    public function testRemovePouleFromRootRound2(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [3, 3, 3, 3]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [5, 5]);

        self::expectException(Exception::class);
        $structureEditor->removePouleFromRootRound($rootRound);
    }

    // with childRounds
    public function testRemovePouleFromRootRound3(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [3, 3, 3]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [3]);
        $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [3]);

        $structureEditor->removePouleFromRootRound($rootRound);
        // (new StructureOutput())->output($structure, console);

        self::assertCount(2, $rootRound->getChildren());
    }

    // too little placesperpoule
    public function testIncrementNrOfPoules1(): void
    {
        $competition = $this->createCompetition();

        $ranges = new PlaceRange(2, 100, new SportRange(2, 10));
        $structureEditor = $this->createStructureEditor([$ranges]);
        $structure = $structureEditor->create($competition, [3, 2]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        self::expectException(Exception::class);
        $structureEditor->incrementNrOfPoules($rootRound);
    }

    // middleRound
    public function testIncrementNrOfPoules2(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [5, 5]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [3, 3]);
        $structureEditor->addChildRound($winnersRound, QualifyTarget::WINNERS, [4]);
        // (new StructureOutput())->output($structure, console);

        $structureEditor->incrementNrOfPoules($winnersRound);
        // (new StructureOutput())->output($structure, console);

        self::assertSame(6, $winnersRound->getNrOfPlaces());
    }

    // too little poules
    public function testDecrementNrOfPoules1(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [3]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        self::expectException(Exception::class);
        $structureEditor->decrementNrOfPoules($rootRound);
    }

    public function testDecrementNrOfPoules2(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [3, 3, 2]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $structureEditor->decrementNrOfPoules($rootRound);

        self::assertCount(2, $rootRound->getPoules());
        self::assertCount(4, $rootRound->getFirstPoule()->getPlaces());
        self::assertCount(4, $rootRound->getLastPoule()->getPlaces());
    }

    // new Round too little qualifiers
    public function testAddQualifiers1(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [4]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        self::expectException(Exception::class);
        $structureEditor->addQualifiers($rootRound, QualifyTarget::WINNERS, 1);
    }

    // out of range
    public function testAddQualifiers2(): void
    {
        $competition = $this->createCompetition();
        $ranges = new PlaceRange(2, 6, new SportRange(2, 4));
        $structureEditor = $this->createStructureEditor([$ranges]);
        $structure = $structureEditor->create($competition, [3,3]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [4]);

        self::expectException(Exception::class);
        $structureEditor->addQualifiers($rootRound, QualifyTarget::WINNERS, 1);
    }

    // new Round
    public function testAddQualifiers3(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [4]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $structureEditor->addQualifiers($rootRound, QualifyTarget::WINNERS, 2);

        $winnersRound = $rootRound->getChild(QualifyTarget::WINNERS, 1);
        self::assertNotNull($winnersRound);
        self::assertSame(2, $winnersRound->getNrOfPlaces());
    }

    // 3 levels deep
    public function testRemoveQualifiers1(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [4,4,4,4]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $quarterFinals = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2, 2, 2, 2]);
        $semiFinals = $structureEditor->addChildRound($quarterFinals, QualifyTarget::WINNERS, [2, 2]);
        $final = $structureEditor->addChildRound($semiFinals, QualifyTarget::WINNERS, [2]);

        //(new StructureOutput())->output($structure);
        $structureEditor->removeQualifier($rootRound, QualifyTarget::WINNERS);
        // (new StructureOutput())->output($structure);
        $structureEditor->removeQualifier($rootRound, QualifyTarget::WINNERS);
        // (new StructureOutput())->output($structure);
        $structureEditor->removeQualifier($rootRound, QualifyTarget::WINNERS);
        // (new StructureOutput())->output($structure);
        $structureEditor->removeQualifier($rootRound, QualifyTarget::WINNERS);
        // (new StructureOutput())->output($structure);
        $structureEditor->removeQualifier($rootRound, QualifyTarget::WINNERS);
        // (new StructureOutput())->output($structure);

        $newSemiFinals = $rootRound->getChild(QualifyTarget::WINNERS, 1);
        self::assertNotNull($newSemiFinals);
        $newFinal = $newSemiFinals->getChild(QualifyTarget::WINNERS, 1);
        self::assertNotNull($newFinal);

        self::assertSame(3, $newSemiFinals->getNrOfPlaces());
        self::assertSame(2, $newFinal->getNrOfPlaces());
    }

    // empty NextRoundNumber
    public function testRemoveQualifiers2(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [4,4,4,4]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        self::assertSame($firstRoundNumber, $rootRound->getNumber());
        self::assertSame($firstRoundNumber, $structure->getLastRoundNumber());

        // (new StructureOutput())->output($structure, console);
        $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        // (new StructureOutput())->output($structure, console);
        $structureEditor->removeQualifier($rootRound, QualifyTarget::WINNERS);
        // (new StructureOutput())->output($structure, console);

        self::assertCount(0, $rootRound->getTargetQualifyGroups(QualifyTarget::WINNERS));
        self::assertCount(1, $structure->getRoundNumbers());

        self::assertNull($structure->getRoundNumber(2));
        self::assertNull($structure->getFirstRoundNumber()->getNext());
    }

    // too few places per poule
    public function testSplitQualifyGroupFrom1(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [6]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstSix = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [3]);

        $qualifyGroup = $firstSix->getParentQualifyGroup();
        self::assertNotNull($qualifyGroup);

        $firstSingleQualifyRule = $qualifyGroup->getFirstSingleRule();
        self::assertNotNull($firstSingleQualifyRule);
        // (new StructureOutput())->output($structure, console);

        self::expectException(Exception::class);
        $structureEditor->splitQualifyGroupFrom($qualifyGroup, $firstSingleQualifyRule);
    }

    // keep nrOfPoulePlaces
    public function testSplitQualifyGroupFrom2(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [3, 3, 3, 3, 3, 3]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstSix = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [6, 6]);

        $qualifyGroup = $firstSix->getParentQualifyGroup();
        self::assertNotNull($qualifyGroup);

        $firstSingleQualifyRule = $qualifyGroup->getFirstSingleRule();
        self::assertNotNull($firstSingleQualifyRule);

        // (new StructureOutput())->output($structure, console);
        $structureEditor->splitQualifyGroupFrom($qualifyGroup, $firstSingleQualifyRule);
        // (new StructureOutput())->output($structure, console);
    }

    // no
    public function testIsQualifyGroupSplittableAt1(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [8]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $nextRound = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [3]);
        $qualifyGroup = $nextRound->getParentQualifyGroup();
        self::assertNotNull($qualifyGroup);

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
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [3,3]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [3]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [3]);
        // (new StructureOutput())->output($structure, console);

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
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [4,4]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $nextRound = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [4]);
        // (new StructureOutput())->output($structure, console);

        $qualifyGroup = $nextRound->getParentQualifyGroup();
        self::assertNotNull($qualifyGroup);

        $singleRule = $qualifyGroup->getFirstSingleRule();
        self::assertNotNull($singleRule);
        self::assertTrue($structureEditor->isQualifyGroupSplittableAt($singleRule));
    }

    public function testAreQualifyGroupsMergable(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [4,4]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstSecond = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        $thirdFourth = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        $fifthSixth = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [2]);

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
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [5,5]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstSecond = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        $thirdFourth = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        $fifthSixth = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        $worstLlosersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [2]);

        $firstSecondQualifyGroup = $firstSecond->getParentQualifyGroup();
        $thirdFourthQualifyGroup = $thirdFourth->getParentQualifyGroup();
        self::assertNotNull($firstSecondQualifyGroup);
        self::assertNotNull($thirdFourthQualifyGroup);

        $structureEditor->mergeQualifyGroups($firstSecondQualifyGroup, $thirdFourthQualifyGroup);
        self::assertSame(4,$firstSecond->getNrOfPlaces());
    }

    // W3/4 W5/6
    public function testMergeQualifyGroups2(): void
    {
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [5,5]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstSecond = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        $thirdFourth = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        $fifthSixth = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        $worstLlosersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [2]);

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
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [5,5]);
        $rootRound = $structure->getRootRound();
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstSecond = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        $thirdFourth = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        $fifthSixth = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        $worstLosersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [2]);

        $worstLosersQualifyGroup = $worstLosersRound->getParentQualifyGroup();
        $losersQualifyGroup = $losersRound->getParentQualifyGroup();
        self::assertNotNull($worstLosersQualifyGroup);
        self::assertNotNull($losersQualifyGroup);

        $structureEditor->mergeQualifyGroups($worstLosersQualifyGroup, $losersQualifyGroup);
        self::assertSame(4, $worstLosersRound->getNrOfPlaces());
    }
}
