<?php
declare(strict_types=1);

namespace Sports\Tests;

use Sports\Qualify\Target as QualifyTarget;
use PHPUnit\Framework\TestCase;
use Sports\Competition\Field;
use Sports\Competitor\Map as CompetitorMap;
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
    use CompetitionCreator, StructureEditorCreator;

    public function testWinnersOrLosersDescription(): void
    {
        $nameService = new NameService();

        self::assertSame($nameService->getQualifyTargetDescription(QualifyTarget::WINNERS), 'winnaar');
        self::assertSame($nameService->getQualifyTargetDescription(QualifyTarget::LOSERS), 'verliezer');
        self::assertSame($nameService->getQualifyTargetDescription(QualifyTarget::WINNERS, true), 'winnaars');
        self::assertSame($nameService->getQualifyTargetDescription(QualifyTarget::LOSERS, true), 'verliezers');
        self::assertSame($nameService->getQualifyTargetDescription(QualifyTarget::DROPOUTS), '');
    }

    public function testRoundNumberName(): void
    {
        $nameService = new NameService();
        $competition = $this->createCompetition();
        $structureEditor = $this->createStructureEditor([]);
        $structure = $structureEditor->create($competition, [3,3,2]);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $structureEditor->addQualifiers($rootRound, QualifyTarget::WINNERS, 4);
        $structureEditor->addQualifiers($rootRound, QualifyTarget::LOSERS, 4);

        $secondRoundNumber = $firstRoundNumber->getNext();
        self::assertNotNull($secondRoundNumber);
        $secondRoundNumberName = $nameService->getRoundNumberName($secondRoundNumber);
        // all equal
        self::assertSame($secondRoundNumberName, 'finale');

        $borderGroup = $rootRound->getBorderQualifyGroup(QualifyTarget::LOSERS);
        self::assertNotNull($borderGroup);
        $losersChildRound = $borderGroup->getChildRound();

        $structureEditor->addQualifier($losersChildRound, QualifyTarget::LOSERS);
        // not all equal
        $newSecondRoundNumberName = $nameService->getRoundNumberName($secondRoundNumber);
        self::assertSame($newSecondRoundNumberName, '2de ronde'); // '2<sup>de</sup> ronde'
    }

    public function testRoundName(): void
    {
        $nameService = new NameService();
        $competition = $this->createCompetition();

        // root needs no ranking, unequal depth
        {
            $structureEditor = $this->createStructureEditor([]);
            $structure = $structureEditor->create($competition, [2,2]);
            $rootRound = $structure->getRootRound();

            $structureEditor->addQualifier($rootRound, QualifyTarget::WINNERS);
            self::assertSame($nameService->getRoundName($rootRound), 'halve finale'); // '&frac12; finale'

            $structureEditor->addQualifier($rootRound, QualifyTarget::LOSERS);
            self::assertSame($nameService->getRoundName($rootRound), 'halve finale'); // '&frac12; finale'
        }

        // root needs ranking
        {
            $structureEditor2 = $this->createStructureEditor([]);
            $structure2 = $structureEditor2->create($competition, [4,4,4,4]);
            $rootRound2 = $structure2->getRootRound();

            self::assertSame($nameService->getRoundName($rootRound2), '1ste ronde'); // '1<sup>ste</sup> ronde'

            $structureEditor2->addQualifiers($rootRound2, QualifyTarget::WINNERS, 3);

            $rootRound2Child = $rootRound2->getChild(QualifyTarget::WINNERS, 1);
            self::assertNotNull($rootRound2Child);
            self::assertSame($nameService->getRoundName($rootRound2Child), '2de ronde'); // '2<sup>de</sup> ronde'
        }
    }

    public function testRoundNameHtmlFractialNumber(): void
    {
        $nameService = new NameService();
        $competition = $this->createCompetition();

        // root needs ranking, depth 2
        {
            $structureEditor = $this->createStructureEditor([]);
            $structure = $structureEditor->create($competition, [2,2,2,2,2,2,2,2]);
            $rootRound = $structure->getRootRound();

            $structureEditor->addQualifiers($rootRound, QualifyTarget::WINNERS, 8);
            $structureEditor->addQualifiers($rootRound, QualifyTarget::LOSERS, 8);

            $rootWinnersBorderGroup = $rootRound->getBorderQualifyGroup(QualifyTarget::WINNERS);
            self::assertNotNull($rootWinnersBorderGroup);
            $winnersChildRound = $rootWinnersBorderGroup->getChildRound();
            $structureEditor->addQualifiers($winnersChildRound, QualifyTarget::WINNERS, 4);

            $rootLosersBorderGroup = $rootRound->getBorderQualifyGroup(QualifyTarget::LOSERS);
            self::assertNotNull($rootLosersBorderGroup);
            $losersChildRound = $rootLosersBorderGroup->getChildRound();
            $structureEditor->addQualifiers($losersChildRound, QualifyTarget::LOSERS, 4);

            self::assertSame('kwart finale', $nameService->getRoundName($rootRound)); // '&frac14; finale'

            $winnersBorderGroup = $winnersChildRound->getBorderQualifyGroup(QualifyTarget::WINNERS);
            self::assertNotNull($winnersBorderGroup);
            $doubleWinnersChildRound = $winnersBorderGroup->getChildRound();
            $structureEditor->addQualifier($doubleWinnersChildRound, QualifyTarget::WINNERS);

            $losersBorderGroup = $losersChildRound->getBorderQualifyGroup(QualifyTarget::LOSERS);
            self::assertNotNull($losersBorderGroup);
            $doubleLosersChildRound = $losersBorderGroup->getChildRound();
            $structureEditor->addQualifier($doubleLosersChildRound, QualifyTarget::LOSERS);

            $number = 8;
            // '<span style="font-size: 80%"><sup>1</sup>&frasl;<sub>' . $number . '</sub></span> finale'
            self::assertSame('1/8 finale', $nameService->getRoundName($rootRound));

            $doubleLosersBorderGroup = $doubleLosersChildRound->getBorderQualifyGroup(QualifyTarget::LOSERS);
            self::assertNotNull($doubleLosersBorderGroup);
            $losersFinal = $doubleLosersBorderGroup->getChildRound();
            self::assertSame('15de/16de' . ' plaats', $nameService->getRoundName($losersFinal)); // '15<sup>de</sup>/16<sup>de</sup>'
        }
    }

    public function testPouleName(): void
    {
        $nameService = new NameService();
        $competition = $this->createCompetition();

        // basics
        {
            $structureEditor = $this->createStructureEditor([]);
            $poules = [];
            for ($i = 1 ; $i <= 29 ; $i++) {
                array_push($poules, 3);
            }
            array_push($poules, 2);
            $structure = $structureEditor->create($competition, ...$poules);
            $rootRound = $structure->getRootRound();

            self::assertSame($nameService->getPouleName($rootRound->getPoule(1), false), 'A');
            self::assertSame($nameService->getPouleName($rootRound->getPoule(1), true), 'poule A');

            self::assertSame($nameService->getPouleName($rootRound->getPoule(27), false), 'AA');
            self::assertSame($nameService->getPouleName($rootRound->getPoule(27), true), 'poule AA');

            self::assertSame($nameService->getPouleName($rootRound->getPoule(30), false), 'AD');
            self::assertSame($nameService->getPouleName($rootRound->getPoule(30), true), 'wed. AD');
        }
    }

    public function testPlaceName(): void
    {
        $competition = $this->createCompetition();

        // basics
        {
            $structureEditor = $this->createStructureEditor([]);
            $structure = $structureEditor->create($competition, [3]);
            $rootRound = $structure->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyTarget::WINNERS);
            $competitor = new TeamCompetitor(
                $competition,
                $firstPlace->getPouleNr(),
                $firstPlace->getPlaceNr(),
                new Team($competition->getLeague()->getAssociation(), 'competitor 1')
            );

            $competitorMap = new CompetitorMap([$competitor]);
            $nameService = new NameService($competitorMap);

            self::assertSame($nameService->getPlaceName($firstPlace, false, false), 'A1');
            self::assertSame($nameService->getPlaceName($firstPlace, true, false), 'competitor 1');
            self::assertSame($nameService->getPlaceName($firstPlace, false, true), 'poule A nr. 1');
            self::assertSame($nameService->getPlaceName($firstPlace, true, true), 'competitor 1');

            $lastPlace = $rootRound->getFirstPlace(QualifyTarget::LOSERS);

            self::assertSame($nameService->getPlaceName($lastPlace), 'A3');
            self::assertSame($nameService->getPlaceName($lastPlace, true, false), 'A3');
            self::assertSame($nameService->getPlaceName($lastPlace, false, true), 'poule A nr. 3');
            self::assertSame($nameService->getPlaceName($lastPlace, true, true), 'poule A nr. 3');
        }
    }

    public function testPlaceFromName(): void
    {
        $competition = $this->createCompetition();

        // basics
        {
            $structureEditor = $this->createStructureEditor([]);
            $structure = $structureEditor->create($competition, [3,3,3]);
            $rootRound = $structure->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyTarget::WINNERS);
            $competitor = new TeamCompetitor(
                $competition,
                $firstPlace->getPouleNr(),
                $firstPlace->getPlaceNr(),
                new Team($competition->getLeague()->getAssociation(), 'competitor 1')
            );

            $competitorMap = new CompetitorMap([$competitor]);
            $nameService = new NameService($competitorMap);

            $structureEditor->addQualifiers($rootRound, QualifyTarget::WINNERS, 4);

            self::assertSame($nameService->getPlaceFromName($firstPlace, false, false), 'A1');
            self::assertSame($nameService->getPlaceFromName($firstPlace, true, false), 'competitor 1');
            self::assertSame($nameService->getPlaceFromName($firstPlace, false, true), 'poule A nr. 1');
            self::assertSame($nameService->getPlaceFromName($firstPlace, true, true), 'competitor 1');

            $lastPlace = $rootRound->getFirstPlace(QualifyTarget::LOSERS);

            self::assertSame($nameService->getPlaceFromName($lastPlace, false, false), 'C3');
            self::assertSame($nameService->getPlaceFromName($lastPlace, true, false), 'C3');
            self::assertSame($nameService->getPlaceFromName($lastPlace, false, true), 'poule C nr. 3');
            self::assertSame($nameService->getPlaceFromName($lastPlace, true, true), 'poule C nr. 3');


            $rootWinnersBorderGroup = $rootRound->getBorderQualifyGroup(QualifyTarget::WINNERS);
            self::assertNotNull($rootWinnersBorderGroup);
            $winnersChildRound = $rootWinnersBorderGroup->getChildRound();
            $winnersLastPlace = $winnersChildRound->getPoule(1)->getPlace(2);

            self::assertSame($nameService->getPlaceFromName($winnersLastPlace, false, false), '?2');
            self::assertSame($nameService->getPlaceFromName($winnersLastPlace, false, true), 'beste nummer 2');

            $winnersFirstPlace = $winnersChildRound->getPoule(1)->getPlace(1);

            self::assertSame($nameService->getPlaceFromName($winnersFirstPlace, false, false), 'A1');
            self::assertSame($nameService->getPlaceFromName($winnersFirstPlace, false, true), 'poule A nr. 1');

            $structureEditor->addQualifier($winnersChildRound, QualifyTarget::WINNERS);
            $winnersBorderGroup = $winnersChildRound->getBorderQualifyGroup(QualifyTarget::WINNERS);
            self::assertNotNull($winnersBorderGroup);
            $doubleWinnersChildRound = $winnersBorderGroup->getChildRound();

            $doubleWinnersFirstPlace = $doubleWinnersChildRound->getPoule(1)->getPlace(1);

            self::assertSame($nameService->getPlaceFromName($doubleWinnersFirstPlace, false, false), 'D1');
            self::assertSame($nameService->getPlaceFromName($doubleWinnersFirstPlace, false, true), 'winnaar D');

            $structureEditor->addQualifier($winnersChildRound, QualifyTarget::LOSERS);
            $losersBorderGroup = $winnersChildRound->getBorderQualifyGroup(QualifyTarget::LOSERS);
            self::assertNotNull($losersBorderGroup);

            $winnersLosersChildRound = $losersBorderGroup->getChildRound();
            $winnersLosersFirstPlace = $winnersLosersChildRound->getPoule(1)->getPlace(1);

            self::assertSame($nameService->getPlaceFromName($winnersLosersFirstPlace, false), 'D2');
            self::assertSame($nameService->getPlaceFromName($winnersLosersFirstPlace, false, true), 'verliezer D');
        }
    }

    public function testPlacesFromName(): void
    {
        $nameService = new NameService();
        $competition = $this->createCompetition();
        $competitionSport = $competition->getSingleSport();
        $field = $competitionSport->getFields()->last();
        self::assertNotFalse($field);
        $competitionSport->getFields()->removeElement($field);

        // basics
        {
            $structureEditor = $this->createStructureEditor([]);
            $structure = $structureEditor->create($competition, [2]);
            $rootRound = $structure->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyTarget::WINNERS);
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

    public function testHourizontalPouleName(): void
    {
        $nameService = new NameService();
        $competition = $this->createCompetition();

        // basics
        {
            $structureEditor = $this->createStructureEditor([]);
            $structure = $structureEditor->create($competition, [4,4,4]);
            $rootRound = $structure->getRootRound();

            $firstWinnersHorPoule = $rootRound->getHorizontalPoules(QualifyTarget::WINNERS)[0];
            self::assertSame($nameService->getHorizontalPouleName($firstWinnersHorPoule), 'nummers 1');

            $structureEditor->addQualifier($rootRound, QualifyTarget::WINNERS);
            $structureEditor->addQualifier($rootRound, QualifyTarget::LOSERS);

            $firstWinnersHorPoule2 = $rootRound->getHorizontalPoules(QualifyTarget::WINNERS)[0];
            self::assertSame($nameService->getHorizontalPouleName($firstWinnersHorPoule2), '2 beste nummers 1');

            $firstLosersHorPoule = $rootRound->getHorizontalPoules(QualifyTarget::LOSERS)[0];
            self::assertSame($nameService->getHorizontalPouleName($firstLosersHorPoule), '2 slechtste nummers laatste');

            $structureEditor->addQualifier($rootRound, QualifyTarget::WINNERS);
            $structureEditor->addQualifier($rootRound, QualifyTarget::WINNERS);

            $structureEditor->addQualifier($rootRound, QualifyTarget::LOSERS);
            $structureEditor->addQualifier($rootRound, QualifyTarget::LOSERS);

            $firstWinnersHorPoule3 = $rootRound->getHorizontalPoules(QualifyTarget::WINNERS)[0];
            self::assertSame($nameService->getHorizontalPouleName($firstWinnersHorPoule3), 'nummers 1');

            $firstLosersHorPoule3 = $rootRound->getHorizontalPoules(QualifyTarget::LOSERS)[0];
            self::assertSame($nameService->getHorizontalPouleName($firstLosersHorPoule3), 'nummers laatste');

            $secondWinnersHorPoule = $rootRound->getHorizontalPoules(QualifyTarget::WINNERS)[1];
            self::assertSame($nameService->getHorizontalPouleName($secondWinnersHorPoule), 'beste nummer 2');

            $secondLosersHorPoule = $rootRound->getHorizontalPoules(QualifyTarget::LOSERS)[1];
            self::assertSame($nameService->getHorizontalPouleName($secondLosersHorPoule), 'slechtste 1 na laatst');


            $structureEditor->addQualifier($rootRound, QualifyTarget::WINNERS);
            $secondWinnersHorPoule2 = $rootRound->getHorizontalPoules(QualifyTarget::WINNERS)[1];
            self::assertSame($nameService->getHorizontalPouleName($secondWinnersHorPoule2), '2 beste nummers 2');

            $structureEditor->addQualifier($rootRound, QualifyTarget::LOSERS);
            $secondLosersHorPoule2 = $rootRound->getHorizontalPoules(QualifyTarget::LOSERS)[1];
            self::assertSame($nameService->getHorizontalPouleName($secondLosersHorPoule2), '2 slechtste nummers 1 na laatst');
        }
    }

    public function testRefereeName(): void
    {
        $competition = $this->createCompetition();
        $competitionSport = $competition->getSingleSport();
        $lastField = $competitionSport->getFields()->last();
        self::assertInstanceOf(Field::class, $lastField);
        $competitionSport->getFields()->removeElement($lastField);

        // basics
        {
            $structureEditor = $this->createStructureEditor([]);
            $structure = $structureEditor->create($competition, [2]);
            $rootRound = $structure->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyTarget::WINNERS);
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
