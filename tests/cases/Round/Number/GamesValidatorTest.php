<?php
declare(strict_types=1);

namespace Sports\Tests\Round\Number;

use \Exception;
use League\Period\Period;
use PHPUnit\Framework\TestCase;
use Sports\Competition\Field;
use Sports\Competition\Referee;
use Sports\Output\Against as AgainstGameOutput;
use SportsHelpers\Against\Side as AgainstSide;
use Sports\Game\Against as AgainstGame;
use Sports\Place;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\Round\Number\GamesValidator;
use Sports\Structure\Service as StructureService;
use SportsPlanning\SelfReferee;

class GamesValidatorTest extends TestCase
{
    use CompetitionCreator;

    public function testGameWithoutField()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        (new GamesCreator())->createStructureGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $structure->getRootRound()->getPoule(1);
        $firstGame = $firstPoule->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $firstGame);
        $firstGame->setField(null);

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testAllPlacesSameNrOfGames()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        (new GamesCreator())->createStructureGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $structure->getRootRound()->getPoule(1);
        $removedGame = $firstPoule->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $removedGame);
        $firstPoule->getAgainstGames()->removeElement($removedGame);

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testResourcesPerBatchMultiplePlaces()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        (new GamesCreator())->createStructureGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $structure->getRootRound()->getPoule(1);
        $game = $firstPoule->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $game);
        $homeGamePlaces = $game->getSidePlaces(AgainstSide::HOME);
        $firstHomeGamePlace = array_shift($homeGamePlaces);
        self::assertInstanceOf(AgainstGamePlace::class, $firstHomeGamePlace);
        $game->setRefereePlace($firstHomeGamePlace->getPlace());

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testResourcesPerBatchMultipleFields()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        (new GamesCreator())->createStructureGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $structure->getRootRound()->getPoule(1);
        $game = $firstPoule->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $game);
        $field = $game->getField();
        self::assertInstanceOf(Field::class, $field);
        $newFieldPriority = $field->getPriority() === 1 ? 2 : 1;
        $game->setField($competition->getField($newFieldPriority));

        $gamesValidator = new GamesValidator();
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testResourcesPerBatchMultipleReferees()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        (new GamesCreator())->createStructureGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $structure->getRootRound()->getPoule(1);
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

    public function testNrOfGamesPerRefereeAndFieldNoRefereesAssigned()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        (new GamesCreator())->createStructureGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $structure->getRootRound()->getPoule(1);

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

    public function testNrOfGamesRange()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        (new GamesCreator())->createStructureGames($structure);

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $structure->getRootRound()->getPoule(1);

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

    public function testNrOfGamesRangeRefereePlace()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $firstRoundNumber->getValidPlanningConfig()->setSelfReferee(SelfReferee::SAMEPOULE);

        (new GamesCreator())->createStructureGames($structure);

//        $outputGame = new AgainstGameOutput();
//        $games = $firstRoundNumber->getGames(AgainstGame::ORDER_BY_BATCH);
//        foreach( $games as $gameIt ) {
//            $outputGame->output( $gameIt );
//        }

        $firstPoule = $structure->getRootRound()->getPoule(1);

        $game = $firstPoule->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $game);
        $availablePlaces = $firstPoule->getPlaces()->filter(function (Place $place) use ($game): bool {
            return !$game->isParticipating($place) && $place !== $game->getRefereePlace();
        });
        $firstAvailablePlace = $availablePlaces->first();
        self::assertInstanceOf(Place::class, $firstAvailablePlace);
        $game->setRefereePlace();

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

    public function testNrOfGamesRangeRefereePlaceDifferentPouleSizes()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 9, 2);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $firstRoundNumber->getValidPlanningConfig()->setSelfReferee(SelfReferee::OTHERPOULES);

        (new GamesCreator())->createStructureGames($structure);

//        $outputGame = new \Sports\Output\Game();
//        $games = $firstRoundNumber->getGames(Game::ORDER_BY_BATCH);
//        foreach( $games as $gameIt ) {
//            $outputGame->output( $gameIt );
//        }

        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        self::assertNull($gamesValidator->validate($firstRoundNumber, $nrOfReferees));
    }

    public function testGameInBreak()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 9, 2);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $firstRoundNumber->getValidPlanningConfig()->setSelfReferee(SelfReferee::OTHERPOULES);

        // 2 pak vervolgend een wedstrijd en laatr deze in de pauze zijn
        // 3 en laat de validator de boel opsporen!
        $start = $competition->getStartDateTime()->modify("+30 minutes");
        $blockedPeriod = new Period($start, $start->modify("+30 minutes"));
        (new GamesCreator())->createStructureGames($structure);

        /** @var AgainstGame[] $games */
        $games = $firstRoundNumber->getGames(AgainstGame::ORDER_BY_BATCH);
        $game = reset($games);
        $game->setStartDateTime($start->modify("+10 minutes"));
//        $outputGame = new \Sports\Output\Game();
//        $games = $firstRoundNumber->getGames(Game::ORDER_BY_BATCH);
//        foreach( $games as $gameIt ) {
//            $outputGame->output( $gameIt );
//        }

        $gamesValidator = new GamesValidator();
        $gamesValidator->setBlockedPeriod($blockedPeriod);
        self::expectException(Exception::class);
        $nrOfReferees = $competition->getReferees()->count();
        $gamesValidator->validate($firstRoundNumber, $nrOfReferees);
    }

    public function testValid()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        (new GamesCreator())->createStructureGames($structure);

        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        self::assertNull($gamesValidator->validateStructure($structure, $nrOfReferees));
    }
}
