<?php

declare(strict_types=1);

namespace Sports\Tests\Qualify\Rule;

use PHPUnit\Framework\TestCase;
use Sports\Place;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\QualifyTarget as QualifyTarget;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\SetScores;
use Sports\TestHelper\StructureEditorCreator;

class DefaultCreatorTest extends TestCase
{
    use CompetitionCreator;
    use SetScores;
    use StructureEditorCreator;

    public function testPlacesNotSameParentSimple(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3, 3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2, 2, 2]);

        $qualifyGroup = $rootRound->getQualifyGroup(QualifyTarget::Winners, 1);
        self::assertInstanceOf(QualifyGroup::class, $qualifyGroup);

        // (new StructureOutput())->output($structure);

        foreach ($winnersRound->getPoules() as $poule) {
            $fromPlace1 = $qualifyGroup->getFromPlace($poule->getPlace(1));
            $fromPlace2 = $qualifyGroup->getFromPlace($poule->getPlace(2));
            self::assertFalse($fromPlace1?->getPoule() === $fromPlace2?->getPoule());
        }
    }

    public function testPlacesNotSameParent(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4, 4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2, 2, 2, 2, 2, 2, 2]);

        $qualifyGroup = $rootRound->getQualifyGroup(QualifyTarget::Winners, 1);
        self::assertInstanceOf(QualifyGroup::class, $qualifyGroup);

        // (new StructureOutput())->output($structure);

        foreach ($winnersRound->getPoules() as $poule) {
            $fromPlace1 = $qualifyGroup->getFromPlace($poule->getPlace(1));
            $fromPlace2 = $qualifyGroup->getFromPlace($poule->getPlace(2));
            self::assertFalse($fromPlace1?->getPoule() === $fromPlace2?->getPoule());
        }
    }

    public function testPlacesNotSameGrandParent(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();


        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2, 2, 2, 2, 2, 2, 2]);
        $firstQualifyGroup = $rootRound->getQualifyGroup(QualifyTarget::Winners, 1);
        self::assertInstanceOf(QualifyGroup::class, $firstQualifyGroup);
        $quarterFinal = $structureEditor->addChildRound($winnersRound, QualifyTarget::Winners, [2, 2, 2, 2]);
        $lastQualifyGroup = $winnersRound->getQualifyGroup(QualifyTarget::Winners, 1);
        self::assertInstanceOf(QualifyGroup::class, $lastQualifyGroup);

        // (new StructureOutput())->output($structure);

        foreach ($quarterFinal->getPoules() as $poule) {
            $fromPlace1 = $lastQualifyGroup->getFromPlace($poule->getPlace(1));
            self::assertInstanceOf(Place::class, $fromPlace1);
            $fromPlace2 = $lastQualifyGroup->getFromPlace($poule->getPlace(2));
            self::assertInstanceOf(Place::class, $fromPlace2);
            $grandParentPlaces1 = [];
            foreach ($fromPlace1->getPoule()->getPlaces() as $parentPlace) {
                $grandParentPlace = $firstQualifyGroup->getFromPlace($parentPlace);
                self::assertInstanceOf(Place::class, $grandParentPlace);
                $grandParentPlaces1[] = $grandParentPlace;
            }
            $grandParentPlaces2 = [];
            foreach ($fromPlace2->getPoule()->getPlaces() as $parentPlace) {
                $grandParentPlace = $firstQualifyGroup->getFromPlace($parentPlace);
                self::assertInstanceOf(Place::class, $grandParentPlace);
                $grandParentPlaces2[] = $grandParentPlace;
            }
            $grandParentPoules1 = array_map(fn (Place $place) => $place->getPoule(), $grandParentPlaces1);
            $grandParentPoules2 = array_map(fn (Place $place) => $place->getPoule(), $grandParentPlaces2);
            foreach ($grandParentPoules1 as $grandParentPoule1) {
                $msg = 'poule "' . $poule->getNumber() . '" should not be among poules';
                self::assertFalse(array_search($grandParentPoule1, $grandParentPoules2, true), $msg);
            }
        }
    }

    public function testPerformance(): void
    {
        $time_start = microtime(true);
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [8, 8, 8, 8, 8]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $secondRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [6,6,6,6,6]);
        $thirdRound = $structureEditor->addChildRound($secondRound, QualifyTarget::Winners, [4,4,4,4,4]);
        $fourthRound = $structureEditor->addChildRound($thirdRound, QualifyTarget::Winners, [2, 2, 2, 2, 2, 2, 2, 2]);
        $quarterFinal = $structureEditor->addChildRound($fourthRound, QualifyTarget::Winners, [2, 2, 2, 2]);
        $semiFinal = $structureEditor->addChildRound($quarterFinal, QualifyTarget::Winners, [2, 2]);
        $structureEditor->addChildRound($semiFinal, QualifyTarget::Winners, [2]);

        // (new StructureOutput())->output($structure);

        // echo 'Total Execution Time: '. (microtime(true) - $time_start);
        self::assertTrue((microtime(true) - $time_start) < 0.3);
    }
}
