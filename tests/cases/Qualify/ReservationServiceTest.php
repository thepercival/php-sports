<?php

declare(strict_types=1);

namespace Sports\Tests\Qualify;

use Sports\Qualify\Target as QualifyTarget;
use PHPUnit\Framework\TestCase;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\SetScores;
use Sports\Qualify\Service as QualifyService;
use Sports\Qualify\ReservationService as QualifyReservationService;
use Sports\TestHelper\StructureEditorCreator;

final class ReservationServiceTest extends TestCase
{
    use CompetitionCreator;
    use SetScores;
    use StructureEditorCreator;

    public function testFreeAndReserve(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5]);
        $rootRound = $structure->getSingleCategory()->getRootRound();
        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);

        $winnersRound = $rootRound->getChild(QualifyTarget::Winners, 1);
        self::assertNotNull($winnersRound);

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);

        $this->setAgainstScore($pouleOne, 1, 2, 2, 1);
        $this->setAgainstScore($pouleOne, 1, 3, 3, 1);
        $this->setAgainstScore($pouleOne, 1, 4, 4, 1);
        $this->setAgainstScore($pouleOne, 1, 5, 5, 1);
        $this->setAgainstScore($pouleOne, 2, 3, 3, 2);
        $this->setAgainstScore($pouleOne, 2, 4, 4, 2);
        $this->setAgainstScore($pouleOne, 2, 5, 5, 2);
        $this->setAgainstScore($pouleOne, 3, 4, 4, 3);
        $this->setAgainstScore($pouleOne, 3, 5, 5, 3);
        $this->setAgainstScore($pouleOne, 4, 5, 5, 4);

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers();

        $resService = new QualifyReservationService($winnersRound);

        self::assertTrue($resService->isFree(1, $pouleOne));

        $resService->reserve(1, $pouleOne);
        self::assertFalse($resService->isFree(1, $pouleOne));
    }

    public function testFreeAndLeastAvailabe(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3, 3, 3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2, 2]);

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);
        $pouleThree = $rootRound->getPoule(3);
        $pouleFour = $rootRound->getPoule(4);

        $this->setAgainstScore($pouleOne, 1, 2, 1, 2);
        $this->setAgainstScore($pouleOne, 1, 3, 1, 3);
        $this->setAgainstScore($pouleOne, 2, 3, 2, 3);
        $this->setAgainstScore($pouleTwo, 1, 2, 1, 2);
        $this->setAgainstScore($pouleTwo, 1, 3, 1, 3);
        $this->setAgainstScore($pouleTwo, 2, 3, 2, 4);
        $this->setAgainstScore($pouleThree, 1, 2, 1, 5);
        $this->setAgainstScore($pouleThree, 1, 3, 1, 3);
        $this->setAgainstScore($pouleThree, 2, 3, 2, 5);
        $this->setAgainstScore($pouleFour, 1, 2, 1, 2);
        $this->setAgainstScore($pouleFour, 1, 3, 1, 3);
        $this->setAgainstScore($pouleFour, 2, 3, 2, 3);

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers();

        // $winnersRound = $rootRound->getChild(QualifyTarget::Winners, 1);
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


        $horPoule = $rootRound->getHorizontalPoule(QualifyTarget::Winners, 1);

        // none available
        $placeLocationOne = $resService->getFreeAndLeastAvailabe(
            1,
            $rootRound,
            array_values($horPoule->getPlaces()->toArray())
        );
        self::assertSame($placeLocationOne->getPouleNr(), $pouleOne->getNumber());

        // two available, three least available
        $placeLocationThree = $resService->getFreeAndLeastAvailabe(
            3,
            $rootRound,
            array_values($horPoule->getPlaces()->toArray())
        );
        self::assertSame($placeLocationThree->getPouleNr(), $pouleTwo->getNumber());
    }

    public function testTwoRoundNumbersMultipleRuleNotPlayed333(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3, 3]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [4]);

        (new GamesCreator())->createStructureGames($structure);

        $pouleOne = $rootRound->getPoule(1);
        $pouleTwo = $rootRound->getPoule(2);
        $pouleThree = $rootRound->getPoule(3);

        $this->setAgainstScore($pouleOne, 1, 2, 1, 2);
        $this->setAgainstScore($pouleOne, 1, 3, 1, 3);
        $this->setAgainstScore($pouleOne, 2, 3, 2, 3);
        $this->setAgainstScore($pouleTwo, 1, 2, 1, 2);
        $this->setAgainstScore($pouleTwo, 1, 3, 1, 3);
        $this->setAgainstScore($pouleTwo, 2, 3, 2, 4);
        $this->setAgainstScore($pouleThree, 1, 2, 1, 5);
        $this->setAgainstScore($pouleThree, 1, 3, 1, 3);
        // $this->setAgainstScore(pouleThree, 2, 3, 2, 5);

        $qualifyService = new QualifyService($rootRound);
        $qualifyService->setQualifiers();

        $winnersPoule = $winnersRound->getPoule(1);

        self::assertNull($winnersPoule->getPlace(4)->getQualifiedPlace());
    }
}
