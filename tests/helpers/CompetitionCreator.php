<?php

declare(strict_types=1);

namespace Sports\TestHelper;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Association;
use Sports\Competition;
use Sports\Competition\Field;
use Sports\Competition\Referee;
use Sports\Competition\Sport as CompetitionSport;
use Sports\League;
use Sports\Season;
use Sports\Sport;
use Sports\Sport\Custom as SportCustom;
use SportsHelpers\GameMode;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;

trait CompetitionCreator
{
    /**
     * @var Competition|null
     */
    protected $competition;

    protected function createCompetition(
        SportVariantWithFields|null $sportVariantWithFields = null
    ): Competition {
        if ($this->competition !== null) {
            return $this->competition;
        }

        $league = new League(new Association("knvb"), "my league");
        $season = new Season("2018/2019", new Period(
            new DateTimeImmutable("2018-08-01"),
            new DateTimeImmutable("2019-07-01"),
        ));
        $competition = new Competition($league, $season);
        $competition->setId(0);
        $competition->setStartDateTime(new DateTimeImmutable("2030-01-01T12:00:00.000Z"));
        new Referee($competition, '111');
        new Referee($competition, '222');

        $this->createCompetitionSport($competition, $sportVariantWithFields);
        $this->competition = $competition;
        return $competition;
    }

    /*protected function createSportVariant(): Sport
    {
        if ($this->sport !== null) {
            return $this->sport;
        }

        $this->sport = new Sport("voetbal", true, 2, GameMode::Against);
        $this->sport->setCustomId(SportCustom::Football);
        return $this->sport;
    }*/

    protected function createCompetitionSport(
        Competition $competition,
        SportVariantWithFields|null $sportVariantWithFields
    ): void {
        $sport = new Sport("voetbal", true, GameMode::Against, 1);
        $sport->setCustomId(SportCustom::Football);

        if ($sportVariantWithFields === null) {
            $sportVariantWithFields = new SportVariantWithFields(
                new AgainstSportVariant(1, 1, 1, 0),
                2
            );
        }
        $persistVariant = $sportVariantWithFields->getSportVariant()->toPersistVariant();
        $competitionSport = new CompetitionSport($sport, $competition, $persistVariant);
        for ($fieldNr = 1; $fieldNr <= $sportVariantWithFields->getNrOfFields(); $fieldNr++) {
            $field = new Field($competitionSport);
            $field->setName((string)$fieldNr);
        }
    }

    protected function getAgainstSportVariantWithFields(
        int $nrOfFields,
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfH2H = 1,
        int $nrOfGamesPerPlace = 0
    ): SportVariantWithFields {
        return new SportVariantWithFields(
            $this->getAgainstSportVariant($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfH2H, $nrOfGamesPerPlace),
            $nrOfFields
        );
    }

    protected function getAgainstSportVariant(
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfH2H = 1,
        int $nrOfGamesPerPlace = 0
    ): AgainstSportVariant {
        return new AgainstSportVariant($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfH2H, $nrOfGamesPerPlace);
    }
}
