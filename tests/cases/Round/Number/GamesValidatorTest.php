<?php

declare(strict_types=1);

namespace Sports\Tests\Round\Number;

use Exception;
use League\Period\Period;
use PHPUnit\Framework\TestCase;
use Sports\Competition\Field;
use Sports\Competition\Referee;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Order as GameOrder;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Output\Game\Against as AgainstGameOutput;
use Sports\Place;
use Sports\Round\Number\GamesValidator;
use Sports\Round\Number\PlanningInputCreator;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\TestHelper\StructureEditorCreator;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;

class GamesValidatorTest extends TestCase
{
    use CompetitionCreator;
    use StructureEditorCreator;

    public function testGameWithoutField(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5]);

        (new GamesCreator())->createStructureGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $structure->getSingleCategory()->getRootRound()->getFirstPoule();
        $firstGame = $firstPoule->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $firstGame);
        $firstGame->setField(null);

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testAllPlacesSameNrOfGames(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5]);

        (new GamesCreator())->createStructureGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $structure->getSingleCategory()->getRootRound()->getFirstPoule();
        $removedGame = $firstPoule->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $removedGame);
        $firstPoule->getAgainstGames()->removeElement($removedGame);

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testResourcesPerBatchMultiplePlaces(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5]);

        (new GamesCreator())->createStructureGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $structure->getSingleCategory()->getRootRound()->getFirstPoule();
        $game = $firstPoule->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $game);
        $homeGamePlaces = $game->getSidePlaces(AgainstSide::Home);
        $firstHomeGamePlace = array_shift($homeGamePlaces);
        self::assertInstanceOf(AgainstGamePlace::class, $firstHomeGamePlace);
        $game->setRefereePlace($firstHomeGamePlace->getPlace());

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testResourcesPerBatchMultipleFields(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5]);

        (new GamesCreator())->createStructureGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $structure->getSingleCategory()->getRootRound()->getFirstPoule();
        $game = $firstPoule->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $game);
        $field = $game->getField();
        self::assertInstanceOf(Field::class, $field);
        $newFieldPriority = $field->getPriority() === 1 ? 2 : 1;
        $game->setField($competition->getSingleSport()->getField($newFieldPriority));

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testResourcesPerBatchMultipleReferees(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5]);

        (new GamesCreator())->createStructureGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $structure->getSingleCategory()->getRootRound()->getFirstPoule();
        $game = $firstPoule->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $game);
        $referee = $game->getReferee();
        self::assertInstanceOf(Referee::class, $referee);
        $newRefereePriority = $referee->getPriority() === 1 ? 2 : 1;
        $game->setReferee($competition->getReferee($newRefereePriority));

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testNrOfGamesPerRefereeAndFieldNoRefereesAssigned(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5]);

        (new GamesCreator())->createStructureGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $structure->getSingleCategory()->getRootRound()->getFirstPoule();

//        $outputGame = new \Sports\Output\Game();
//        $games = $firstRoundNumber->getGames(Game::ORDER_BY_BATCH);
//        foreach( $games as $gameIt ) {
//            $outputGame->output( $gameIt );
//        }

        foreach ($firstPoule->getAgainstGames() as $game) {
            $game->setReferee(null);
        }

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testNrOfGamesRange(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5]);

        (new GamesCreator())->createStructureGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $structure->getSingleCategory()->getRootRound()->getFirstPoule();

//        $outputGame = new AgainstGameOutput();
//        $games = $firstRoundNumber->getGames(AgainstGame::ORDER_BY_BATCH);
//        foreach( $games as $gameIt ) {
//            $outputGame->output( $gameIt );
//        }

        foreach ($firstPoule->getAgainstGames() as $game) {
            $referee = $game->getReferee();
            self::assertInstanceOf(Referee::class, $referee);
            if ($referee->getPriority() === 1 && $game->getBatchNr() <= 3) {
                $game->setReferee(null);
            }
        }

//        $outputGame = new AgainstGameOutput();
//        $games = $firstRoundNumber->getGames(AgainstGame::ORDER_BY_BATCH);
//        foreach( $games as $gameIt ) {
//            $outputGame->output( $gameIt );
//        }

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testNrOfGamesRangeRefereePlace(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5]);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $firstRoundNumber->getValidPlanningConfig()->setSelfReferee(SelfReferee::SamePoule);

        (new GamesCreator())->createStructureGames($structure);

//        $outputGame = new AgainstGameOutput();
//        $games = $firstRoundNumber->getGames(AgainstGame::ORDER_BY_BATCH);
//        foreach( $games as $gameIt ) {
//            $outputGame->output( $gameIt );
//        }

        $firstPoule = $structure->getSingleCategory()->getRootRound()->getFirstPoule();

        $game = $firstPoule->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $game);
        $availablePlaces = $firstPoule->getPlaces()->filter(function (Place $place) use ($game): bool {
            return !$game->isParticipating($place) && $place !== $game->getRefereePlace();
        });
        $firstAvailablePlace = $availablePlaces->first();
        self::assertInstanceOf(Place::class, $firstAvailablePlace);
        $lastAvailablePlace = $availablePlaces->last();
        self::assertInstanceOf(Place::class, $lastAvailablePlace);
        $game->setRefereePlace($lastAvailablePlace);

//        $outputGame = new AgainstGameOutput();
//        $games = $firstRoundNumber->getGames(AgainstGame::ORDER_BY_BATCH);
//        foreach ($games as $gameIt) {
//            $outputGame->output($gameIt);
//        }

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testNrOfGamesRangeRefereePlaceDifferentPouleSizes(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5,4]);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $firstRoundNumber->getValidPlanningConfig()->setSelfReferee(SelfReferee::OtherPoules);

        (new GamesCreator())->createStructureGames($structure);

//        $outputGame = new \Sports\Output\Game\Against();
//        $games = $firstRoundNumber->getGames(GameOrder::ByBatch);
//        foreach( $games as $gameIt ) {
//            $outputGame->output( $gameIt );
//        }

        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        self::expectNotToPerformAssertions();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testGameInRecess(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5, 4]);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $firstRoundNumber->getValidPlanningConfig()->setSelfReferee(SelfReferee::OtherPoules);

        // 2 pak vervolgend een wedstrijd en laatr deze in de pauze zijn
        // 3 en laat de validator de boel opsporen!
        $start = $competition->getStartDateTime()->add(new \DateInterval('PT30M'));
        $blockedPeriod = new Period($start, $start->add(new \DateInterval('PT30M')));
        (new GamesCreator())->createStructureGames($structure);

        $games = $firstRoundNumber->getGames(GameOrder::ByBatch);
        $game = reset($games);
        self::assertInstanceOf(AgainstGame::class, $game);
        $game->setStartDateTime($start->add(new \DateInterval('PT10M')));
//        $outputGame = new \Sports\Output\Game();
//        $games = $firstRoundNumber->getGames(Game::ORDER_BY_BATCH);
//        foreach( $games as $gameIt ) {
//            $outputGame->output( $gameIt );
//        }

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees, true, [$blockedPeriod]);
    }

    public function testMultiSportsFieldRange(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(3),
            $this->getAgainstGppSportVariantWithFields(3),
            $this->getAgainstGppSportVariantWithFields(2)
        ];
        $competition = $this->createCompetition($sportVariantsWithFields);

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [3, 3, 3]);

        (new GamesCreator())->createStructureGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

//        $outputGame = new AgainstGameOutput();
//        $games = $firstRoundNumber->getGames(GameOrder::ByBatch);
//        foreach( $games as $gameIt ) {
//            $outputGame->output( $gameIt );
//        }

        $gamesValidator = new GamesValidator();
        self::expectNotToPerformAssertions();
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testValid(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5]);

        (new GamesCreator())->createStructureGames($structure);

        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        self::expectNotToPerformAssertions();
        $gamesValidator->validateStructure($structure, $nrOfReferees);
    }

    public function testValidMultiSports(): void
    {
        $competition = $this->createCompetition(
            [
                $this->getAgainstGppSportVariantWithFields(1),
                new SportVariantWithFields(new Single(1, 1), 1)
            ]
        );

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [5]);

        (new GamesCreator())->createStructureGames($structure);

        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        self::expectNotToPerformAssertions();
        $gamesValidator->validateStructure($structure, $nrOfReferees);
    }
}
