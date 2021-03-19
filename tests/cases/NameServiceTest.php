<?php
declare(strict_types=1);

namespace Sports\Tests;

use PHPUnit\Framework\TestCase;
use Sports\Competition\Field;
use Sports\Competitor\Map as CompetitorMap;
use Sports\Team;
use Sports\Game\Against as AgainstGame;
use Sports\TestHelper\CompetitionCreator;
use Sports\NameService;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Competition\Referee;
use Sports\Structure\Service as StructureService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\TestHelper\GamesCreator;

final class NameServiceTest extends TestCase
{
    use CompetitionCreator;

    public function testWinnersOrLosersDescription(): void
    {
        $nameService = new NameService();

        self::assertSame($nameService->getWinnersLosersDescription(QualifyGroup::WINNERS), 'winnaar');
        self::assertSame($nameService->getWinnersLosersDescription(QualifyGroup::LOSERS), 'verliezer');
        self::assertSame($nameService->getWinnersLosersDescription(QualifyGroup::WINNERS, true), 'winnaars');
        self::assertSame($nameService->getWinnersLosersDescription(QualifyGroup::LOSERS, true), 'verliezers');
        self::assertSame($nameService->getWinnersLosersDescription(QualifyGroup::DROPOUTS), '');
    }

    public function testRoundNumberName(): void
    {
        $nameService = new NameService();
        $competition = $this->createCompetition();
        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 8, 3);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $rootRound = $structure->getRootRound();

        $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);
        $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 4);

        $secondRoundNumber = $firstRoundNumber->getNext();
        self::assertNotNull($secondRoundNumber);
        $secondRoundNumberName = $nameService->getRoundNumberName($secondRoundNumber);
        // all equal
        self::assertSame($secondRoundNumberName, 'finale');

        $borderGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::LOSERS);
        self::assertNotNull($borderGroup);
        $losersChildRound = $borderGroup->getChildRound();

        $structureService->addQualifier($losersChildRound, QualifyGroup::LOSERS);
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
            $structureService = new StructureService([]);
            $structure = $structureService->create($competition, 4, 2);
            $rootRound = $structure->getRootRound();

            $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
            self::assertSame($nameService->getRoundName($rootRound), 'halve finale'); // '&frac12; finale'

            $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);
            self::assertSame($nameService->getRoundName($rootRound), 'halve finale'); // '&frac12; finale'
        }

        // root needs ranking
        {
            $structureService2 = new StructureService([]);
            $structure2 = $structureService2->create($competition, 16, 4);
            $rootRound2 = $structure2->getRootRound();

            self::assertSame($nameService->getRoundName($rootRound2), '1ste ronde'); // '1<sup>ste</sup> ronde'

            $structureService2->addQualifiers($rootRound2, QualifyGroup::WINNERS, 3);

            $rootRound2Child = $rootRound2->getChild(QualifyGroup::WINNERS, 1);
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
            $structureService = new StructureService([]);
            $structure = $structureService->create($competition, 16, 8);
            $rootRound = $structure->getRootRound();

            $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 8);

            $rootWinnersBorderGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::WINNERS);
            self::assertNotNull($rootWinnersBorderGroup);
            $winnersChildRound = $rootWinnersBorderGroup->getChildRound();
            $structureService->addQualifiers($winnersChildRound, QualifyGroup::WINNERS, 4);

            $structureService->addQualifiers($rootRound, QualifyGroup::LOSERS, 8);

            $rootLosersBorderGroup = $winnersChildRound->getBorderQualifyGroup(QualifyGroup::LOSERS);
            self::assertNotNull($rootLosersBorderGroup);
            $losersChildRound = $rootLosersBorderGroup->getChildRound();
            $structureService->addQualifiers($losersChildRound, QualifyGroup::LOSERS, 4);

            self::assertSame($nameService->getRoundName($rootRound), 'kwart finale'); // '&frac14; finale'

            $winnersBorderGroup = $winnersChildRound->getBorderQualifyGroup(QualifyGroup::WINNERS);
            self::assertNotNull($winnersBorderGroup);
            $doubleWinnersChildRound = $winnersBorderGroup->getChildRound();
            $structureService->addQualifier($doubleWinnersChildRound, QualifyGroup::WINNERS);

            $losersBorderGroup = $losersChildRound->getBorderQualifyGroup(QualifyGroup::LOSERS);
            self::assertNotNull($losersBorderGroup);
            $doubleLosersChildRound = $losersBorderGroup->getChildRound();
            $structureService->addQualifier($doubleLosersChildRound, QualifyGroup::LOSERS);

            $number = 8;
            // '<span style="font-size: 80%"><sup>1</sup>&frasl;<sub>' . $number . '</sub></span> finale'
            self::assertSame($nameService->getRoundName($rootRound), '1/8 finale');

            $doubleLosersBorderGroup = $doubleLosersChildRound->getBorderQualifyGroup(QualifyGroup::LOSERS);
            self::assertNotNull($doubleLosersBorderGroup);
            $losersFinal = $doubleLosersBorderGroup->getChildRound();
            self::assertSame($nameService->getRoundName($losersFinal), '15de/16de' . ' plaats'); // '15<sup>de</sup>/16<sup>de</sup>'
        }
    }

    public function testPouleName(): void
    {
        $nameService = new NameService();
        $competition = $this->createCompetition();

        // basics
        {
            $structureService = new StructureService([]);
            $structure = $structureService->create($competition, 89, 30);
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
            $structureService = new StructureService([]);
            $structure = $structureService->create($competition, 3);
            $rootRound = $structure->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyGroup::WINNERS);
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

            $lastPlace = $rootRound->getFirstPlace(QualifyGroup::LOSERS);

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
            $structureService = new StructureService([]);
            $structure = $structureService->create($competition, 9, 3);
            $rootRound = $structure->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyGroup::WINNERS);
            $competitor = new TeamCompetitor(
                $competition,
                $firstPlace->getPouleNr(),
                $firstPlace->getPlaceNr(),
                new Team($competition->getLeague()->getAssociation(), 'competitor 1')
            );

            $competitorMap = new CompetitorMap([$competitor]);
            $nameService = new NameService($competitorMap);

            $structureService->addQualifiers($rootRound, QualifyGroup::WINNERS, 4);

            self::assertSame($nameService->getPlaceFromName($firstPlace, false, false), 'A1');
            self::assertSame($nameService->getPlaceFromName($firstPlace, true, false), 'competitor 1');
            self::assertSame($nameService->getPlaceFromName($firstPlace, false, true), 'poule A nr. 1');
            self::assertSame($nameService->getPlaceFromName($firstPlace, true, true), 'competitor 1');

            $lastPlace = $rootRound->getFirstPlace(QualifyGroup::LOSERS);

            self::assertSame($nameService->getPlaceFromName($lastPlace, false, false), 'C3');
            self::assertSame($nameService->getPlaceFromName($lastPlace, true, false), 'C3');
            self::assertSame($nameService->getPlaceFromName($lastPlace, false, true), 'poule C nr. 3');
            self::assertSame($nameService->getPlaceFromName($lastPlace, true, true), 'poule C nr. 3');


            $rootWinnersBorderGroup = $rootRound->getBorderQualifyGroup(QualifyGroup::WINNERS);
            self::assertNotNull($rootWinnersBorderGroup);
            $winnersChildRound = $rootWinnersBorderGroup->getChildRound();
            $winnersLastPlace = $winnersChildRound->getPoule(1)->getPlace(2);

            self::assertSame($nameService->getPlaceFromName($winnersLastPlace, false, false), '?2');
            self::assertSame($nameService->getPlaceFromName($winnersLastPlace, false, true), 'beste nummer 2');

            $winnersFirstPlace = $winnersChildRound->getPoule(1)->getPlace(1);

            self::assertSame($nameService->getPlaceFromName($winnersFirstPlace, false, false), 'A1');
            self::assertSame($nameService->getPlaceFromName($winnersFirstPlace, false, true), 'poule A nr. 1');

            $structureService->addQualifier($winnersChildRound, QualifyGroup::WINNERS);
            $winnersBorderGroup = $winnersChildRound->getBorderQualifyGroup(QualifyGroup::WINNERS);
            self::assertNotNull($winnersBorderGroup);
            $doubleWinnersChildRound = $winnersBorderGroup->getChildRound();

            $doubleWinnersFirstPlace = $doubleWinnersChildRound->getPoule(1)->getPlace(1);

            self::assertSame($nameService->getPlaceFromName($doubleWinnersFirstPlace, false, false), 'D1');
            self::assertSame($nameService->getPlaceFromName($doubleWinnersFirstPlace, false, true), 'winnaar D');

            $structureService->addQualifier($winnersChildRound, QualifyGroup::LOSERS);
            $losersBorderGroup = $winnersChildRound->getBorderQualifyGroup(QualifyGroup::LOSERS);
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
            $structureService = new StructureService([]);
            $structure = $structureService->create($competition, 2, 1);
            $rootRound = $structure->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyGroup::WINNERS);
            $competitor = new TeamCompetitor(
                $competition,
                $firstPlace->getPouleNr(),
                $firstPlace->getPlaceNr(),
                new Team($competition->getLeague()->getAssociation(), 'competitor 1')
            );

            $competitorMap = new CompetitorMap([$competitor]);
            $nameService = new NameService($competitorMap);

            (new GamesCreator())->createStructureGames($structure);

            $game = $rootRound->getGames()[0];
            $gamePlaces = $game->getPlaces()->toArray();

            self::assertSame($nameService->getPlacesFromName($gamePlaces, false, false), 'A1 & A2');
        }
    }

    public function testHourizontalPouleName(): void
    {
        $nameService = new NameService();
        $competition = $this->createCompetition();

        // basics
        {
            $structureService = new StructureService([]);
            $structure = $structureService->create($competition, 12, 3);
            $rootRound = $structure->getRootRound();

            $firstWinnersHorPoule = $rootRound->getHorizontalPoules(QualifyGroup::WINNERS)[0];
            self::assertSame($nameService->getHorizontalPouleName($firstWinnersHorPoule), 'nummers 1');

            $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
            $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);

            $firstWinnersHorPoule2 = $rootRound->getHorizontalPoules(QualifyGroup::WINNERS)[0];
            self::assertSame($nameService->getHorizontalPouleName($firstWinnersHorPoule2), '2 beste nummers 1');

            $firstLosersHorPoule = $rootRound->getHorizontalPoules(QualifyGroup::LOSERS)[0];
            self::assertSame($nameService->getHorizontalPouleName($firstLosersHorPoule), '2 slechtste nummers laatste');

            $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
            $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);

            $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);
            $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);

            $firstWinnersHorPoule3 = $rootRound->getHorizontalPoules(QualifyGroup::WINNERS)[0];
            self::assertSame($nameService->getHorizontalPouleName($firstWinnersHorPoule3), 'nummers 1');

            $firstLosersHorPoule3 = $rootRound->getHorizontalPoules(QualifyGroup::LOSERS)[0];
            self::assertSame($nameService->getHorizontalPouleName($firstLosersHorPoule3), 'nummers laatste');

            $secondWinnersHorPoule = $rootRound->getHorizontalPoules(QualifyGroup::WINNERS)[1];
            self::assertSame($nameService->getHorizontalPouleName($secondWinnersHorPoule), 'beste nummer 2');

            $secondLosersHorPoule = $rootRound->getHorizontalPoules(QualifyGroup::LOSERS)[1];
            self::assertSame($nameService->getHorizontalPouleName($secondLosersHorPoule), 'slechtste 1 na laatst');


            $structureService->addQualifier($rootRound, QualifyGroup::WINNERS);
            $secondWinnersHorPoule2 = $rootRound->getHorizontalPoules(QualifyGroup::WINNERS)[1];
            self::assertSame($nameService->getHorizontalPouleName($secondWinnersHorPoule2), '2 beste nummers 2');

            $structureService->addQualifier($rootRound, QualifyGroup::LOSERS);
            $secondLosersHorPoule2 = $rootRound->getHorizontalPoules(QualifyGroup::LOSERS)[1];
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
            $structureService = new StructureService([]);
            $structure = $structureService->create($competition, 2);
            $rootRound = $structure->getRootRound();

            $firstPlace = $rootRound->getFirstPlace(QualifyGroup::WINNERS);
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

            $referee = new Referee($competition);
            $referee->setInitials('CDK');
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
