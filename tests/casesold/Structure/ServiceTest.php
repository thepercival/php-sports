<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-6-19
 * Time: 13:14
 */

namespace Sports\Tests\Structure;

include_once __DIR__ . '/../../data/CompetitionCreator.php';
include_once __DIR__ . '/Check332a.php';

use Sports\Structure\Service as StructureService;
use Sports\Range;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Poule\Horizontal as HorizontalPoule;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    use Check332a;

    public function testCreating332a()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 8, 3);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 4);

        foreach ([QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers) {
            $childRound = $rootRound->getBorderQualifyGroup($winnersOrLosers)->getChildRound();
            $structureService->addQualifier($childRound, QualifyGroup::WINNERS);
            $structureService->addQualifier($childRound, QualifyGroup::LOSERS);
        }

        $this->check332astructure($structure);
    }

    public function testDefaultPoules()
    {
        $structureService = new StructureService(new Range(3, 40));

        $this->assertSame($structureService->getDefaultNrOfPoules(3), 1);
        $this->assertSame($structureService->getDefaultNrOfPoules(40), 8);

        $structureService2 = new StructureService();
        $this->assertSame($structureService2->getDefaultNrOfPoules(2), 1);
        $this->assertSame($structureService2->getDefaultNrOfPoules(41), 8);
    }

    public function testDefaultPoulesOutOfRange1()
    {
        $structureService = new StructureService(new Range(3, 40));

        $this->expectException(\Exception::class);
        $structureService->getDefaultNrOfPoules(2);
    }

    public function testDefaultPoulesOutOfRange2()
    {
        $structureService = new StructureService(new Range(3, 40));

        $this->expectException(\Exception::class);
        $structureService->getDefaultNrOfPoules(41);
    }

    public function testDefaultPoulesOutOfRange3()
    {
        $structureService2 = new StructureService();

        $this->expectException(\Exception::class);
        $structureService2->getDefaultNrOfPoules(1);
    }

    public function testMinimumNrOfPlacesPerPoule()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 6, 3);
        $rootRound = $structure->getRootRound();

        $this->expectException(\Exception::class);
        $structureService->removePlaceFromRootRound($rootRound);
    }

    public function testMinimumNrOfPlacesAndPoules()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 4, 2);
        $rootRound = $structure->getRootRound();

        $structureService->removePoule($rootRound, false);

        $this->expectException(\Exception::class);
        $structureService->removePoule($rootRound, false);
    }

    public function testMaximumNrOfPlaces()
    {
        $competition = createCompetition();

        $structureService = new StructureService(new Range(3, 40));
        $structure = $structureService->create($competition, 36, 6);
        $rootRound = $structure->getRootRound();

        $structureService->removePoule($rootRound, false);

        $this->expectException(\Exception::class);
        $structureService->addPoule($rootRound, true);
    }

    public function testMinimumNrOfQualifiers()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 4, 2);
        $rootRound = $structure->getRootRound();

        $structureService->addPlaceToRootRound($rootRound);
        $structureService->addPlaceToRootRound($rootRound);

        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);

        $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);

        $structureService->removePlaceFromRootRound($rootRound);
        try {
            $structureService->removePlaceFromRootRound($rootRound);
            $this->assertSame(true, false);
        } catch (\Exception $e) {
        }

        $structureService->addPlaceToRootRound($rootRound);

        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
        $structureService->removeQualifier($rootRound, QualifyGroup::LOSERS);

        $this->expectException(\Exception::class);
        $structureService->removePoule($rootRound, true);
    }

    public function testMaximumNrOfQualifiers()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 4, 2);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);

        $structureService->removeQualifier($rootRound, QualifyGroup::WINNERS);
        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);

        $this->expectException(\Exception::class);
        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
    }

    public function testQualifiersAvailable()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 8, 2);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);


        $structureService->removePoule($rootRound, true);


        $this->expectException(\Exception::class);
        $structureService->removePoule($rootRound, true);
    }


    public function testRangeMinimum()
    {
        $competition = createCompetition();

        $structureService = new StructureService(new Range(3, 40));
        $structure = $structureService->create($competition, 3, 1);
        $rootRound = $structure->getRootRound();

        $this->expectException(\Exception::class);
        $structureService->removePlaceFromRootRound($rootRound);
    }

    public function testRangeMaximum()
    {
        $competition = createCompetition();

        $structureService = new StructureService(new Range(3, 40));
        $structure = $structureService->create($competition, 40, 4);
        $rootRound = $structure->getRootRound();

        $this->expectException(\Exception::class);
        $structureService->addPlaceToRootRound($rootRound);
    }

    public function testRemovePouleNextRound()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 6);
        $rootRound = $structure->getRootRound();

        $structureService->addPoule($rootRound, true);

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);

        $childRound = $rootRound->getBorderQualifyGroup(QualifyGroup::WINNERS)->getChildRound();

        $structureService->removePoule($childRound);
        $structureService->addPoule($childRound);
        $structureService->removePoule($childRound);

        $this->assertSame($childRound->getPoules()->count(), 1);
        $this->assertSame($childRound->getNrOfPlaces(), 4);
    }

    public function testQualifyGroupSplittableWinners332()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 8, 3);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);
        {
            $borderQualifyGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::WINNERS);

            $this->assertSame(count($borderQualifyGroup->getHorizontalPoules()), 2);

            $horPoule1 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 1);
            $horPoule2 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 2);

            $this->assertSame($structureService->isQualifyGroupSplittable($horPoule1, $horPoule2), false);

            try {
                $structureService->splitQualifyGroup($borderQualifyGroup, $horPoule1, $horPoule2);
                $this->assertSame(true, false);
            } catch (\Exception $e) {
            }
        }

        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);

        {
            $borderQualifyGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::WINNERS);
            $this->assertSame(count($borderQualifyGroup->getHorizontalPoules()), 2);

            $horPoule1 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 1);
            $horPoule2 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 2);

            $this->assertSame($structureService->isQualifyGroupSplittable($horPoule1, $horPoule2), true);

            $structureService->splitQualifyGroup($borderQualifyGroup, $horPoule1, $horPoule2);
        }
    }

    public function testQualifyGroupSplittableLosers332()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 8, 3);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 4);
        {
            $borderQualifyGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::LOSERS);

            $this->assertSame(count($borderQualifyGroup->getHorizontalPoules()), 2);

            $horPoule1 = $rootRound->getHorizontalPoule(QualifyGroup::LOSERS, 1);
            $horPoule2 = $rootRound->getHorizontalPoule(QualifyGroup::LOSERS, 2);

            $this->assertSame($structureService->isQualifyGroupSplittable($horPoule1, $horPoule2), false);

            try {
                $structureService->splitQualifyGroup($borderQualifyGroup, $horPoule1, $horPoule2);
                $this->assertSame(true, false);
            } catch (\Exception $e) {
            }
        }

        $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);

        {
            $borderQualifyGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::LOSERS);
            $this->assertSame(count($borderQualifyGroup->getHorizontalPoules()), 2);

            $horPoule1 = $rootRound->getHorizontalPoule(QualifyGroup::LOSERS, 1);
            $horPoule2 = $rootRound->getHorizontalPoule(QualifyGroup::LOSERS, 2);

            $this->assertSame($structureService->isQualifyGroupSplittable($horPoule1, $horPoule2), true);

            $structureService->splitQualifyGroup($borderQualifyGroup, $horPoule1, $horPoule2);
        }
    }

    public function testQualifyGroupSplittableWinners331()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 7, 3);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);

        $borderQualifyGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::WINNERS);

        $this->assertSame(count($borderQualifyGroup->getHorizontalPoules()), 2);

        $horPoule1 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 1);
        $horPoule2 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 2);

        $this->assertSame($structureService->isQualifyGroupSplittable($horPoule1, $horPoule2), false);

        try {
            $structureService->splitQualifyGroup($borderQualifyGroup, $horPoule1, $horPoule2);
            $this->assertSame(true, false);
        } catch (\Exception $e) {
        }

        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);

        $borderQualifyGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::WINNERS);

        $this->assertSame(count($borderQualifyGroup->getHorizontalPoules()), 2);

        $horPoule1 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 1);
        $horPoule2 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 2);

        $this->assertSame($structureService->isQualifyGroupSplittable($horPoule1, $horPoule2), true);

        $structureService->splitQualifyGroup($borderQualifyGroup, $horPoule1, $horPoule2);
    }

    public function testQualifyGroupSplittableLosers331()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 7, 3);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 4);

        $borderQualifyGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::LOSERS);

        $this->assertSame(count($borderQualifyGroup->getHorizontalPoules()), 2);

        $horPoule1 = $rootRound->getHorizontalPoule(QualifyGroup::LOSERS, 1);
        $horPoule2 = $rootRound->getHorizontalPoule(QualifyGroup::LOSERS, 2);

        $this->assertSame($structureService->isQualifyGroupSplittable($horPoule1, $horPoule2), false);

        try {
            $structureService->splitQualifyGroup($borderQualifyGroup, $horPoule1, $horPoule2);
            $this->assertSame(true, false);
        } catch (\Exception $e) {
        }

        $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);

        $borderQualifyGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::LOSERS);

        $this->assertSame(count($borderQualifyGroup->getHorizontalPoules()), 2);

        $horPoule1 = $rootRound->getHorizontalPoule(QualifyGroup::LOSERS, 1);
        $horPoule2 = $rootRound->getHorizontalPoule(QualifyGroup::LOSERS, 2);

        $this->assertSame($structureService->isQualifyGroupSplittable($horPoule2, $horPoule1), true);

        $structureService->splitQualifyGroup($borderQualifyGroup, $horPoule2, $horPoule1);
    }

    public function testQualifyGroupSplitOrder()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 12, 2);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 12);

        $borderQualifyGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::WINNERS);

        $this->assertSame(count($borderQualifyGroup->getHorizontalPoules()), 6);

        $horPoule4 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 4);
        $horPoule5 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 5);

        // nrs 1 t/ 4(8) opgesplits van nrs 5 t/m 6(4)
        $structureService->splitQualifyGroup($borderQualifyGroup, $horPoule4, $horPoule5);

        $horPoule2 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 2);
        $horPoule3 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 3);

        $firstQualifyGroup = $rootRound->getQualifyGroups(QualifyGroup::WINNERS)->first();

        // nrs 1 t/ 2(4), nrs 3 t/ 4(4) en nrs 5 t/m 6(4)
        $structureService->splitQualifyGroup($firstQualifyGroup, $horPoule2, $horPoule3);

        $qualifyGroup12 = $rootRound->getQualifyGroups(QualifyGroup::WINNERS)->first();
        $qualifyGroup56 = $rootRound->getBorderQualifyGroup(QualifyGroup::WINNERS);

        $horPoule1 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 1);
        $horPoule2 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 2);
        $horPoule3 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 3);
        $horPoule4 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 4);
        $horPoule5 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 5);
        $horPoule6 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 6);

        $hasHorPoule = function (QualifyGroup $qualifyGroup, HorizontalPoule $horPoule): bool {
            $foundHorPoules = array_filter($qualifyGroup->getHorizontalPoules(), function ($horPouleIt) use ($horPoule) {
                return $horPouleIt === $horPoule;
            });
            return count($foundHorPoules) > 0;
        };
        $this->assertSame($hasHorPoule($qualifyGroup12, $horPoule1), true);
        $this->assertSame($hasHorPoule($qualifyGroup12, $horPoule2), true);
        $this->assertSame($hasHorPoule($qualifyGroup12, $horPoule3), false);
        $this->assertSame($hasHorPoule($qualifyGroup12, $horPoule4), false);
        $this->assertSame($hasHorPoule($qualifyGroup56, $horPoule5), true);
        $this->assertSame($hasHorPoule($qualifyGroup56, $horPoule6), true);
        $this->assertSame($hasHorPoule($qualifyGroup56, $horPoule3), false);
        $this->assertSame($hasHorPoule($qualifyGroup56, $horPoule4), false);
    }

    public function testQualifyGroupMergable33()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 6, 3);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 3);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 3);

        $winnersBorderQualifyGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::WINNERS);
        $losersBorderQualifyGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::LOSERS);

        $this->assertSame($structureService->areQualifyGroupsMergable($losersBorderQualifyGroup, $winnersBorderQualifyGroup), false);

        try {
            $structureService->mergeQualifyGroups($losersBorderQualifyGroup, $winnersBorderQualifyGroup);
            $this->assertSame(true, false);
        } catch (\Exception $e) {
        }
    }

    public function testQualifyGroupUnmergableWinners544()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 13, 3);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 5);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 5);

        $winnersBorderQualifyGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::WINNERS);
        $losersBorderQualifyGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::LOSERS);

        $this->assertSame($structureService->areQualifyGroupsMergable($winnersBorderQualifyGroup, $winnersBorderQualifyGroup), false);
        $this->assertSame($structureService->areQualifyGroupsMergable($losersBorderQualifyGroup, $winnersBorderQualifyGroup), false);

        try {
            $structureService->mergeQualifyGroups($losersBorderQualifyGroup, $winnersBorderQualifyGroup);
            $this->assertSame(true, false);
        } catch (\Exception $e) {
        }
    }

    public function testQualifyGroupMergable544()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 13, 3);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 5);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 5);

        $winnersBorderQualifyGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::WINNERS);
        $winHorPoule1 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 1);
        $winHorPoule2 = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 2);

        // nrs 1 en 2 beste nummers 2 opsplitsen
        $structureService->splitQualifyGroup($winnersBorderQualifyGroup, $winHorPoule1, $winHorPoule2);

        $winnersBorderQualifyGroups = $rootRound->getQualifyGroups(QualifyGroup::WINNERS);
        $structureService->mergeQualifyGroups(
            $winnersBorderQualifyGroups->last(),
            $winnersBorderQualifyGroups->first()
        );

        $losersBorderQualifyGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::LOSERS);
        $losHorPoule1 = $rootRound->getHorizontalPoule(QualifyGroup::LOSERS, 1);
        $losHorPoule2 = $rootRound->getHorizontalPoule(QualifyGroup::LOSERS, 2);

        // nrs laatst en 2 beste nrs 1 na laatst opsplitsen
        $structureService->splitQualifyGroup($losersBorderQualifyGroup, $losHorPoule1, $losHorPoule2);

        $losersBorderQualifyGroups = $rootRound->getQualifyGroups(QualifyGroup::LOSERS);
        $structureService->mergeQualifyGroups($losersBorderQualifyGroups->last(), $losersBorderQualifyGroups->first());

        // no exceptions
        $this->assertSame(true, true);
    }
}
