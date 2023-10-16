<?php

declare(strict_types=1);

namespace Sports\Tests\Structure;

use Sports\Competitor\StartLocation;
use Sports\Competitor\StartLocationMap;
use Sports\Qualify\Distribution;
use Sports\Qualify\Target;
use Sports\Qualify\Target as QualifyTarget;
use PHPUnit\Framework\TestCase;
use Sports\Output\StructureOutput;
use Sports\Team;
use Sports\TestHelper\CompetitionCreator;
use Sports\Structure\NameService;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\StructureEditorCreator;

final class NameServiceTest extends TestCase
{
    use CompetitionCreator;
    use StructureEditorCreator;

    public function testQualifyTargetDescription(): void
    {
        $nameService = new NameService();

        self::assertSame($nameService->getQualifyTargetDescription(QualifyTarget::Winners), 'winnaar');
        self::assertSame($nameService->getQualifyTargetDescription(QualifyTarget::Losers), 'verliezer');
        self::assertSame($nameService->getQualifyTargetDescription(QualifyTarget::Winners, true), 'winnaars');
        self::assertSame($nameService->getQualifyTargetDescription(QualifyTarget::Losers, true), 'verliezers');
        self::assertSame($nameService->getQualifyTargetDescription(QualifyTarget::Dropouts), '');
    }

    public function testRoundNumberName(): void
    {
        $nameService = new NameService();
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3,3,2]);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2,2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2, 2]);
        // (new StructureOutput())->output($structure);
        $secondRoundNumber = $firstRoundNumber->getNext();
        self::assertNotNull($secondRoundNumber);
        // all equal
        self::assertSame('finale', $nameService->getRoundNumberName($secondRoundNumber));

        $structureEditor->addChildRound($losersRound, QualifyTarget::Losers, [2]);
        // (new StructureOutput())->output($structure);
        // not all equal
        self::assertSame('2e ronde', $nameService->getRoundNumberName($secondRoundNumber)); // '2<sup>de</sup> ronde'
    }

    public function testRoundNameRootNeedsRankingUnequalDepth(): void
    {
        $nameService = new NameService();
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [2, 2]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
        self::assertSame($nameService->getRoundName($rootRound), 'halve finale'); // '&frac12; finale'

        $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);
        self::assertSame($nameService->getRoundName($rootRound), 'halve finale'); // '&frac12; finale'
    }

    public function testRoundNameRootNeedsNoRankingUnequalDepth(): void
    {
        $nameService = new NameService();
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4, 4, 4, 4]);
        $rootRound = $structure->getSingleCategory()->getRootRound();

        self::assertSame($nameService->getRoundName($rootRound), '1e ronde'); // '1<sup>ste</sup> ronde'

        $rootRoundChild = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [3]);

        self::assertSame($nameService->getRoundName($rootRoundChild), '2e ronde'); // '2<sup>de</sup> ronde'
    }

    public function testRoundNameHtmlFractialNumber(): void
    {
        $nameService = new NameService();
        $competition = $this->createCompetition();

        // root needs ranking, depth 2
        {
            $structureEditor = $this->createStructureEditor();
            $structure = $structureEditor->create($competition, [2, 2, 2, 2, 2, 2, 2, 2]);
            $rootRound = $structure->getSingleCategory()->getRootRound();

            $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [4,4]);
            $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [4,4]);

            $winnersWinnersRound = $structureEditor->addChildRound($winnersRound, QualifyTarget::Winners, [4]);
            $losersLosersRound = $structureEditor->addChildRound($losersRound, QualifyTarget::Losers, [4]);

            self::assertSame('kwart finale', $nameService->getRoundName($rootRound)); // '&frac14; finale'

            $structureEditor->addChildRound($winnersWinnersRound, QualifyTarget::Winners, [2]);
            $losersFinal = $structureEditor->addChildRound($losersLosersRound, QualifyTarget::Losers, [2]);

            // '<span style="font-size: 80%"><sup>1</sup>&frasl;<sub>' . $number . '</sub></span> finale'
            self::assertSame('1/8 finale', $nameService->getRoundName($rootRound));

            self::assertSame(
                '15e/16e' . ' pl',
                $nameService->getRoundName($losersFinal)
            ); // '15<sup>de</sup>/16<sup>de</sup>'
        }
    }

    public function testPouleName(): void
    {
        $nameService = new NameService();
        $competition = $this->createCompetition();

        // basics
        {
            $structureEditor = $this->createStructureEditor();
            $poules = [];
            for ($i = 1; $i <= 29; $i++) {
                array_push($poules, 3);
            }
            array_push($poules, 2);
            $structure = $structureEditor->create($competition, $poules);
            $rootRound = $structure->getSingleCategory()->getRootRound();

            self::assertSame('A', $nameService->getPouleName($rootRound->getPoule(1), false));
            self::assertSame('poule A', $nameService->getPouleName($rootRound->getPoule(1), true));

            self::assertSame('AA', $nameService->getPouleName($rootRound->getPoule(27), false));
            self::assertSame('poule AA', $nameService->getPouleName($rootRound->getPoule(27), true));

            self::assertSame('AD', $nameService->getPouleName($rootRound->getPoule(30), false));
            self::assertSame('wed. AD', $nameService->getPouleName($rootRound->getPoule(30), true),);
        }
    }

    public function testPlaceName(): void
    {
        $competition = $this->createCompetition();

        // basics
        {
            $structureEditor = $this->createStructureEditor();
            $structure = $structureEditor->create($competition, [3]);
            $rootRound = $structure->getSingleCategory()->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyTarget::Winners);
            $startLocation = new StartLocation(
                $structure->getSingleCategory()->getNumber(),
                $firstPlace->getPouleNr(),
                $firstPlace->getPlaceNr()
            );

            $competitor = new TeamCompetitor(
                $competition,
                $startLocation,
                new Team($competition->getLeague()->getAssociation(), 'competitor 1')
            );

            $startLocationMap = new StartLocationMap([$competitor]);
            $nameService = new NameService($startLocationMap);

            self::assertSame('A1', $nameService->getPlaceName($firstPlace, false, false));
            self::assertSame('competitor 1', $nameService->getPlaceName($firstPlace, true, false));
            self::assertSame('nr. 1 poule A', $nameService->getPlaceName($firstPlace, false, true));
            self::assertSame('competitor 1', $nameService->getPlaceName($firstPlace, true, true));

            $lastPlace = $rootRound->getFirstPlace(QualifyTarget::Losers);

            self::assertSame('A3', $nameService->getPlaceName($lastPlace));
            self::assertSame('A3', $nameService->getPlaceName($lastPlace, true, false));
            self::assertSame('nr. 3 poule A', $nameService->getPlaceName($lastPlace, false, true));
            self::assertSame('nr. 3 poule A', $nameService->getPlaceName($lastPlace, true, true));
        }
    }

    public function testPlaceFromName(): void
    {
        $competition = $this->createCompetition();

        // basics
        {
            $structureEditor = $this->createStructureEditor();
            $structure = $structureEditor->create($competition, [3, 3, 3]);
            $rootRound = $structure->getSingleCategory()->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyTarget::Winners);
            $startLocation = new StartLocation(
                $structure->getSingleCategory()->getNumber(),
                $firstPlace->getPouleNr(),
                $firstPlace->getPlaceNr()
            );
            $competitor = new TeamCompetitor(
                $competition, $startLocation,
                new Team($competition->getLeague()->getAssociation(), 'competitor 1')
            );

            $startLocationMap = new StartLocationMap([$competitor]);
            $nameService = new NameService($startLocationMap);

            $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2]);
            $winnersWinnersRound = $structureEditor->addChildRound($winnersRound, QualifyTarget::Winners, [2]);
            $winnersLosersRound = $structureEditor->addChildRound($winnersRound, QualifyTarget::Losers, [2]);
            // (new StructureOutput())->output($structure);
            self::assertSame('A1', $nameService->getPlaceFromName($firstPlace, false, false));
            self::assertSame('competitor 1', $nameService->getPlaceFromName($firstPlace, true, false));
            self::assertSame('nr. 1 poule A', $nameService->getPlaceFromName($firstPlace, false, true),);
            self::assertSame('competitor 1', $nameService->getPlaceFromName($firstPlace, true, true));

            $lastPlace = $rootRound->getFirstPlace(QualifyTarget::Losers);

            self::assertSame('C3', $nameService->getPlaceFromName($lastPlace, false));
            self::assertSame('C3', $nameService->getPlaceFromName($lastPlace, true));
            self::assertSame('nr. 3 poule C', $nameService->getPlaceFromName($lastPlace, false, true));
            self::assertSame('nr. 3 poule C', $nameService->getPlaceFromName($lastPlace, true, true));


            $winnersLastPlace = $winnersRound->getFirstPoule()->getPlace(2);

            self::assertSame('1e2', $nameService->getPlaceFromName($winnersLastPlace, false));
            self::assertSame('1e van 2e plekken', $nameService->getPlaceFromName($winnersLastPlace, false, true));

            $winnersFirstPlace = $winnersRound->getPoule(1)->getPlace(1);

            self::assertSame('1eA', $nameService->getPlaceFromName($winnersFirstPlace, false));
            self::assertSame('1e poule A', $nameService->getPlaceFromName($winnersFirstPlace, false, true));

            $doubleWinnersFirstPlace = $winnersWinnersRound->getPoule(1)->getPlace(1);

            self::assertSame('1eD', $nameService->getPlaceFromName($doubleWinnersFirstPlace, false));
            self::assertSame('1e pl. wed. D', $nameService->getPlaceFromName($doubleWinnersFirstPlace, false, true));


            $winnersLosersFirstPlace = $winnersLosersRound->getPoule(1)->getPlace(1);

            self::assertSame('2eD', $nameService->getPlaceFromName($winnersLosersFirstPlace, false));
            self::assertSame('2e pl. wed. D', $nameService->getPlaceFromName($winnersLosersFirstPlace, false, true));
        }
    }

    public function testPlacesFromName(): void
    {
        $competition = $this->createCompetition();
        $competitionSport = $competition->getSingleSport();
        $field = $competitionSport->getFields()->last();
        self::assertNotFalse($field);
        $competitionSport->getFields()->removeElement($field);

        // basics
        {
            $structureEditor = $this->createStructureEditor();
            $structure = $structureEditor->create($competition, [2]);
            $rootRound = $structure->getSingleCategory()->getRootRound();
            $firstPlace = $rootRound->getFirstPlace(QualifyTarget::Winners);
            $startLocation = new StartLocation(
                $structure->getSingleCategory()->getNumber(),
                $firstPlace->getPouleNr(),
                $firstPlace->getPlaceNr()
            );
            $competitor = new TeamCompetitor(
                $competition,
                $startLocation,
                new Team($competition->getLeague()->getAssociation(), 'competitor 1')
            );

            $startLocationMap = new StartLocationMap([$competitor]);
            $nameService = new NameService($startLocationMap);

            (new GamesCreator())->createStructureGames($structure);

            $roundGames = $rootRound->getGames();
            $firstGame = reset($roundGames);
            self::assertNotFalse($firstGame);
            $gamePlaces = $firstGame->getPlaces()->toArray();

            self::assertSame($nameService->getPlacesFromName($gamePlaces, false, false), 'A1 & A2');
        }
    }

    public function testPlacesFromName2(): void
    {
        $competition = $this->createCompetition();
        $competitionSport = $competition->getSingleSport();
        $field = $competitionSport->getFields()->last();
        self::assertNotFalse($field);
        $competitionSport->getFields()->removeElement($field);

        // basics
        {
            $structureEditor = $this->createStructureEditor();
            $structure = $structureEditor->create($competition, [4, 3, 3]);
            $rootRound = $structure->getSingleCategory()->getRootRound();

            $nameService = new NameService();

            $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2]);
            $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [3, 2]);
//            (new StructureOutput())->output($structure);

            $winnersSecondPlaceFirstPoule = $winnersRound->getPoule(1)->getPlace(2); // 1e2
            self::assertSame(
                '1e2',
                $nameService->getPlaceFromName($winnersSecondPlaceFirstPoule, false, false)
            );
            self::assertSame(
                '1e van 2e plekken',
                $nameService->getPlaceFromName($winnersSecondPlaceFirstPoule, false, true)
            );

            $losersFirstPlaceFirstPoule = $losersRound->getPoule(1)->getPlace(1); // 3e3
            self::assertSame(
                '2e3',
                $nameService->getPlaceFromName($losersFirstPlaceFirstPoule, false, false)
            );
            self::assertSame(
                '2e van 2e pl. van onderen',
                $nameService->getPlaceFromName($losersFirstPlaceFirstPoule, false, true)
            );

            $losersSecondPlaceFirstPoule = $losersRound->getPoule(1)->getPlace(2); // 2e2
            self::assertSame(
                '3e3',
                $nameService->getPlaceFromName($losersSecondPlaceFirstPoule, false, false)
            );
            self::assertSame(
                '3e van 2e pl. van onderen',
                $nameService->getPlaceFromName($losersSecondPlaceFirstPoule, false, true)
            );
        }
    }

    public function testPlaceFromNameVertical(): void
    {
        $competition = $this->createCompetition();

        // basics
        {
            $structureEditor = $this->createStructureEditor();
            $structure = $structureEditor->create($competition, [5, 5, 5, 5]);
            $rootRound = $structure->getSingleCategory()->getRootRound();

            $nameService = new NameService();

            $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [3, 3, 3], Distribution::Vertical);
            $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [3, 3, 3], Distribution::Vertical);
            (new StructureOutput())->output($structure);

            $winnersThirdPlaceThirdPoule = $winnersRound->getPoule(3)->getPlace(3); // 1e3
            self::assertSame(
                '1e3',
                $nameService->getPlaceFromName($winnersThirdPlaceThirdPoule, false)
            );
            self::assertSame(
                '1e van 3e plekken',
                $nameService->getPlaceFromName($winnersThirdPlaceThirdPoule, false, true)
            );

            $losersFirstPlaceFirstPoule = $losersRound->getPoule(1)->getPlace(1); // 4e3
            self::assertSame(
                '4e3',
                $nameService->getPlaceFromName($losersFirstPlaceFirstPoule, false)
            );
            self::assertSame(
                '4e van 3e plekken',
                $nameService->getPlaceFromName($losersFirstPlaceFirstPoule, false, true)
            );
        }
    }

    public function testPlaceFromNameVerticalLosers(): void
    {
        $competition = $this->createCompetition();

        // basics
        {
            $structureEditor = $this->createStructureEditor();
            $structure = $structureEditor->create($competition, [3, 3, 3, 2]);
            $rootRound = $structure->getSingleCategory()->getRootRound();

            $nameService = new NameService();

            $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [3, 3], Distribution::Vertical);
            // (new StructureOutput())->output($structure);

            $losersSecondPlaceFirstPoule = $losersRound->getPoule(1)->getPlace(2);
            self::assertSame(
                '4e2',
                $nameService->getPlaceFromName($losersSecondPlaceFirstPoule, false)
            );

            $losersThirdPlaceFirstPoule = $losersRound->getPoule(1)->getPlace(3);
            self::assertSame(
                '1e3',
                $nameService->getPlaceFromName($losersThirdPlaceFirstPoule, false)
            );

            $losersThirdPlaceSecondPoule = $losersRound->getPoule(2)->getPlace(3);
            self::assertSame(
                '4e3',
                $nameService->getPlaceFromName($losersThirdPlaceSecondPoule, false)
            );
        }
    }


//    public function testHourizontalPouleName(): void
//    {
//        $nameService = new NameService();
//        $competition = $this->createCompetition();
//
//        // basics
//        {
//            $structureEditor = $this->createStructureEditor();
//            $structure = $structureEditor->create($competition, [4,4,4]);
//            $rootRound = $structure->getSingleCategory()->getRootRound();

//            $firstWinnersHorPoule = $rootRound->getHorizontalPoule(QualifyTarget::Winners, 1);
//            self::assertSame($nameService->getHorizontalPouleName($firstWinnersHorPoule), 'nummers 1');

//            $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
//            $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);

//            $firstWinnersHorPoule2 = $rootRound->getHorizontalPoule(QualifyTarget::Winners, 1);
//            $firstWinnersQualifyRule2 = $firstWinnersHorPoule2->getQualifyRule();
//            self::assertInstanceOf(MultipleQualifyRule::class, $firstWinnersQualifyRule2);
//            self::assertSame('één van de 1e plekken',
//                             $nameService->getPlaceFromName($firstWinnersQualifyRule2, true));
//
//            $firstLosersHorPoule = $rootRound->getHorizontalPoule(QualifyTarget::Losers, 1);
//            $firstLosersQualifyRule = $firstLosersHorPoule->getQualifyRule();
//            self::assertInstanceOf(MultipleQualifyRule::class, $firstLosersQualifyRule);
//
//            self::assertSame('één van de 1e plekken van onderen',
//                             $nameService->getMultipleQualifyRuleName($firstLosersQualifyRule, true));

//            $structureEditor->addQualifiers($rootRound, QualifyTarget::Winners, 1);
//            $structureEditor->addQualifiers($rootRound, QualifyTarget::Winners, 1);
//
//            $structureEditor->addQualifiers($rootRound, QualifyTarget::Losers, 1);
//            $structureEditor->addQualifiers($rootRound, QualifyTarget::Losers, 1);

//            $firstWinnersHorPoule3 = $rootRound->getHorizontalPoule(QualifyTarget::Winners, 1);
//            $firstWinnersQualifyRule3 = $firstWinnersHorPoule3->getQualifyRule();
//            self::assertInstanceOf(MultipleQualifyRule::class, $firstWinnersQualifyRule3);
//            self::assertSame('1e plekken',
//                             $nameService->getMultipleQualifyRuleName($firstWinnersQualifyRule3, true));

//            $firstLosersHorPoule3 = $rootRound->getHorizontalPoule(QualifyTarget::Losers, 1);
//            self::assertSame($nameService->getHorizontalPouleName($firstLosersHorPoule3), 'nummers laatste');

//            $secondWinnersHorPoule = $rootRound->getHorizontalPoule(QualifyTarget::Winners, 2);
//            $secondWinnersQualifyRule = $secondWinnersHorPoule->getQualifyRule();
//            self::assertSame($nameService->getPlacesFromName($secondWinnersQualifyRule), 'beste nummer 2');
//
//            $secondLosersHorPoule = $rootRound->getHorizontalPoule(QualifyTarget::Losers, 2);
//            self::assertSame($nameService->getHorizontalPouleName($secondLosersHorPoule), 'slechtste 1 na laatst');
//
//
//            $structureEditor->addQualifiers($rootRound, QualifyTarget::Winners, 1);
//            $secondWinnersHorPoule2 = $rootRound->getHorizontalPoule(QualifyTarget::Winners, 2);
//            self::assertSame($nameService->getHorizontalPouleName($secondWinnersHorPoule2), '2 beste nummers 2');
//
//            $structureEditor->addQualifiers($rootRound, QualifyTarget::Losers, 1);
//            $secondLosersHorPoule2 = $rootRound->getHorizontalPoule(QualifyTarget::Losers, 2);
//            self::assertSame($nameService->getHorizontalPouleName($secondLosersHorPoule2), '2 slechtste nummers 1 na laatst');
//        }
//    }

}
