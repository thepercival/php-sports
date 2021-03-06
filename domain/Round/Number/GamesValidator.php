<?php
declare(strict_types=1);

namespace Sports\Round\Number;

use DateTimeImmutable;
use Exception;
use League\Period\Period;
use Sports\Game;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Order;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Place;
use Sports\Poule;
use Sports\Structure;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\SelfReferee;

class GamesValidator
{
    public function __construct()
    {
    }

    public function validateStructure(Structure $structure, int $nrOfReferees, Period $period = null): void
    {
        $roundNumber = $structure->getFirstRoundNumber();
        while ($roundNumber !== null) {
            $this->validate($roundNumber, $nrOfReferees, $period);
            $roundNumber = $roundNumber->getNext();
        }
    }

    public function validate(RoundNumber $roundNumber, int $nrOfReferees, Period|null $blockedPeriod = null): void
    {
        $this->validateEnoughTotalNrOfGames($roundNumber);
        $this->validateFields($roundNumber);
        $this->validateReferee($roundNumber, $nrOfReferees);
        $this->validateSelfReferee($roundNumber);
        if ($blockedPeriod !== null) {
            $this->validateGameNotInBlockedPeriod($roundNumber, $blockedPeriod);
        }
        $this->validateAllPlacesSameNrOfGames($roundNumber);
        $this->validateResourcesPerBatch($roundNumber);
        $this->validateEquallyAssigned($roundNumber, $nrOfReferees);
        $this->validatePriorityOfFieldsAndReferees($roundNumber);
    }

    protected function validateEnoughTotalNrOfGames(RoundNumber $roundNumber): void
    {
        if (!$roundNumber->allPoulesHaveGames()) {
            throw new Exception("the planning has not enough games", E_ERROR);
        }
    }


    protected function validateFields(RoundNumber $roundNumber): void
    {
        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
            if ($game->getField() === null) {
                throw new Exception("there is at least one game without a field", E_ERROR);
            }
        }
    }

    protected function validateReferee(RoundNumber $roundNumber, int $nrOfReferees): void
    {
        $selfReferee = $roundNumber->getValidPlanningConfig()->getSelfReferee();
        if ($selfReferee !== SelfReferee::DISABLED || $nrOfReferees === 0) {
            return;
        }
        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
            if ($game->getReferee() === null) {
                throw new Exception("the game should have a referee", E_ERROR);
            }
        }
    }

    protected function validateSelfReferee(RoundNumber $roundNumber): void
    {
        $pouleStructure = $roundNumber->createPouleStructure();
        $selfReferee = $roundNumber->getValidPlanningConfig()->getSelfReferee();
        $sportVariants = $roundNumber->getCompetition()->createSportVariants();
        if (!$pouleStructure->isSelfRefereeBeAvailable($selfReferee, $sportVariants)) {
            return;
        }
        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
            if ($game->getRefereePlace() === null) {
                throw new Exception("the game should have a refereeplace", E_ERROR);
            }
        }
    }

    protected function validateGameNotInBlockedPeriod(RoundNumber $roundNumber, Period $blockedPeriod): void
    {
        $maxNrOfMinutesPerGame = $roundNumber->getValidPlanningConfig()->getMaxNrOfMinutesPerGame();
        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
            $gamePeriod = new Period(
                $game->getStartDateTime(),
                $game->getStartDateTime()->modify("+" . $maxNrOfMinutesPerGame . " minutes")
            );
            if ($gamePeriod->overlaps($blockedPeriod)) {
                throw new Exception("a game is during a blocked period", E_ERROR);
            }
        }
    }

    protected function validateAllPlacesSameNrOfGames(RoundNumber $roundNumber): void
    {
        foreach ($roundNumber->getPoules() as $poule) {
            if ($this->allPlacesInPouleSameNrOfGames($poule) === false) {
                throw new Exception("not all places within poule have same number of games", E_ERROR);
            }
        }
    }

    protected function allPlacesInPouleSameNrOfGames(Poule $poule): bool
    {
        $nrOfGames = [];
        foreach ($poule->getGames() as $game) {
            /** @var array|Place[] $places */
            $places = $this->getPlaces($game);
            /** @var Place $place */
            foreach ($places as $place) {
                if (array_key_exists($place->getRoundLocationId(), $nrOfGames) === false) {
                    $nrOfGames[$place->getRoundLocationId()] = 0;
                }
                $nrOfGames[$place->getRoundLocationId()]++;
            }
        }
        $value = reset($nrOfGames);
        foreach ($nrOfGames as $valueIt) {
            if ($value !== $valueIt) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param TogetherGame|AgainstGame $game
     * @return list<Place>
     */
    protected function getPlaces($game): array
    {
        return array_values($game->getPlaces()->map(
            function (AgainstGamePlace|TogetherGamePlace $gamePlace): Place {
                return $gamePlace->getPlace();
            }
        )->toArray());
    }

    protected function validateResourcesPerBatch(RoundNumber $roundNumber): void
    {
        if ($this->validateResourcesPerBatchHelper($roundNumber) !== true) {
            throw new Exception("more resources per batch than allowed", E_ERROR);
        }
    }

    protected function validateResourcesPerBatchHelper(RoundNumber $roundNumber): bool
    {
        $batchesResources = [];
        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
            if (array_key_exists($game->getBatchNr(), $batchesResources) === false) {
                $batchesResources[$game->getBatchNr()] = array("fields" => [], "referees" => [], "places" => []);
            }
            $places = $this->getPlaces($game);
            if ($game->getRefereePlace() !== null) {
                $places[] = $game->getRefereePlace();
            }
            foreach ($places as $placeIt) {
                /** @var bool|int|string $search */
                $search = array_search($placeIt, $batchesResources[$game->getBatchNr()]["places"], true);
                if ($search !== false) {
                    return false;
                }
                array_push($batchesResources[$game->getBatchNr()]["places"], $placeIt);
            }
            $field = $game->getField();
            if ($field !== null) {
                /** @var bool|int|string $search */
                $search = array_search($field, $batchesResources[$game->getBatchNr()]["fields"], true);
                if ($search !== false) {
                    return false;
                }
                array_push($batchesResources[$game->getBatchNr()]["fields"], $field);
            }

            $referee = $game->getReferee();
            if ($referee !== null) {
                /** @var bool|int|string $search */
                $search = array_search($referee, $batchesResources[$game->getBatchNr()]["referees"], true);
                if ($search !== false) {
                    return false;
                }
                array_push($batchesResources[$game->getBatchNr()]["referees"], $referee);
            }
        }
        return true;
    }

    protected function validateEquallyAssigned(RoundNumber $roundNumber, int $nrOfReferees): void
    {
        $fields = [];
        $referees = [];
        $refereePlaces = [];

        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
            $field = $game->getField();
            if ($field !== null) {
                if (array_key_exists($field->getPriority(), $fields) === false) {
                    $fields[$field->getPriority()] = 0;
                }
                $fields[$field->getPriority()]++;
            }

            $refereePlace = $game->getRefereePlace();
            if ($refereePlace !== null) {
                $pouleNr = $refereePlace->getPoule()->getStructureLocation();
                if (array_key_exists($pouleNr, $refereePlaces) === false) {
                    $refereePlaces[$pouleNr] = [];
                }
                if (array_key_exists($refereePlace->getRoundLocationId(), $refereePlaces[$pouleNr]) === false) {
                    $refereePlaces[$pouleNr][$refereePlace->getRoundLocationId()] = 0;
                }
                $refereePlaces[$pouleNr][$refereePlace->getRoundLocationId()]++;
            }

            $referee = $game->getReferee();
            if ($refereePlace !== null || $referee === null) {
                continue;
            }
            if (array_key_exists($referee->getPriority(), $referees) === false) {
                $referees[$referee->getPriority()] = 0;
            }
            $referees[$referee->getPriority()]++;
        }

        $this->validateNrOfGamesRange($fields, "fields");
        $this->validateNrOfGamesRange($referees, "referees");
        if (count($refereePlaces) === 0 && $nrOfReferees > 0 and count($referees) === 0) {
            throw new Exception("no referees have been assigned", E_ERROR);
        }

        if ($this->arePoulesEquallySized($roundNumber)) {
            $refereePlacesMerged = [];
            foreach ($refereePlaces as $refereePlacesPerPoule) {
                $refereePlacesMerged = array_merge($refereePlacesMerged, $refereePlacesPerPoule);
            }
            $this->validateNrOfGamesRange($refereePlacesMerged, "refereePlaces");
        } else {
            foreach ($refereePlaces as $refereePlacesPerPoule) {
                $this->validateNrOfGamesRange($refereePlacesPerPoule, "refereePlaces");
            }
        }
    }

    protected function arePoulesEquallySized(RoundNumber $roundNumber): bool
    {
        return ($roundNumber->getNrOfPlaces() % count($roundNumber->getPoules())) === 0;
    }

    /**
     * @param array<string|int, int> $gameAmounts
     * @param string $suffix
     * @throws Exception
     * @return void
     */
    protected function validateNrOfGamesRange(array $gameAmounts, string $suffix): void
    {
        /** @var int|null $minNrOfGames */
        $minNrOfGames = null;
        /** @var int|null $maxNrOfGames */
        $maxNrOfGames = null;
        foreach ($gameAmounts as $nrOfGames) {
            if ($minNrOfGames === null || $nrOfGames < $minNrOfGames) {
                $minNrOfGames = $nrOfGames;
            }
            if ($maxNrOfGames === null || $nrOfGames > $maxNrOfGames) {
                $maxNrOfGames = $nrOfGames;
            }
        }
        if ($minNrOfGames !== null && $maxNrOfGames !== null && $maxNrOfGames - $minNrOfGames > 1) {
            throw new Exception("too much difference in number of games for " . $suffix, E_ERROR);
        }
    }

    protected function validatePriorityOfFieldsAndReferees(RoundNumber $roundNumber): void
    {
        $orderedGames = $roundNumber->getGames(Order::ByBatch);
        $gamesFirstBatch = $this->getGamesForBatch($roundNumber, $orderedGames);
        $priority = 1;
        foreach ($gamesFirstBatch as $game) {
//            $field = $game->getField();
//            if ($field !== null && $field->getPriority() !== $priority) {
//                throw new Exception("fields are not prioritized", E_ERROR);
//            }
            $referee = $game->getReferee();
            if ($referee !== null && $referee->getPriority() !== $priority) {
                throw new Exception("referees are not prioritized", E_ERROR);
            }
            $priority++;
        }
    }

    /**
     * @param list<AgainstGame|TogetherGame> $orderedGames
     * @return list<AgainstGame|TogetherGame>
     */
    protected function getGamesForBatch(RoundNumber $roundNumber, array $orderedGames): array
    {
        $gamesOrderedByRoundNumber = $roundNumber->isFirst() ? $orderedGames : array_reverse($orderedGames);

        $game = array_shift($gamesOrderedByRoundNumber);
        $gamesBatch = [];
        $batchNr = null;
        while ($game !== null && ($game->getBatchNr() === $batchNr || $batchNr === null)) {
            $gamesBatch[] = $game;
            $batchNr = $game->getBatchNr();
            $game = array_shift($gamesOrderedByRoundNumber);
        }
        return $gamesBatch;
    }
}
