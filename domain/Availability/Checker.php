<?php

declare(strict_types=1);

namespace Sports\Availability;

use Exception;
use Sports\Competition;
use Sports\Competition\Field;
use Sports\Competition\Referee;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Competitor;
use Sports\Place\LocationInterface as PlaceLocationInterface;
use Sports\Priority\Prioritizable;

class Checker
{
    public function checkRefereeInitials(Competition $competition, string $initials, Referee $refereeToCheck = null): void
    {
        $nonUniqueReferees = $competition->getReferees()->filter(
            function (Referee $refereeIt) use ($initials, $refereeToCheck): bool {
                return $refereeIt->getInitials() === $initials && $refereeToCheck !== $refereeIt;
            }
        );
        if (!$nonUniqueReferees->isEmpty()) {
            throw new Exception(
                "de scheidsrechter met de initialen " . $initials . " bestaat al",
                E_ERROR
            );
        }
    }

    public function checkFieldName(Competition $competition, string $name, Field $fieldToCheck = null): void
    {
        $nonUniqueFields = array_filter(
            $competition->getFields(),
            function (Field $fieldIt) use ($name, $fieldToCheck): bool {
                return $fieldIt->getName() === $name && $fieldToCheck !== $fieldIt;
            }
        );
        if (count($nonUniqueFields) > 0) {
            throw new Exception(
                "het veld met de naam " . $name . " bestaat al",
                E_ERROR
            );
        }
    }

    /**
     * @return void
     */
    public function checkRefereeEmailaddress(
        Competition $competition,
        string $emailaddress = null,
        Referee $refereeToCheck = null
    ) {
        if ($emailaddress === null) {
            return;
        }
        $nonUniqueReferees = $competition->getReferees()->filter(
            function (Referee $refereeIt) use ($emailaddress, $refereeToCheck): bool {
                return $refereeIt->getEmailaddress() === $emailaddress && $refereeToCheck !== $refereeIt;
            }
        );
        if (!$nonUniqueReferees->isEmpty()) {
            throw new Exception(
                "de scheidsrechter met het emailadres " . $emailaddress . " bestaat al",
                E_ERROR
            );
        }
    }

    public function checkRefereePriority(Competition $competition, int $priority, Referee $referee = null): void
    {
        $referees = array_values($competition->getReferees()->toArray());
        $this->checkPriority($referees, $priority, $referee);
    }

    public function checkFieldPriority(CompetitionSport $competitionSport, int $priority, Field $field = null): void
    {
        $fields = array_values($competitionSport->getFields()->toArray());
        $this->checkPriority($fields, $priority, $field);
    }

    /**
     * @param list<Prioritizable> $prioritizables
     * @param int $priority
     * @param Prioritizable|null $objectToCheck
     * @throws Exception
     * @return void
     */
    protected function checkPriority(array $prioritizables, int $priority, Prioritizable $objectToCheck = null): void
    {
        $nonUniqueObjects = array_filter(
            $prioritizables,
            function (Prioritizable $prioritizableIt) use ($priority, $objectToCheck): bool {
                return $prioritizableIt->getPriority() === $priority && $objectToCheck !== $prioritizableIt;
            }
        );
        if (count($nonUniqueObjects) > 0) {
            throw new Exception(
                "de prioriteit " . $priority . " bestaat al",
                E_ERROR
            );
        }
    }

    /**
     * @param list<Competitor> $competitors
     * @param string $name
     * @param Competitor|null $competitorToCheck
     * @throws Exception
     * @return void
     */
    public function checkCompetitorName(array $competitors, string $name, Competitor $competitorToCheck = null): void
    {
        $nonUniqueFields = array_filter(
            $competitors,
            function (Competitor $competitorIt) use ($name, $competitorToCheck): bool {
                return $competitorIt->getName() === $name && $competitorToCheck !== $competitorIt;
            }
        );
        if (count($nonUniqueFields) > 0) {
            throw new Exception(
                "de deelnemer met de naam " . $name . " bestaat al",
                E_ERROR
            );
        }
    }

    /**
     * @param list<Competitor> $competitors
     * @param PlaceLocationInterface $placeLocation
     * @param Competitor|null $competitorToCheck
     * @throws Exception
     * @return void
     */
    public function checkCompetitorPlaceLocation(array $competitors, PlaceLocationInterface $placeLocation, Competitor $competitorToCheck = null): void
    {
        $nonUniqueFields = array_filter(
            $competitors,
            function (Competitor $competitorIt) use ($placeLocation, $competitorToCheck): bool {
                return $competitorIt->getPouleNr() === $placeLocation->getPouleNr()
                    && $competitorIt->getPlaceNr() === $placeLocation->getPlaceNr()
                    && $competitorToCheck !== $competitorIt;
            }
        );
        if (count($nonUniqueFields) > 0) {
            throw new Exception(
                "er bestaat al een deelnemer op deze plek",
                E_ERROR
            );
        }
    }
}
