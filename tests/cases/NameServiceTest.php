<?php

declare(strict_types=1);

namespace Sports\Tests;

use Sports\Qualify\Target as QualifyTarget;
use PHPUnit\Framework\TestCase;
use Sports\Competition\Field;
use Sports\Competitor\Map as CompetitorMap;
use Sports\Output\StructureOutput;
use Sports\Team;
use Sports\Game\Against as AgainstGame;
use Sports\TestHelper\CompetitionCreator;
use Sports\NameService;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Competition\Referee;
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
        $rootRound = $structure->getRootRound();

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

    public function testRoundName(): void
    {
        $nameService = new NameService();
        $competition = $this->createCompetition();

        // root needs no ranking, unequal depth
        {
            $structureEditor = $this->createStructureEditor();
            $structure = $structureEditor->create($competition, [2,2]);
            $rootRound = $structure->getRootRound();

            $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2]);
            self::assertSame($nameService->getRoundName($rootRound), 'halve finale'); // '&frac12; finale'

            $structureEditor->addChildRound($rootRound, QualifyTarget::Losers, [2]);
            self::assertSame($nameService->getRoundName($rootRound), 'halve finale'); // '&frac12; finale'
        }

        // root needs ranking
        {
            $structureEditor2 = $this->createStructureEditor();
            $structure2 = $structureEditor2->create($competition, [4, 4, 4, 4]);
            $rootRound2 = $structure2->getRootRound();

            self::assertSame($nameService->getRoundName($rootRound2), '1e ronde'); // '1<sup>ste</sup> ronde'

            $rootRound2Child = $structureEditor2->addChildRound($rootRound2, QualifyTarget::Winners, [3]);

            self::assertSame($nameService->getRoundName($rootRound2Child), '2e ronde'); // '2<sup>de</sup> ronde'
        }
    }

    public function testRoundNameHtmlFractialNumber(): void
    {
        $nameService = new NameService();
        $competition = $this->createCompetition();

        // root needs ranking, depth 2
        {
            $structureEditor = $this->createStructureEditor();
            $structure = $structureEditor->create($competition, [2,2,2,2,2,2,2,2]);
            $rootRound = $structure->getRootRound();

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
                '15e/16e' . ' plaats',
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
            $rootRound = $structure->getRootRound();

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
            $rootRound = $structure->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyTarget::Winners);
            $competitor = new TeamCompetitor(
                $competition,
                $firstPlace->getPouleNr(),
                $firstPlace->getPlaceNr(),
                new Team($competition->getLeague()->getAssociation(), 'competitor 1')
            );

            $competitorMap = new CompetitorMap([$competitor]);
            $nameService = new NameService($competitorMap);

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
            $structure = $structureEditor->create($competition, [3,3,3]);
            $rootRound = $structure->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyTarget::Winners);
            $competitor = new TeamCompetitor(
                $competition,
                $firstPlace->getPouleNr(),
                $firstPlace->getPlaceNr(),
                new Team($competition->getLeague()->getAssociation(), 'competitor 1')
            );

            $competitorMap = new CompetitorMap([$competitor]);
            $nameService = new NameService($competitorMap);

            $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::Winners, [2, 2]);
            $winnersWinnersRound = $structureEditor->addChildRound($winnersRound, QualifyTarget::Winners, [2]);
            $winnersLosersRound = $structureEditor->addChildRound($winnersRound, QualifyTarget::Losers, [2]);
            // (new StructureOutput())->output($structure);return;
            self::assertSame('A1', $nameService->getPlaceFromName($firstPlace, false, false));
            self::assertSame('competitor 1', $nameService->getPlaceFromName($firstPlace, true, false));
            self::assertSame('nr. 1 poule A', $nameService->getPlaceFromName($firstPlace, false, true),);
            self::assertSame('competitor 1', $nameService->getPlaceFromName($firstPlace, true, true));

            $lastPlace = $rootRound->getFirstPlace(QualifyTarget::Losers);

            self::assertSame('C3', $nameService->getPlaceFromName($lastPlace, false, false));
            self::assertSame('C3', $nameService->getPlaceFromName($lastPlace, true, false));
            self::assertSame('nr. 3 poule C', $nameService->getPlaceFromName($lastPlace, false, true));
            self::assertSame('nr. 3 poule C', $nameService->getPlaceFromName($lastPlace, true, true));


            $winnersLastPlace = $winnersRound->getFirstPoule()->getPlace(2);

            self::assertSame('1e2', $nameService->getPlaceFromName($winnersLastPlace, false, false));
            self::assertSame('1e van 2e plekken', $nameService->getPlaceFromName($winnersLastPlace, false, true));

            $winnersFirstPlace = $winnersRound->getPoule(1)->getPlace(1);

            self::assertSame('A1', $nameService->getPlaceFromName($winnersFirstPlace, false, false));
            self::assertSame('1e poule A', $nameService->getPlaceFromName($winnersFirstPlace, false, true));

            $doubleWinnersFirstPlace = $winnersWinnersRound->getPoule(1)->getPlace(1);

            self::assertSame('D1', $nameService->getPlaceFromName($doubleWinnersFirstPlace, false, false));
            self::assertSame('1e pl. wed. D', $nameService->getPlaceFromName($doubleWinnersFirstPlace, false, true));


            $winnersLosersFirstPlace = $winnersLosersRound->getPoule(1)->getPlace(1);

            self::assertSame('D2', $nameService->getPlaceFromName($winnersLosersFirstPlace, false));
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
            $rootRound = $structure->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyTarget::Winners);
            $competitor = new TeamCompetitor(
                $competition,
                $firstPlace->getPouleNr(),
                $firstPlace->getPlaceNr(),
                new Team($competition->getLeague()->getAssociation(), 'competitor 1')
            );

            $competitorMap = new CompetitorMap([$competitor]);
            $nameService = new NameService($competitorMap);

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
            $rootRound = $structure->getRootRound();

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
                '3e3',
                $nameService->getPlaceFromName($losersFirstPlaceFirstPoule, false, false)
            );
            self::assertSame(
                '3e van 2e pl. van onderen',
                $nameService->getPlaceFromName($losersFirstPlaceFirstPoule, false, true)
            );

            $losersSecondPlaceFirstPoule = $losersRound->getPoule(1)->getPlace(2); // 2e2
            self::assertSame(
                '2e3',
                $nameService->getPlaceFromName($losersSecondPlaceFirstPoule, false, false)
            );
            self::assertSame(
                '2e van 2e pl. van onderen',
                $nameService->getPlaceFromName($losersSecondPlaceFirstPoule, false, true)
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
//            $rootRound = $structure->getRootRound();

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

    public function testRefereeName(): void
    {
        $competition = $this->createCompetition();
        $competitionSport = $competition->getSingleSport();
        $lastField = $competitionSport->getFields()->last();
        self::assertInstanceOf(Field::class, $lastField);
        $competitionSport->getFields()->removeElement($lastField);

        // basics
        {
            $structureEditor = $this->createStructureEditor();
            $structure = $structureEditor->create($competition, [2]);
            $rootRound = $structure->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyTarget::Winners);
            $competitor = new TeamCompetitor(
                $competition,
                $firstPlace->getPouleNr(),
                $firstPlace->getPlaceNr(),
                new Team($competition->getLeague()->getAssociation(), 'competitor 1')
            );

            $competitorMap = new CompetitorMap([$competitor]);
            $nameService = new NameService($competitorMap);

            (new GamesCreator())->createStructureGames($structure);

            $game = $firstPlace->getPoule()->getAgainstGames()->first();
            self::assertInstanceOf(AgainstGame::class, $game);
            self::assertSame($nameService->getRefereeName($game), '111');

            $referee = new Referee($competition, 'CDK');
            $referee->setName('Co Du');

            $game->setReferee($referee);

            self::assertSame($nameService->getRefereeName($game), 'CDK');
            self::assertSame($nameService->getRefereeName($game, false), 'CDK');
            self::assertSame($nameService->getRefereeName($game, true), 'Co Du');

            $game->setReferee(null);
            $game->setRefereePlace($firstPlace);

            self::assertSame($nameService->getRefereeName($game), 'competitor 1');
            self::assertSame($nameService->getRefereeName($game, false), 'competitor 1');
            self::assertSame($nameService->getRefereeName($game, true), 'competitor 1');
        }
    }
}
