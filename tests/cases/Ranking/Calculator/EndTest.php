<?php

declare(strict_types=1);

namespace Sports\Tests\Ranking\Calculator;

use PHPUnit\Framework\TestCase;
use Sports\Place\Location as PlaceLocation;
use Sports\Qualify\Service as QualifyService;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Ranking\AgainstRuleSet;
use Sports\Ranking\Calculator\End as EndRankingCalculator;
use Sports\Ranking\Item\End as EndRankingItem;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\SetScores;
use Sports\TestHelper\StructureEditorCreator;

class EndTest extends TestCase
{
    use CompetitionCreator;
    use SetScores;
    use StructureEditorCreator;

    public function testOnePouleOfThreePlaces(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3]);
        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setScoreSingle($pouleOne, 1, 2, 2, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 3, 2);

        $calculator = new EndRankingCalculator($structure, AgainstRuleSet::DiffFirst);
        $items = $calculator->getItems();

        for ($rank = 1; $rank <= count($items); $rank++) {
            $endRankingItem = array_shift($items);
            self::assertInstanceOf(EndRankingItem::class, $endRankingItem);
            $placeLocation = $endRankingItem->getPlaceLocation();
            self::assertInstanceOf(PlaceLocation::class, $placeLocation);
            self::assertSame($placeLocation->getPlaceNr(), $rank);
            self::assertSame($endRankingItem->getUniqueRank(), $rank);
        }
    }

    public function testOnePouleOfThreePlacesNotPlayed(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3]);
        $rootRound = $structure->getRootRound();

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setScoreSingle($pouleOne, 1, 2, 2, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1);
        // $this->setScoreSingle($pouleOne, 2, 3, 3, 2);

        $calculator = new EndRankingCalculator($structure, AgainstRuleSet::DiffFirst);
        $items = $calculator->getItems();

        for ($rank = 1; $rank <= count($items); $rank++) {
            $endRankingItem = array_shift($items);
            self::assertInstanceOf(EndRankingItem::class, $endRankingItem);
            self::assertNull($endRankingItem->getPlaceLocation());
        }
    }

    public function testTwoRoundNumbers5(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5]);
        $rootRound = $structure->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setScoreSingle($pouleOne, 1, 2, 2, 1);
        $this->setScoreSingle($pouleOne, 1, 3, 3, 1);
        $this->setScoreSingle($pouleOne, 1, 4, 4, 1);
        $this->setScoreSingle($pouleOne, 1, 5, 5, 1);
        $this->setScoreSingle($pouleOne, 2, 3, 3, 2);
        $this->setScoreSingle($pouleOne, 2, 4, 4, 2);
        $this->setScoreSingle($pouleOne, 2, 5, 5, 2);
        $this->setScoreSingle($pouleOne, 3, 4, 4, 3);
        $this->setScoreSingle($pouleOne, 3, 5, 5, 3);
        $this->setScoreSingle($pouleOne, 4, 5, 5, 4);

        $winnersPoule = $winnersRound->getPoule(1);
        $this->setScoreSingle($winnersPoule, 1, 2, 2, 1);
        $loserssPoule = $losersRound->getPoule(1);
        $this->setScoreSingle($loserssPoule, 1, 2, 2, 1);

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers();

        $calculator = new EndRankingCalculator($structure, AgainstRuleSet::DiffFirst);
        $items = $calculator->getItems();

        for ($rank = 1; $rank <= count($items); $rank++) {
            $endRankingItem = array_shift($items);
            self::assertNotNull($endRankingItem);
            $placeLocation = $endRankingItem->getPlaceLocation();
            self::assertInstanceOf(PlaceLocation::class, $placeLocation);
            self::assertSame($placeLocation->getPlaceNr(), $rank);
        }
    }
}
