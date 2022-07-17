<?php

declare(strict_types=1);

namespace Sports\TestHelper;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Association;
use Sports\Category;
use Sports\Competition;
use Sports\Competition\Field;
use Sports\Competition\Referee;
use Sports\Competition\Sport as CompetitionSport;
use Sports\League;
use Sports\Ranking\PointsCalculation;
use Sports\Season;
use Sports\Sport;
use Sports\Sport\Custom as CustomSport;
use Sports\Structure;
use SportsHelpers\GameMode;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;

trait CompetitionCreator
{
    /**
     * @var Competition|null
     */
    protected $competition;

    /**
     * @param non-empty-list<SportVariantWithFields>|null $sportVariantsWithFields
     * @return Competition
     * @throws \League\Period\Exception
     */
    protected function createCompetition(
        array|null $sportVariantsWithFields = null
    ): Competition {
        if ($this->competition !== null) {
            return $this->competition;
        }

        $league = new League(new Association('knvb'), 'my league');
        $season = new Season(
            '2018/2019',
            new Period(
                new DateTimeImmutable('2018-08-01'),
                new DateTimeImmutable('2019-07-01'),
            )
        );
        $competition = new Competition($league, $season);
        $competition->setId(0);
        $competition->setStartDateTime(new DateTimeImmutable('2030-01-01T12:00:00.000Z'));
        new Referee($competition, '111');
        new Referee($competition, '222');

        if ($sportVariantsWithFields === null) {
            $sportVariantsWithFields = [
                new SportVariantWithFields(
                    new AgainstH2h(1, 1, 1),
                    2
                )
            ];
        }
        $this->createCompetitionSports($competition, $sportVariantsWithFields);
        $this->competition = $competition;
        return $competition;
    }

    /*protected function createSportVariant(): Sport
    {
        if ($this->sport !== null) {
            return $this->sport;
        }

        $this->sport = new Sport('voetbal', true, 2, GameMode::Against);
        $this->sport->setCustomId(CustomSport::Football);
        return $this->sport;
    }*/

    /**
     * @param Competition $competition
     * @param non-empty-list<SportVariantWithFields> $sportVariantsWithFields
     */
    protected function createCompetitionSports(Competition $competition, array $sportVariantsWithFields): void
    {
        $counter = 0;
        foreach ($sportVariantsWithFields as $sportVariantWithFields) {
            if (++$counter === 1) {
                $sport = new Sport(
                    'voetbal',
                    true,
                    GameMode::Against,
                    1
                );
                $sport->setCustomId(CustomSport::Football);
            } else {
                $sport = new Sport(
                    'sport' . $counter,
                    true,
                    GameMode::Against,
                    1
                );
            }
            if (count($sportVariantsWithFields) === 1) {
                $sportAbbreviation = '';
            } else {
                $sportAbbreviation = mb_substr($sport->getName(), 0, 1);
            }

            $persistVariant = $sportVariantWithFields->getSportVariant()->toPersistVariant();
            $competitionSport = new CompetitionSport($sport, $competition, PointsCalculation::AgainstGamePoints, $persistVariant);
            for ($fieldNr = 1; $fieldNr <= $sportVariantWithFields->getNrOfFields(); $fieldNr++) {
                $field = new Field($competitionSport);
                $field->setName($sportAbbreviation . '' . $fieldNr);
            }
        }
    }

    protected function getAgainstH2hSportVariantWithFields(
        int $nrOfFields,
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfH2H = 1
    ): SportVariantWithFields {
        return new SportVariantWithFields(
            $this->getAgainstH2hSportVariant($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfH2H),
            $nrOfFields
        );
    }

    protected function getAgainstH2hSportVariant(
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfH2H = 1
    ): AgainstH2h {
        return new AgainstH2h($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfH2H);
    }

    protected function getAgainstGppSportVariantWithFields(
        int $nrOfFields,
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfGamesPerPlace = 1
    ): SportVariantWithFields {
        return new SportVariantWithFields(
            $this->getAgainstGppSportVariant($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfGamesPerPlace),
            $nrOfFields
        );
    }

    protected function getAgainstGppSportVariant(
        int $nrOfHomePlaces = 1,
        int $nrOfAwayPlaces = 1,
        int $nrOfGamesPerPlace = 1
    ): AgainstGpp {
        return new AgainstGpp($nrOfHomePlaces, $nrOfAwayPlaces, $nrOfGamesPerPlace);
    }
}
