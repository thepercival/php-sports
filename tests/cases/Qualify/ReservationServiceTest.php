<?php

namespace Sports\Tests\Qualify;

use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\SetScores;
use Sports\Structure\Service as StructureService;
use Sports\Qualify\Service as QualifyService;
use Sports\Ranking\Service as RankingService;
use Sports\Qualify\ReservationService as QualifyReservationService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Competitor;

class ReservationServiceTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, SetScores;

    public function testFreeAndReserve()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);
        $rootRound = $structure->getRootRound();
        $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
        $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);

        (new GamesCreator())->createStructureGames( $structure );

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

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers();

        $winnersRound = $rootRound->getChild(QualifyGroup::WINNERS, 1);
        $resService = new QualifyReservationService($winnersRound);

        self::assertTrue($resService->isFree(1, $pouleOne));

        $resService->reserve(1, $pouleOne);
        self::assertFalse($resService->isFree(1, $pouleOne));
    }

    public function testFreeAndLeastAvailabe()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 12, 4);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 6);

        $structureService->addPoule($rootRound->getChild(QualifyGroup::WINNERS, 1));

        (new GamesCreator())->createStructureGames( $structure );

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);
        $pouleThree = $rootRound->getPoule(3);
        $pouleFour = $rootRound->getPoule(4);

        $this->setScoreSingle($pouleOne, 1, 2, 1, 2);
        $this->setScoreSingle($pouleOne, 1, 3, 1, 3);
        $this->setScoreSingle($pouleOne, 2, 3, 2, 3);
        $this->setScoreSingle($pouleTwo, 1, 2, 1, 2);
        $this->setScoreSingle($pouleTwo, 1, 3, 1, 3);
        $this->setScoreSingle($pouleTwo, 2, 3, 2, 4);
        $this->setScoreSingle($pouleThree, 1, 2, 1, 5);
        $this->setScoreSingle($pouleThree, 1, 3, 1, 3);
        $this->setScoreSingle($pouleThree, 2, 3, 2, 5);
        $this->setScoreSingle($pouleFour, 1, 2, 1, 2);
        $this->setScoreSingle($pouleFour, 1, 3, 1, 3);
        $this->setScoreSingle($pouleFour, 2, 3, 2, 3);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers();

        $winnersRound = $rootRound->getChild(QualifyGroup::WINNERS, 1);
        $resService = new QualifyReservationService($winnersRound);

        $resService->reserve(1, $pouleOne);
        $resService->reserve(2, $pouleOne);
        $resService->reserve(3, $pouleOne);

        $resService->reserve(1, $pouleTwo);
        $resService->reserve(2, $pouleTwo);

        $resService->reserve(1, $pouleThree);
        $resService->reserve(1, $pouleThree);

        $resService->reserve(1, $pouleFour);
        $resService->reserve(3, $pouleFour);


        $horPoule = $rootRound->getHorizontalPoule(QualifyGroup::WINNERS, 1);

        // none available
        $placeLocationOne = $resService->getFreeAndLeastAvailabe(1, $rootRound, $horPoule->getPlaces());
        self::assertSame($placeLocationOne->getPouleNr(), $pouleOne->getNumber());

        // two available, three least available
        $placeLocationThree = $resService->getFreeAndLeastAvailabe(3, $rootRound, $horPoule->getPlaces());
        self::assertSame($placeLocationThree->getPouleNr(), $pouleTwo->getNumber());
    }

    public function testTwoRoundNumbersMultipleRuleNotPlayed333()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 9);
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);

        $structureService->removePoule($rootRound->getChild(QualifyGroup::WINNERS, 1));

        (new GamesCreator())->createStructureGames( $structure );

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);
        $pouleThree = $rootRound->getPoule(3);

        $this->setScoreSingle($pouleOne, 1, 2, 1, 2);
        $this->setScoreSingle($pouleOne, 1, 3, 1, 3);
        $this->setScoreSingle($pouleOne, 2, 3, 2, 3);
        $this->setScoreSingle($pouleTwo, 1, 2, 1, 2);
        $this->setScoreSingle($pouleTwo, 1, 3, 1, 3);
        $this->setScoreSingle($pouleTwo, 2, 3, 2, 4);
        $this->setScoreSingle($pouleThree, 1, 2, 1, 5);
        $this->setScoreSingle($pouleThree, 1, 3, 1, 3);
        // $this->setScoreSingle(pouleThree, 2, 3, 2, 5);

        $qualifyService = new QualifyService($rootRound, RankingService::RULESSET_WC);
        $qualifyService->setQualifiers();

        $winnersPoule = $rootRound->getChild(QualifyGroup::WINNERS, 1)->getPoule(1);

        self::assertNull($winnersPoule->getPlace(4)->getQualifiedPlace() );
    }
}
