<?php

declare(strict_types=1);

namespace Sports\Round\Number;

use Exception;
use League\Period\Period;
use Sports\Competition\Field;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Order;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Game\Together as TogetherGame;
use Sports\Place;
use Sports\Poule;
use Sports\Round\Number as RoundNumber;
use Sports\Structure;
use SportsPlanning\PouleStructure as PlanningPouleStructure;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsHelpers\Sport\Variant\Creator as VariantCreator;
use SportsHelpers\Sport\Variant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsHelpers\SelfReferee;

class GamesValidator
{
    public function __construct()
    {
    }

    /**
     * @param Structure $structure
     * @param int $nrOfReferees
     * @param bool $validatePriority
     * @param list<Period> $blockedPeriods
     */
    public function validateStructure(
        Structure $structure,
        int $nrOfReferees,
        bool $validatePriority = true,
        array $blockedPeriods = []
    ): void {
        $roundNumber = $structure->getFirstRoundNumber();
        while ($roundNumber !== null) {
            $this->validate($roundNumber, $nrOfReferees, $validatePriority, $blockedPeriods);
            $roundNumber = $roundNumber->getNext();
        }
    }

    /**
     * @param RoundNumber $roundNumber
     * @param int $nrOfReferees
     * @param bool $validatePriority
     * @param list<Period> $blockedPeriods
     * @throws Exception
     */
    public function validate(
        RoundNumber $roundNumber,
        int $nrOfReferees,
        bool $validatePriority = true,
        array $blockedPeriods = []
    ): void {
        $this->validateEnoughTotalNrOfGames($roundNumber);
        $this->validateFields($roundNumber);
        $this->validateReferee($roundNumber, $nrOfReferees);
        if( $roundNumber->getValidPlanningConfig()->selfRefereeEnabled() ) {
            $this->validateSelfReferee($roundNumber);
        }
        if (count($blockedPeriods) > 0) {
            $this->validateGameNotInBlockedPeriod($roundNumber, $blockedPeriods);
        }
        $this->validateAllPlacesSameNrOfGames($roundNumber);
        $this->validateResourcesPerBatch($roundNumber);
        $this->validateFieldsEquallyAssigned($roundNumber);
        $this->validateRefereesEquallyAssigned($roundNumber, $nrOfReferees);
        if ($validatePriority) {
            $this->validatePriorityOfFieldsAndReferees($roundNumber);
        }
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
            $field = $game->getField();
            if ($field === null) {
                throw new Exception("there is at least one game without a field", E_ERROR);
            }
            if ($field->getCompetitionSport() !== $game->getCompetitionSport()) {
                throw new Exception("game->field->sport should be equal to game->sport", E_ERROR);
            }
        }
    }

    protected function validateReferee(RoundNumber $roundNumber, int $nrOfReferees): void
    {
        $selfReferee = $roundNumber->getValidPlanningConfig()->getSelfReferee();
        if ($selfReferee !== SelfReferee::Disabled || $nrOfReferees === 0) {
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
        if ($roundNumber->getRefereeInfo()->selfRefereeInfo->selfReferee === SelfReferee::Disabled ) {
            return;
        }

        new PlanningPouleStructure(
            $roundNumber->createPouleStructure(),
            $roundNumber->getCompetition()->createSportVariantsWithFields(),
            $roundNumber->getRefereeInfo()
        );
        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
            if ($game->getRefereePlace() === null) {
                throw new Exception("the game should have a refereeplace", E_ERROR);
            }
        }
    }

    /**
     * @param RoundNumber $roundNumber
     * @param non-empty-list<Period> $blockedPeriods
     * @throws \League\Period\Exception
     */
    protected function validateGameNotInBlockedPeriod(RoundNumber $roundNumber, array $blockedPeriods): void
    {
        $maxNrOfMinutesPerGame = $roundNumber->getValidPlanningConfig()->getMaxNrOfMinutesPerGame();
        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
            $gamePeriod = new Period(
                $game->getStartDateTime(),
                $game->getStartDateTime()->add(new \DateInterval('PT' . $maxNrOfMinutesPerGame . 'M'))
            );
            foreach ($blockedPeriods as $blockedPeriod) {
                if ($gamePeriod->overlaps($blockedPeriod)) {
                    throw new Exception("a game is during a blocked period", E_ERROR);
                }
            }
        }
    }

    protected function validateAllPlacesSameNrOfGames(RoundNumber $roundNumber): void
    {
        $variantCreaotr = new VariantCreator();
        foreach ($roundNumber->getCompetitionSports() as $competitionSport) {
            $sportVariant = $competitionSport->createVariant();
            foreach ($roundNumber->getPoules() as $poule) {
                $nrOfPlaces = count($poule->getPlaces());
                $variantWithPoule = $variantCreaotr->createWithPoule($nrOfPlaces, $sportVariant);
                if ($variantWithPoule instanceof AgainstGppWithPoule && !$variantWithPoule->allPlacesSameNrOfGamesAssignable()) {
                    continue;
                }
                if ($this->allPlacesInPouleSameNrOfGames($poule, $competitionSport) === false) {
                    throw new Exception("not all places within poule have same number of games", E_ERROR);
                }
            }
        }
    }

    protected function allPlacesInPouleSameNrOfGames(Poule $poule, CompetitionSport $competitionSport): bool
    {
        $nrOfGames = [];
        foreach ($poule->getGames($competitionSport) as $game) {
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
    protected function getPlaces(TogetherGame|AgainstGame $game): array
    {
        return array_values(
            array_map(function (AgainstGamePlace|TogetherGamePlace $gamePlace): Place {
                return $gamePlace->getPlace();
                }, $game->getPlaces()->toArray()
        ));
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

    protected function validateFieldsEquallyAssigned(RoundNumber $roundNumber): void
    {
        foreach ($roundNumber->getCompetitionSports() as $competitionSport) {
            $this->validateFieldsPerSportEquallyAssigned($roundNumber, $competitionSport);
        }
    }

    protected function validateFieldsPerSportEquallyAssigned(
        RoundNumber $roundNumber,
        CompetitionSport $competitionSport
    ): void {
        $fields = [];

        $games = array_filter(
            $roundNumber->getGames(Order::ByPoule),
            function (AgainstGame|TogetherGame $game) use ($competitionSport): bool {
                return $game->getCompetitionSport() === $competitionSport;
            }
        );

        foreach ($games as $game) {
            $field = $game->getField();
            if ($field !== null) {
                $fieldIndex = $this->getFieldIndex($field);
                if (array_key_exists($fieldIndex, $fields) === false) {
                    $fields[$fieldIndex] = 0;
                }
                $fields[$fieldIndex]++;
            }
        }

        $this->validateNrOfGamesRange($fields, "fields");
    }

    protected function validateRefereesEquallyAssigned(RoundNumber $roundNumber, int $nrOfReferees): void
    {
        $referees = [];
        $refereePlaces = [];

        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
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
        $nrOfPlaces = null;
        foreach ($roundNumber->getPoules() as $poule) {
            $nrOfPoulePlaces = $poule->getPlaces()->count();
            if ($nrOfPlaces === null) {
                $nrOfPlaces = $nrOfPoulePlaces;
                continue;
            }
            if ($nrOfPlaces !== $nrOfPoulePlaces) {
                return false;
            }
        }
        return true;
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

    protected function getFieldIndex(Field $field): string|int
    {
        $fieldId = $field->getId();
        if ($fieldId !== null) {
            return $fieldId;
        }
        $sportVariant = $field->getCompetitionSport()->createVariant();
        return $field->getCompetitionSport()->getSport()->getName() . '-' . $sportVariant . '-' . $field->getPriority();
    }
}
