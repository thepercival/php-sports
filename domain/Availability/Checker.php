<?php

declare(strict_types=1);

namespace Sports\Availability;

use Exception;
use Sports\Category;
use Sports\Competition;
use Sports\Competition\Field;
use Sports\Competition\Referee;
use Sports\Competition\CompetitionSport as CompetitionSport;
use Sports\Competitor;
use Sports\Competitor\StartLocation;
use Sports\Competitor\StartLocationMap;
use SportsHelpers\PlaceLocationInterface;
use Sports\Priority\Prioritizable;

final class Checker
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
     * @param int $categoryNr ,
     * @param list<Competitor> $competitors
     * @param string $name
     * @param Competitor|null $competitorToCheck
     * @return void
     * @throws Exception
     */
    public function checkCompetitorName(
        int $categoryNr,
        array $competitors,
        string $name,
        Competitor $competitorToCheck = null
    ): void {
        $nonUniqueFields = array_filter(
            $competitors,
            function (Competitor $competitorIt) use ($categoryNr, $name, $competitorToCheck): bool {
                return $categoryNr === $competitorIt->getCategoryNr()
                    && $competitorIt->getName() === $name
                    && $competitorToCheck !== $competitorIt;
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
     * @param Category $category
     * @param list<Competitor> $competitors
     * @return StartLocation
     * @throws \Sports\Exceptions\StructureNotFoundException
     */
    public function getFirstAvailableStartLocation(Category $category, array $competitors): StartLocation {
        $startLocationMap = new StartLocationMap($competitors);
        foreach( $category->getRootRound()->getPlaces() as $place ) {

            $startLocation = $place->getStartLocation();
            if( $startLocation === null) {
                throw new \Exception('er is geen plaats meer beschikbaar', E_ERROR);;
            }
            if( $startLocationMap->getCompetitor($startLocation ) === null) {
                return $startLocation;
            }
        }
        throw new \Exception('er is geen plaats meer beschikbaar', E_ERROR);;
    }

    /**
     * @param list<Competitor> $competitors
     * @param StartLocation $startLocation
     * @param Competitor|null $competitorToCheck
     * @throws Exception
     * @return void
     */    public function checkCompetitorStartLocation(
        array $competitors,
        StartLocation $startLocation,
        Competitor $competitorToCheck = null
    ): void {
        $nonUniqueFields = array_filter(
            $competitors,
            function (Competitor $competitorIt) use ($startLocation, $competitorToCheck): bool {
                return $competitorIt->getCategoryNr() === $startLocation->getCategoryNr()
                    && $competitorIt->getPouleNr() === $startLocation->getPouleNr()
                    && $competitorIt->getPlaceNr() === $startLocation->getPlaceNr()
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
