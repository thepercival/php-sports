<?php

declare(strict_types=1);

namespace Sports\SerializationHandler;

use Sports\Association;
use Sports\Category;
use Sports\Competition;
use Sports\Competition\Referee;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Competition\Field;
use Sports\League;
use Sports\Place;
use Sports\Poule;
use Sports\Ranking\PointsCalculation;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Season;
use Sports\Sport;
use Sports\Structure\Cell as StructureCell;
use SportsHelpers\GameMode;
use SportsHelpers\Sport\PersistVariant as PersistSportVariant;

class DummyCreator
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
            $season = new Season("123", new \League\Period\Period("2018-12-17T11:33:15.710Z", "2018-12-17T11:33:15.710Z"));
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

    public function createRoundNumber(): RoundNumber
    {
        return new RoundNumber($this->createCompetition());
    }

    public function createPoule(int $nrOfPlaces): Poule
    {
        $competition = $this->createCompetition();
        $category = new Category($competition, Category::DEFAULTNAME);
        $structureCell = new StructureCell($category, $this->createRoundNumber() );
        $round = new Round($structureCell);
        $poule = new Poule( $round );
        for( $placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++ ) {
            new Place($poule);
        }
        return $poule;
    }

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

    public function createRefereePlace(string $refereeStructureLocation, Round $round): Place {
        $parts = explode('.', $refereeStructureLocation );
        if( count($parts) !== 3 ) {
            throw new \Exception('incorrect structurelocation');
        }
        $poule = $this->createPouleFromStructureLocation((int)$parts[1], $round);

        $placeNr = (int)$parts[2];
        $place = null;
        while ( $place === null && $placeNr > 0) {
            try {
                $place = $poule->getPlace($placeNr);
                return $place;
            } catch(\Exception $e) {
                new Place($poule);
            }
        }
        throw new \Exception('place cannot be null');
    }

    private function createPouleFromStructureLocation(int $pouleNr, Round $round): Poule {

        $poule = null;
        while ( $poule === null && $pouleNr > 0) {
            try {
                $poule = $round->getPoule($pouleNr);
                return $poule;
            } catch(\Exception $e) {
                new Poule($round);
            }
        }
        throw new \Exception('poule cannot be null');
    }
}
