<?php

declare(strict_types=1);

namespace Sports\Team\Role;

use Doctrine\Common\Collections\Collection;
use Sports\Competition;
use Sports\Round;
use Sports\Team\Player;

final class Validator
{
    public function __construct()
    {
    }

    public function validate(Player $player, Competition $competition): void
    {
        $seasonPeriod = $competition->getSeason()->getPeriod();

        if (!$seasonPeriod->contains($player->getPeriod())) {
            throw new \Exception('game x not within player-period', E_ERROR);
        }

        foreach ($player->getAgainstGames($seasonPeriod) as $againstGame) {
            if (!$player->getPeriod()->contains($againstGame->getStartDateTime())) {
                throw new \Exception('game x not within player-period', E_ERROR);
            }
        }

        $personPlayers = $player->getPerson()->getPlayers(null, $seasonPeriod);
        foreach ($personPlayers as $personPlayer) {
            if ($personPlayer !== $player && $personPlayer->getPeriod()->overlaps($player->getPeriod())) {
                throw new \Exception('overlapping periods found', E_ERROR);
            }
        }
        // $this->validateRound($competition, $structure->getRootRound(), $players);
    }

    /**
     * @param Competition $competition
     * @param Round $round
     * @param Collection<int|string, Player> $players
     */
    protected function validateRound(Competition $competition, Round $round, Collection $players): void
    {
        // 1x door de games en dan bewaren aan welke is meegedaan dus
        // map[playerPeriodId => [games]]
//        $gameMap = $this->getGameMap($round);
//        $player =
//        $teams = $competition->getTeams();
//        foreach ($round->getPoules() as $poule) {
//            foreach ($poule->getGames() as $game) {
//            }
//            // $persons = $this->getActivePersons($poule, $team);
//            // $competition->getSeason()
//
//
//
//                    // geen overlappende
//                // sort by person LastName and than Period.Start
////            }
//        }
//
//        $this->validateGames($roundNumber);
//        $this->validateFields($roundNumber);
//        $this->validateReferee($roundNumber, $nrOfReferees);
//        $this->validateSelfReferee($roundNumber);
        foreach ($round->getChildren() as $childRound) {
            $this->validateRound($competition, $childRound, $players);
        }
    }

//    /**
//     * @param Round $round
//     * @return array<int|string, list<AgainstGame>>
//     */
//    protected function getGameMap(Round $round): array
//    {
//        $gameMap = [];
//        foreach ($round->getPoules() as $poule) {
//            foreach ($poule->getAgainstGames() as $againstGame) {
//                foreach ($againstGame->getPlaces() as $againstGamePlace) {
//                    foreach ($againstGamePlace->getGameParticipations()  as $gameParticipation) {
//                        $playerPeriod = $gameParticipation->getPlayerPeriod();
//                        if (!isset($gameMap[$playerPeriod->getId()])) {
//                            $gameMap[$playerPeriod->getId()] = [];
//                        }
//                        $gameMap[$playerPeriod->getId()][] = $againstGame;
//                    }
//                }
//            }
//        }
//        return $gameMap;
//    }

    ////////////////////////////////////////////////////

//    protected function validateEnoughTotalNrOfGames(RoundNumber $roundNumber): void
//    {
//        if (!$roundNumber->allPoulesHaveGames()) {
//            throw new Exception("the planning has not enough games", E_ERROR);
//        }
//    }
//
//
//    protected function validateFields(RoundNumber $roundNumber): void
//    {
//        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
//            $field = $game->getField();
//            if ($field === null) {
//                throw new Exception("there is at least one game without a field", E_ERROR);
//            }
//            if ($field->getCompetitionSport() !== $game->getCompetitionSport()) {
//                throw new Exception("game->field->sport should be equal to game->sport", E_ERROR);
//            }
//        }
//    }
//
//    protected function validateReferee(RoundNumber $roundNumber, int $nrOfReferees): void
//    {
//        $selfReferee = $roundNumber->getValidPlanningConfig()->getSelfReferee();
//        if ($selfReferee !== SelfReferee::Disabled || $nrOfReferees === 0) {
//            return;
//        }
//        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
//            if ($game->getReferee() === null) {
//                throw new Exception("the game should have a referee", E_ERROR);
//            }
//        }
//    }
//
//    protected function validateSelfReferee(RoundNumber $roundNumber): void
//    {
//        $pouleStructure = $roundNumber->createPouleStructure();
//        $selfReferee = $roundNumber->getValidPlanningConfig()->getSelfReferee();
//        $sportVariants = $roundNumber->getCompetition()->createSportVariants();
//        if (!$pouleStructure->isSelfRefereeBeAvailable($selfReferee, $sportVariants)) {
//            return;
//        }
//        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
//            if ($game->getRefereePlace() === null) {
//                throw new Exception("the game should have a refereeplace", E_ERROR);
//            }
//        }
//    }
//
//    protected function validateGameNotInBlockedPeriod(RoundNumber $roundNumber, Period $blockedPeriod): void
//    {
//        $maxNrOfMinutesPerGame = $roundNumber->getValidPlanningConfig()->getMaxNrOfMinutesPerGame();
//        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
//            $gamePeriod = new Period(
//                $game->getStartDateTime(),
//                $game->getStartDateTime()->modify("+" . $maxNrOfMinutesPerGame . " minutes")
//            );
//            if ($gamePeriod->overlaps($blockedPeriod)) {
//                throw new Exception("a game is during a blocked period", E_ERROR);
//            }
//        }
//    }
//
//    protected function validateAllPlacesSameNrOfGames(RoundNumber $roundNumber): void
//    {
//        foreach ($roundNumber->getCompetitionSports() as $competitionSport) {
//            $sportVariant = $competitionSport->createVariant();
//            if ($sportVariant instanceof AgainstSportVariant && $sportVariant->isMixed()) {
//                continue;
//            }
//            foreach ($roundNumber->getPoules() as $poule) {
//                if ($this->allPlacesInPouleSameNrOfGames($poule, $competitionSport) === false) {
//                    throw new Exception("not all places within poule have same number of games", E_ERROR);
//                }
//            }
//        }
//    }
//
//    protected function allPlacesInPouleSameNrOfGames(Poule $poule, CompetitionSport $competitionSport): bool
//    {
//        $nrOfGames = [];
//        foreach ($poule->getGames($competitionSport) as $game) {
//            /** @var array|Place[] $places */
//            $places = $this->getPlaces($game);
//            /** @var Place $place */
//            foreach ($places as $place) {
//                if (array_key_exists($place->getRoundLocationId(), $nrOfGames) === false) {
//                    $nrOfGames[$place->getRoundLocationId()] = 0;
//                }
//                $nrOfGames[$place->getRoundLocationId()]++;
//            }
//        }
//        $value = reset($nrOfGames);
//        foreach ($nrOfGames as $valueIt) {
//            if ($value !== $valueIt) {
//                return false;
//            }
//        }
//        return true;
//    }
//
//    /**
//     * @param TogetherGame|AgainstGame $game
//     * @return list<Place>
//     */
//    protected function getPlaces($game): array
//    {
//        return array_values($game->getPlaces()->map(
//            function (AgainstGamePlace|TogetherGamePlace $gamePlace): Place {
//                return $gamePlace->getPlace();
//            }
//        )->toArray());
//    }
//
//    protected function validateResourcesPerBatch(RoundNumber $roundNumber): void
//    {
//        if ($this->validateResourcesPerBatchHelper($roundNumber) !== true) {
//            throw new Exception("more resources per batch than allowed", E_ERROR);
//        }
//    }
//
//    protected function validateResourcesPerBatchHelper(RoundNumber $roundNumber): bool
//    {
//        $batchesResources = [];
//        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
//            if (array_key_exists($game->getBatchNr(), $batchesResources) === false) {
//                $batchesResources[$game->getBatchNr()] = array("fields" => [], "referees" => [], "places" => []);
//            }
//            $places = $this->getPlaces($game);
//            if ($game->getRefereePlace() !== null) {
//                $places[] = $game->getRefereePlace();
//            }
//            foreach ($places as $placeIt) {
//                /** @var bool|int|string $search */
//                $search = array_search($placeIt, $batchesResources[$game->getBatchNr()]["places"], true);
//                if ($search !== false) {
//                    return false;
//                }
//                array_push($batchesResources[$game->getBatchNr()]["places"], $placeIt);
//            }
//            $field = $game->getField();
//            if ($field !== null) {
//                /** @var bool|int|string $search */
//                $search = array_search($field, $batchesResources[$game->getBatchNr()]["fields"], true);
//                if ($search !== false) {
//                    return false;
//                }
//                array_push($batchesResources[$game->getBatchNr()]["fields"], $field);
//            }
//
//            $referee = $game->getReferee();
//            if ($referee !== null) {
//                /** @var bool|int|string $search */
//                $search = array_search($referee, $batchesResources[$game->getBatchNr()]["referees"], true);
//                if ($search !== false) {
//                    return false;
//                }
//                array_push($batchesResources[$game->getBatchNr()]["referees"], $referee);
//            }
//        }
//        return true;
//    }
//
//    protected function validateEquallyAssigned(RoundNumber $roundNumber, int $nrOfReferees): void
//    {
//        $fields = [];
//        $referees = [];
//        $refereePlaces = [];
//
//        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
//            $field = $game->getField();
//            if ($field !== null) {
//                if (array_key_exists($field->getPriority(), $fields) === false) {
//                    $fields[$field->getPriority()] = 0;
//                }
//                $fields[$field->getPriority()]++;
//            }
//
//            $refereePlace = $game->getRefereePlace();
//            if ($refereePlace !== null) {
//                $pouleNr = $refereePlace->getPoule()->getStructureLocation();
//                if (array_key_exists($pouleNr, $refereePlaces) === false) {
//                    $refereePlaces[$pouleNr] = [];
//                }
//                if (array_key_exists($refereePlace->getRoundLocationId(), $refereePlaces[$pouleNr]) === false) {
//                    $refereePlaces[$pouleNr][$refereePlace->getRoundLocationId()] = 0;
//                }
//                $refereePlaces[$pouleNr][$refereePlace->getRoundLocationId()]++;
//            }
//
//            $referee = $game->getReferee();
//            if ($refereePlace !== null || $referee === null) {
//                continue;
//            }
//            if (array_key_exists($referee->getPriority(), $referees) === false) {
//                $referees[$referee->getPriority()] = 0;
//            }
//            $referees[$referee->getPriority()]++;
//        }
//
//        $this->validateNrOfGamesRange($fields, "fields");
//        $this->validateNrOfGamesRange($referees, "referees");
//        if (count($refereePlaces) === 0 && $nrOfReferees > 0 and count($referees) === 0) {
//            throw new Exception("no referees have been assigned", E_ERROR);
//        }
//
//        if ($this->arePoulesEquallySized($roundNumber)) {
//            $refereePlacesMerged = [];
//            foreach ($refereePlaces as $refereePlacesPerPoule) {
//                $refereePlacesMerged = array_merge($refereePlacesMerged, $refereePlacesPerPoule);
//            }
//            $this->validateNrOfGamesRange($refereePlacesMerged, "refereePlaces");
//        } else {
//            foreach ($refereePlaces as $refereePlacesPerPoule) {
//                $this->validateNrOfGamesRange($refereePlacesPerPoule, "refereePlaces");
//            }
//        }
//    }
//
//    protected function arePoulesEquallySized(RoundNumber $roundNumber): bool
//    {
//        $nrOfPlaces = null;
//        foreach ($roundNumber->getPoules() as $poule) {
//            $nrOfPoulePlaces = $poule->getPlaces()->count();
//            if ($nrOfPlaces === null) {
//                $nrOfPlaces = $nrOfPoulePlaces;
//                continue;
//            }
//            if ($nrOfPlaces !== $nrOfPoulePlaces) {
//                return false;
//            }
//        }
//        return true;
//    }
//
//    /**
//     * @param array<string|int, int> $gameAmounts
//     * @param string $suffix
//     * @throws Exception
//     * @return void
//     */
//    protected function validateNrOfGamesRange(array $gameAmounts, string $suffix): void
//    {
//        /** @var int|null $minNrOfGames */
//        $minNrOfGames = null;
//        /** @var int|null $maxNrOfGames */
//        $maxNrOfGames = null;
//        foreach ($gameAmounts as $nrOfGames) {
//            if ($minNrOfGames === null || $nrOfGames < $minNrOfGames) {
//                $minNrOfGames = $nrOfGames;
//            }
//            if ($maxNrOfGames === null || $nrOfGames > $maxNrOfGames) {
//                $maxNrOfGames = $nrOfGames;
//            }
//        }
//        if ($minNrOfGames !== null && $maxNrOfGames !== null && $maxNrOfGames - $minNrOfGames > 1) {
//            throw new Exception("too much difference in number of games for " . $suffix, E_ERROR);
//        }
//    }
//
//    protected function validatePriorityOfFieldsAndReferees(RoundNumber $roundNumber): void
//    {
//        $orderedGames = $roundNumber->getGames(Order::ByBatch);
//        $gamesFirstBatch = $this->getGamesForBatch($roundNumber, $orderedGames);
//        $priority = 1;
//        foreach ($gamesFirstBatch as $game) {
////            $field = $game->getField();
////            if ($field !== null && $field->getPriority() !== $priority) {
////                throw new Exception("fields are not prioritized", E_ERROR);
////            }
//            $referee = $game->getReferee();
//            if ($referee !== null && $referee->getPriority() !== $priority) {
//                throw new Exception("referees are not prioritized", E_ERROR);
//            }
//            $priority++;
//        }
//    }
//
//    /**
//     * @param list<AgainstGame|TogetherGame> $orderedGames
//     * @return list<AgainstGame|TogetherGame>
//     */
//    protected function getGamesForBatch(RoundNumber $roundNumber, array $orderedGames): array
//    {
//        $gamesOrderedByRoundNumber = $roundNumber->isFirst() ? $orderedGames : array_reverse($orderedGames);
//
//        $game = array_shift($gamesOrderedByRoundNumber);
//        $gamesBatch = [];
//        $batchNr = null;
//        while ($game !== null && ($game->getBatchNr() === $batchNr || $batchNr === null)) {
//            $gamesBatch[] = $game;
//            $batchNr = $game->getBatchNr();
//            $game = array_shift($gamesOrderedByRoundNumber);
//        }
//        return $gamesBatch;
//    }
}
