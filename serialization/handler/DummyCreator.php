<?php

declare(strict_types=1);

namespace Sports\SerializationHandler;

use League\Period\Period;
use Sports\Association;
use Sports\Category;
use Sports\Competition;
use Sports\Competition\Field;
use Sports\Competition\Referee;
use Sports\Competition\Sport as CompetitionSport;
use Sports\League;
use Sports\Place;
use Sports\Poule;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Ranking\PointsCalculation;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Season;
use Sports\Sport;
use Sports\Structure\Cell as StructureCell;
use Sports\Structure\Locations\StructureLocationPlace;
use Sports\Structure\PathNode;
use SportsHelpers\GameMode;
use SportsHelpers\Sport\PersistVariant as PersistSportVariant;

final class DummyCreator
{
    private Competition|null $competition = null;
    /**
     * @var array<string|int, CompetitionSport>
     */
    private array $competitionSports  = [];
    /**
     * @var array<string|int, Referee>
     */
    private array $referees  = [];
    /**
     * @var array<string|int, Field>
     */
    private array $fields  = [];

    public function createCompetition(): Competition
    {
        if ($this->competition === null) {
            $association = new Association("knvb");
            $league = new League($association, "my league");
            $season = new Season("123", Period::fromDate("2018-12-17T11:33:15.710Z", "2018-12-17T11:33:15.710Z"));
            $this->competition = new Competition($league, $season);
            $this->competition->setStartDateTime(new \DateTimeImmutable("2018-12-17T12:00:00.000Z"));
        }
        return $this->competition;
    }

    public function createCompetitionSport(Competition $competition, int $competitionSportId): CompetitionSport
    {
        if (array_key_exists($competitionSportId, $this->competitionSports)) {
            return $this->competitionSports[$competitionSportId];
        }
        $getCompetitionSport = function (int $competitionSportId) use ($competition): ?CompetitionSport {
            foreach ($competition->getSports() as $competitionSport) {
                if ($competitionSport->getId() == $competitionSportId) {
                    return $competitionSport;
                }
            }
            return null;
        };
        $competitionSport = $getCompetitionSport($competitionSportId);
        if ($competitionSport === null) {
            $defaultNrOfSidePlaces = 1;
            $sport = new Sport(
                'dummy',
                true,
                GameMode::Against,
                $defaultNrOfSidePlaces * 2
            );
            $sport->setId($competitionSportId);
            /** @psalm-suppress RedundantCondition, TypeDoesNotContainType */
            $competitionSport = new CompetitionSport(
                $sport,
                $competition,
                PointsCalculation::AgainstGamePoints,
                3, 1, 2, 1, 0,
                new PersistSportVariant(
                    $sport->getDefaultGameMode(),
                    $defaultNrOfSidePlaces,
                    $defaultNrOfSidePlaces,
                    0,
                    $defaultNrOfSidePlaces <= 2 ? 1 : 0,
                    $defaultNrOfSidePlaces > 2 ? 1 : 0
                )
            );
            $competitionSport->setId($competitionSportId);
            $this->competitionSports[$competitionSportId] = $competitionSport;
        }
        return $competitionSport;
    }

    public function createCategoryIfNotExists(int $categoryNr): Category {
        $competition = $this->createCompetition();
        foreach( $competition->getCategories() as $category) {
            if( $category->getNumber() === $categoryNr) {
                return $category;
            }
        }

        $category = new Category($competition, Category::DEFAULTNAME . (count($competition->getCategories()) + 1) );
        while( $category->getNumber() < $categoryNr ) {
            $category = new Category($competition, Category::DEFAULTNAME . count($competition->getCategories()) );
        }
        return $category;
    }

    public function createRootRoundIfNotExists(int $categoryNr): Round {

        $category = $this->createCategoryIfNotExists($categoryNr);
        $structureCells = $category->getStructureCells();
        if( count($structureCells) === 0 ) {
            $structureCell = new StructureCell($category, $this->createFirstRoundNumberIfNotExists() );
        } else {
            $structureCell = $category->getFirstStructureCell();
        }

        $rounds = $structureCell->getRounds();
        if( count($rounds) === 0 ) {
            $rootRound = new Round($structureCell);
        } else {
            $rootRound = $structureCell->getRounds()->first();
            if( $rootRound === false) {
                throw new \Exception('rootRound should be available');
            }
        }
        return $rootRound;
    }

    public function createReferee(int $refereeId, Competition $competition): Referee
    {
        if (array_key_exists($refereeId, $this->referees)) {
            return $this->referees[$refereeId];
        }
        $getReferee = function (int $refereeId) use ($competition): ?Referee {
            foreach ($competition->getReferees() as $referee) {
                if ($referee->getId() == $refereeId) {
                    return $referee;
                }
            }
            return null;
        };
        $referee = $getReferee($refereeId);
        if ($referee === null) {
            $referee = new Referee(
                $competition,
                'DUM'
            );
            $referee->setId($refereeId);
            $this->referees[$refereeId] = $referee;
        }
        return $referee;
    }

    public function createFirstRoundNumberIfNotExists(): RoundNumber
    {
        $competition = $this->createCompetition();
        if( count($competition->getRoundNumbers()) === 0 ) {
            $competition->getRoundNumbers()->add(new RoundNumber($competition));
        }
        $firstRoundNumber = $competition->getRoundNumbers()->first();
        if( $firstRoundNumber === false) {
            throw new \Exception('roundNumber should be available');
        }

        return $firstRoundNumber;
    }

//    public function createPoule(int $nrOfPlaces): Poule
//    {
//        $competition = $this->createCompetition();
//        $category = new Category($competition, Category::DEFAULTNAME);
//        $structureCell = new StructureCell($category, $this->createRoundNumber() );
//        $round = new Round($structureCell);
//        $poule = new Poule( $round );
//        for( $placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++ ) {
//            new Place($poule);
//        }
//        return $poule;
//    }

    public function createField(string|int $fieldId, CompetitionSport $competitionSport): Field {
        if (array_key_exists($fieldId, $this->fields)) {
            return $this->fields[$fieldId];
        }
        $getField = function (int|string $fieldId) use ($competitionSport): ?Field {
            foreach ($competitionSport->getFields() as $field) {
                if ($field->getId() == $fieldId) {
                    return $field;
                }
            }
            return null;
        };
        $field = $getField($fieldId);
        if ($field === null) {
            $field = new Field($competitionSport);
            $field->setId($fieldId);
            $this->fields[$fieldId] = $field;
        }
        return $field;
    }

    public function createRefereePlace(StructureLocationPlace $structureLocation): Place {

        $rootRound = $this->createRootRoundIfNotExists($structureLocation->getCategoryNr());
        $pathNode = $structureLocation->getPathNode();
        $placeLocation = $structureLocation->getPlaceLocation();
        return $this->createFromRootRoundToPlaceIfNotExists($rootRound, $pathNode, $placeLocation);
    }

    private function createFromRootRoundToPlaceIfNotExists(
        Round $rootRound, PathNode $pathNode, Place\Location $placeLocation): Place
    {
        $round = $this->createRoundsIfNotExists($rootRound, $pathNode->getRoot() );
        return $this->createPoulesAndPlacesIfNotExists($round, $placeLocation );
    }

    private function createRoundsIfNotExists(Round $round, PathNode $pathNode): Round
    {
        $nextPathNode = $pathNode->getNext();
        if( $nextPathNode === null ) {
            return $round;
        }
        $nextQualifyTarget = $nextPathNode->getQualifyTarget();
        if( $nextQualifyTarget === null ) {
            throw new \Exception('qualifygroup has incorrect target');
        }
        $borderQualifyGroup = $round->getBorderQualifyGroup($nextQualifyTarget);

        $qualifyGroup = null;
        if( $borderQualifyGroup !== null && $borderQualifyGroup->getNumber() >= $nextPathNode->getQualifyGroupNumber() ) {
            $qualifyGroup = $round->getQualifyGroup($nextQualifyTarget, $nextPathNode->getQualifyGroupNumber() );
            if( $qualifyGroup === null ) {
                throw new \Exception('qualifygroup has incorrect number');
            }
            return $qualifyGroup->getChildRound();
        }
        $structureCell = $round->getStructureCell();
        $nextStructureCell = $structureCell->getNext();
        if( $nextStructureCell === null ) {
            $nextStructureCell = $structureCell->createNext();
        }

        $qualifyGroupNr = $borderQualifyGroup?->getNumber() ?? 0;
        while ( $qualifyGroupNr < $nextPathNode->getQualifyGroupNumber() ) {
            $qualifyGroup = new QualifyGroup($round, $nextQualifyTarget, $nextStructureCell);
            $qualifyGroupNr = $qualifyGroup->getNumber();
        }
        if( $qualifyGroup === null ) {
            throw new \Exception('qualifygroup has incorrect number');
        }

        return $this->createRoundsIfNotExists($qualifyGroup->getChildRound(), $nextPathNode);
    }

    private function createPoulesAndPlacesIfNotExists(Round $round, Place\Location $placeLocation ): Place {
        $poule = $this->createPoulesIfNotExists($round, $placeLocation->getPouleNr());
        return $this->createPlacesIfNotExists($poule, $placeLocation->getPlaceNr());
    }

    private function createPoulesIfNotExists(Round $round, int $pouleNr ): Poule {
        $nrOfRoundPoules = count($round->getPoules());
        if( $pouleNr <= $nrOfRoundPoules ) {
            return $round->getPoule($pouleNr);
        }
        $poule = new Poule($round);
        while ( $poule->getNumber() < $pouleNr ) {
            $poule = new Poule($round);
        }
        return $poule;
    }

    private function createPlacesIfNotExists(Poule $poule, int $placeNr ): Place {
        $nrOfPoulePlaces = count($poule->getPlaces());
        if( $placeNr <= $nrOfPoulePlaces ) {
            return $poule->getPlace($placeNr);
        }
        $place = new Place($poule);
        while ( $place->getPlaceNr() < $placeNr ) {
            $place = new Place($poule);
        }
        return $place;
    }


}
