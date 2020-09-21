<?php

namespace Sports\Tests\Round\Number;

use \Exception;
use League\Period\Period;
use Sports\Game;
use Sports\Poule;
use SportsPlanning\Input as PlanningInput;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\Round\Number\GamesValidator;
use Sports\Structure\Service as StructureService;

class GamesValidatorTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator;

    public function testGameWithoutField()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        (new GamesCreator())->createStructureGames( $structure );

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $firstRoundNumber->getRounds()->first()->getPoule(1);
        $firstGame = $firstPoule->getGames()->first();
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

        (new GamesCreator())->createStructureGames( $structure );

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $firstRoundNumber->getRounds()->first()->getPoule(1);
        $removedGame = $firstPoule->getGames()->first();
        $firstPoule->getGames()->removeElement($removedGame);

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

        (new GamesCreator())->createStructureGames( $structure );

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $firstRoundNumber->getRounds()->first()->getPoule(1);
        $game = $firstPoule->getGames()->first();
        $firstHomePlace = $game->getPlaces(Game::HOME)->first()->getPlace();
        $game->setRefereePlace($firstHomePlace);

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

        (new GamesCreator())->createStructureGames( $structure );

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $firstRoundNumber->getRounds()->first()->getPoule(1);
        $game = $firstPoule->getGames()->first();
        $newFieldPriority = $game->getField()->getPriority() === 1 ? 2 : 1;
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

        (new GamesCreator())->createStructureGames( $structure );

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $firstRoundNumber->getRounds()->first()->getPoule(1);
        /** @var Game $game */
        $game = $firstPoule->getGames()->first();
        $newRefereePriority = $game->getReferee()->getPriority() === 1 ? 2 : 1;
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

        (new GamesCreator())->createStructureGames( $structure );

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $firstRoundNumber->getRounds()->first()->getPoule(1);

//        $outputGame = new \Sports\Output\Game();
//        $games = $firstRoundNumber->getGames(Game::ORDER_BY_BATCH);
//        foreach( $games as $gameIt ) {
//            $outputGame->output( $gameIt );
//        }

        /** @var Game $game */
        foreach ($firstPoule->getGames() as $game) {
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

        (new GamesCreator())->createStructureGames( $structure );

        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstPoule = $firstRoundNumber->getRounds()->first()->getPoule(1);

        /** @var Game $game */
        foreach ($firstPoule->getGames() as $game) {
            if ($game->getReferee()->getPriority() === 1 && $game->getBatchNr() <= 3) {
                $game->setReferee(null);
            }
        }

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
        $firstRoundNumber->getPlanningConfig()->setSelfReferee(PlanningInput::SELFREFEREE_SAMEPOULE);

        (new GamesCreator())->createStructureGames( $structure );

//        $outputGame = new \Sports\Output\Game();
//        $games = $firstRoundNumber->getGames(Game::ORDER_BY_BATCH);
//        foreach( $games as $gameIt ) {
//            $outputGame->output( $gameIt );
//        }

        /** @var Poule $firstPoule */
        $firstPoule = $firstRoundNumber->getRounds()->first()->getPoule(1);

        /** @var Game $game */
        $game = $firstPoule->getGames()->first();
        $game->setRefereePlace($firstPoule->getPlace(3));

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
        $firstRoundNumber->getPlanningConfig()->setSelfReferee(PlanningInput::SELFREFEREE_OTHERPOULES);

        (new GamesCreator())->createStructureGames( $structure );

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
        $firstRoundNumber->getPlanningConfig()->setSelfReferee(PlanningInput::SELFREFEREE_OTHERPOULES);

        // 2 pak vervolgend een wedstrijd en laatr deze in de pauze zijn
        // 3 en laat de validator de boel opsporen!
        $start = $competition->getStartDateTime()->modify("+30 minutes");
        $blockedPeriod = new Period($start, $start->modify("+30 minutes"));
        (new GamesCreator())->createStructureGames( $structure );

        /** @var Game[] $games */
        $games = $firstRoundNumber->getGames(Game::ORDER_BY_BATCH);
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

        (new GamesCreator())->createStructureGames( $structure );

        $gamesValidator = new GamesValidator();
        $nrOfReferees = $competition->getReferees()->count();
        self::assertNull($gamesValidator->validateStructure($structure, $nrOfReferees));
    }
}
