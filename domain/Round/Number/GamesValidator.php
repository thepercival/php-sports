<?php

namespace Sports\Round\Number;

use DateTimeImmutable;
use Exception;
use League\Period\Period;
use Sports\Game;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Place;
use Sports\Poule;
use Sports\Structure;
use Sports\Round\Number as RoundNumber;

class GamesValidator
{
    /**
     * @var RoundNumber
     */
    protected $roundNumber;
    /**
     * @var Period|null
     */
    protected $blockedPeriod;

    /**
     * @var array | AgainstGame[] | TogetherGame[]
     */
    protected $games;

    public function __construct()
    {
    }

    public function setBlockedPeriod(Period $period = null)
    {
        $this->blockedPeriod = $period;
    }

    public function validateStructure(Structure $structure, int $nrOfReferees)
    {
        $roundNumber = $structure->getFirstRoundNumber();
        while ($roundNumber !== null) {
            $this->validate($roundNumber, $nrOfReferees);
            $roundNumber = $roundNumber->getNext();
        }
    }

    public function validate(RoundNumber $roundNumber, int $nrOfReferees)
    {
        $this->roundNumber = $roundNumber;
        $this->games = $this->roundNumber->getGames(); // no order
        $this->validateEnoughTotalNrOfGames();
        $this->validateFields();
        if ($this->blockedPeriod !== null) {
            $this->validateGameNotInBlockedPeriod();
        }
        $this->validateAllPlacesSameNrOfGames();
        $this->validateResourcesPerBatch();
        $this->validateEquallyAssigned($nrOfReferees);
        $this->validatePriorityOfFieldsAndReferees();
    }

    protected function validateEnoughTotalNrOfGames()
    {
        if (count($this->games) === 0) {
            throw new Exception("the planning has not enough games", E_ERROR);
        }
    }


    protected function validateFields()
    {
        foreach ($this->games as $game) {
            if ($game->getField() === null) {
                throw new Exception("there is at least one game without a field", E_ERROR);
            }
        }
    }

    protected function validateGameNotInBlockedPeriod()
    {
        $maxNrOfMinutesPerGame = $this->roundNumber->getValidPlanningConfig()->getMaxNrOfMinutesPerGame();
        foreach ($this->games as $game) {
            $gamePeriod = new Period(
                $game->getStartDateTime(),
                $game->getStartDateTime()->modify("+" . $maxNrOfMinutesPerGame . " minutes")
            );
            if ($gamePeriod->overlaps($this->blockedPeriod)) {
                throw new Exception("a game is during a blocked period", E_ERROR);
            }
        }
    }

    protected function validateAllPlacesSameNrOfGames()
    {
        foreach ($this->roundNumber->getPoules() as $poule) {
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
     * @return array|Place[]
     */
    protected function getPlaces($game): array
    {
        return $game->getPlaces()->map(
            /** @var AgainstGamePlace|TogetherGamePlace $gamePlace */
            function ($gamePlace): Place {
                return $gamePlace->getPlace();
            }
        )->toArray();
    }

    protected function validateResourcesPerBatch()
    {
        if ($this->validateResourcesPerBatchHelper() !== true) {
            throw new Exception("more resources per batch than allowed", E_ERROR);
        }
    }

    protected function validateResourcesPerBatchHelper(): bool
    {
        $batchesResources = [];
        foreach ($this->games as $game) {
            if (array_key_exists($game->getBatchNr(), $batchesResources) === false) {
                $batchesResources[$game->getBatchNr()] = array("fields" => [], "referees" => [], "places" => []);
            }
            $batchResources = &$batchesResources[$game->getBatchNr()];
            /** @var array|Place[] $places */
            $places = $this->getPlaces($game);
            if ($game->getRefereePlace() !== null) {
                $places[] = $game->getRefereePlace();
            }
            foreach ($places as $placeIt) {
                if (array_search($placeIt, $batchResources["places"], true) !== false) {
                    return false;
                }
                $batchResources["places"][] = $placeIt;
            }

            /** @var bool|int|string $search */
            $search = array_search($game->getField(), $batchResources["fields"], true);
            if ($search !== false) {
                return false;
            }
            $batchResources["fields"][] = $game->getField();

            if ($game->getReferee() !== null) {
                /** @var bool|int|string $search */
                $search = array_search($game->getReferee(), $batchResources["referees"], true);
                if ($search !== false) {
                    return false;
                }
                $batchResources["referees"][] = $game->getReferee();
            }
        }
        return true;
    }

    protected function validateEquallyAssigned(int $nrOfReferees)
    {
        $fields = [];
        $referees = [];
        $refereePlaces = [];

        foreach ($this->games as $game) {
            $field = $game->getField();
            if (array_key_exists($field->getPriority(), $fields) === false) {
                $fields[$field->getPriority()] = 0;
            }
            $fields[$game->getField()->getPriority()]++;

            $refereePlace = $game->getRefereePlace();
            if ($refereePlace !== null) {
                $pouleNr = $refereePlace->getPoule()->getStructureNumber();
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
            $referees[$game->getReferee()->getPriority()]++;
        }

        $this->validateNrOfGamesRange($fields, "fields");
        $this->validateNrOfGamesRange($referees, "referees");
        if (count($refereePlaces) === 0 && $nrOfReferees > 0 and count($referees) === 0) {
            throw new Exception("no referees have been assigned", E_ERROR);
        }

        if ($this->arePoulesEquallySized()) {
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

    protected function arePoulesEquallySized(): bool
    {
        return ($this->roundNumber->getNrOfPlaces() % count($this->roundNumber->getPoules())) === 0;
    }

    /**
     * @param array $items
     * @param string $suffix
     * @throws Exception
     */
    protected function validateNrOfGamesRange(array $items, string $suffix)
    {
        $minNrOfGames = null;
        $maxNrOfGames = null;
        foreach ($items as $nr => $nrOfGames) {
            if ($minNrOfGames === null || $nrOfGames < $minNrOfGames) {
                $minNrOfGames = $nrOfGames;
            }
            if ($maxNrOfGames === null || $nrOfGames > $maxNrOfGames) {
                $maxNrOfGames = $nrOfGames;
            }
        }
        if ($maxNrOfGames - $minNrOfGames > 1) {
            throw new Exception("too much difference in number of games for " . $suffix, E_ERROR);
        }
    }

    protected function validatePriorityOfFieldsAndReferees()
    {
        $orderedGames = $this->roundNumber->getGames(Game::ORDER_BY_BATCH);
        $gamesFirstBatch = $this->getGamesForBatch($orderedGames);
        $priority = 1;
        foreach ($gamesFirstBatch as $game) {
            if ($game->getField()->getPriority() !== $priority) {
                throw new Exception("fields are not prioritized", E_ERROR);
            }
            if ($game->getReferee() !== null && $game->getReferee()->getPriority() !== $priority) {
                throw new Exception("referees are not prioritized", E_ERROR);
            }
            $priority++;
        }
    }

    /**
     * @param array|Game[] $orderedGames
     * @return array|Game[]
     */
    protected function getGamesForBatch(array $orderedGames): array
    {
        $gamesOrderedByRoundNumber = $this->roundNumber->isFirst() ? $orderedGames : array_reverse($orderedGames);

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
