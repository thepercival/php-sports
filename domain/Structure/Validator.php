<?php

declare(strict_types=1);

namespace Sports\Structure;

use Sports\Category;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Qualify\Rule\Horizontal\Single as HorizontalSingle;
use Sports\Qualify\Rule\Horizontal\Multiple as HorizontalMultiple;
use Sports\Qualify\Rule\Vertical\Single as VerticalSingle;
use Sports\Qualify\Rule\Vertical\Multiple as VerticalMultiple;
use Sports\Qualify\QualifyTarget;
use Exception;
use Sports\Structure\NameService as StructureNameService;
use Sports\Place;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Poule;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Competition;
use Sports\Structure;
use SportsHelpers\PlaceRanges;

final class Validator
{
    protected StructureNameService $structureNameService;

    public function __construct()
    {
        $this->structureNameService = new StructureNameService();
    }

    public function checkValidity(Competition $competition, Structure|null $structure = null, PlaceRanges|null $placeRanges = null): void
    {
        $prefix = "de structuur(competition:" . $competition->getName() . ")";

        if (!($structure instanceof Structure)) {
            throw new \Exception($prefix . " heeft geen rondenummers", E_ERROR);
        }

        $this->checkCategoryValidity($structure->getCategories());

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $this->checkRoundNumberValidity($firstRoundNumber, $competition);
        foreach ($firstRoundNumber->getRounds() as $round) {
            $this->checkRoundValidity($round, $placeRanges);
        }
    }

    /**
     * @param list<Category> $categories
     * @throws Exception
     */
    public function checkCategoryValidity(array $categories): void
    {
        if (count($categories) === 0) {
            throw new Exception('de structuur bevat geen categorien', E_ERROR);
        }

        $categoryNr = 1;
        foreach ($categories as $category) {
            if ($category->getNumber() !== $categoryNr++) {
                throw new Exception('de categorie-nummers zijn niet opvolgend', E_ERROR);
            }

            // throws if null
            $category->getRootRound();
        }
    }

    public function checkRoundNumberValidity(
        RoundNumber $roundNumber,
        Competition $competition
    ): void {
        $prefix = "rondenummer " . $roundNumber->getNumber() . $this->getIdOutput($roundNumber->getId());
        if (count($roundNumber->getRounds()) === 0) {
            throw new Exception($prefix . " bevat geen ronden", E_ERROR);
        }

        if ($roundNumber->isFirst()) {
            foreach ($competition->getSports() as $competitionSport) {
                if ($roundNumber->getGameAmountConfig($competitionSport) === null) {
                    throw new Exception($prefix . " bevat geen geldige wedstrijd-aantal-config", E_ERROR);
                }
            }
        }
        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null) {
            $this->checkRoundNumberValidity($nextRoundNumber, $competition);
        }
    }

    protected function getIdOutput(int|null|string $id = null): string
    {
        return $id !== null ? " (" . $id . ")" : '';
    }

    public function checkRoundValidity(Round $round, PlaceRanges|null $placeRanges): void
    {
        $prefix = "ronde " . $this->getIdOutput($round->getId());
        if ($round->getPoules()->count() === 0) {
            throw new Exception($prefix . " bevat geen poules", E_ERROR);
        }

        $this->checkPoulesNumberGap(array_values($round->getPoules()->toArray()));
        foreach ($round->getPoules() as $poule) {
            $this->checkPouleValidity($poule);
        }
        $this->checkRoundNrOfPlaces($round);

        if (!$round->getNumber()->hasNext() && $round->getQualifyGroups()->count() > 0) {
            throw new Exception(
                $prefix . " heeft geen volgende ronde, maar wel kwalificatiegroepen",
                E_ERROR
            );
        }

        if ($round->isRoot()) {
            foreach ($round->getNumber()->getCompetitionSports() as $competitionSport) {
                if ($round->getScoreConfig($competitionSport) === null) {
                    throw new Exception($prefix . " bevat geen geldige scoreConfig", E_ERROR);
                }
                if ($round->getAgainstQualifyConfig($competitionSport) === null) {
                    throw new Exception($prefix . " bevat geen geldige puntenconfig", E_ERROR);
                }
            }
        }

        if ($placeRanges !== null) {
            $placeRanges->validateStructure($round->createPouleStructure());
        }

        foreach ([QualifyTarget::Winners, QualifyTarget::Losers] as $target) {
            $qualifyGroups = array_values($round->getTargetQualifyGroups($target)->toArray());
            $this->checkQualifyGroupsNumberGap($qualifyGroups);
        }
        $this->checkHorizontalPouleOneTimeUse($round);

        foreach ($round->getQualifyGroups() as $qualifyGroup) {
            $this->checkRoundValidity($qualifyGroup->getChildRound(), $placeRanges);
        }
    }

    /**
     * @param list<Poule> $poules
     * @throws Exception
     */
    public function checkPoulesNumberGap(array $poules): void
    {
        $startNumber = 1;
        foreach ($poules as $poule) {
            if ($poule->getNumber() !== $startNumber++) {
                throw new Exception("het nummer van de poule is onjuist", E_ERROR);
            }
        }
    }

    /**
     * @param list<QualifyGroup> $qualifyGroups
     * @throws Exception
     */
    public function checkQualifyGroupsNumberGap(array $qualifyGroups): void
    {
        $startNumber = 1;
        foreach ($qualifyGroups as $qualifyGroup) {
            if ($qualifyGroup->getNumber() !== $startNumber++) {
                throw new Exception("het nummer van de kwalificatiegroep is onjuist", E_ERROR);
            }
        }
    }

    public function checkHorizontalPouleOneTimeUse(Round $round): void {
        $horizontalPoules = $this->getQualifyingHorizontalPoules($round);
        foreach( $horizontalPoules as $horizontalPoule ) {
            if( count( array_filter( $horizontalPoules, function( HorizontalPoule $horizontalPoule2) use($horizontalPoule) : bool {
                    return $horizontalPoule === $horizontalPoule2;
                }) ) > 1 ) {
                throw new Exception("er is een horizontale poule die meerdere malen wordt gebruikt", E_ERROR);
            }
        }
    }

    /**
     * @param Round $round
     * @return list<HorizontalPoule>
     * @throws Exception
     */
    private function getQualifyingHorizontalPoules(Round $round): array
    {
        return array_map( function(HorizontalSingle|HorizontalMultiple|VerticalSingle|VerticalMultiple $qualifyRule): HorizontalPoule {
            return $qualifyRule->getFromHorizontalPoule();
        }, $this->getQualifyRules($round) );
    }

    /**
     * @param Round $round
     * @return list<HorizontalSingle|HorizontalMultiple|VerticalSingle|VerticalMultiple>
     * @throws Exception
     */
    private function getQualifyRules(Round $round): array
    {
        $qualifyRules = [];
        foreach( $round->getQualifyGroups() as $qualifyGroup) {
            $qualifyRules = array_merge($qualifyRules, $this->convertToQualifyRulesList($qualifyGroup));
        }
        return $qualifyRules;
    }

    /**
     * @param QualifyGroup $qualifyGroup
     * @return list<HorizontalSingle|HorizontalMultiple|VerticalSingle|VerticalMultiple>
     * @throws Exception
     */
    private function convertToQualifyRulesList(QualifyGroup $qualifyGroup): array
    {
        $qualifyRules = [];
        $singleRule = $qualifyGroup->getFirstSingleRule();
        while ($singleRule !== null) {
            $qualifyRules[] = $singleRule;
            $singleRule = $singleRule->getNext();
        }
        $multipleRule = $qualifyGroup->getMultipleRule();
        if( $multipleRule !== null ) {
            $qualifyRules[] = $multipleRule;
        }
        return $qualifyRules;
    }

    protected function checkRoundNrOfPlaces(Round $round): void
    {
        /** @var int|null $minNrOfPlaces */
        $minNrOfPlaces = null;
        /** @var int|null $maxNrOfPlaces */
        $maxNrOfPlaces = null;
        foreach ($round->getPoules() as $poule) {
            if ($minNrOfPlaces === null || $poule->getPlaces()->count() < $minNrOfPlaces) {
                $minNrOfPlaces = $poule->getPlaces()->count();
            }
            if ($maxNrOfPlaces === null || $poule->getPlaces()->count() > $maxNrOfPlaces) {
                $maxNrOfPlaces = $poule->getPlaces()->count();
            }
        }
        if ($minNrOfPlaces !== null && $maxNrOfPlaces !== null && $maxNrOfPlaces - $minNrOfPlaces > 1) {
            throw new Exception(
                "bij ronde " . $this->getIdOutput($round->getId()) . " zijn er poules met meer dan 1 plaats verschil",
                E_ERROR
            );
        }
    }

    public function checkPouleValidity(Poule $poule): void
    {
        $this->checkPlacesNumberGap(array_values($poule->getPlaces()->toArray()));
        if ($poule->getPlaces()->count() === 0) {
            $prefix = "poule " . $this->getIdOutput($poule->getId()) . "(" . $this->structureNameService->getPouleName(
                    $poule,
                    false
                ) . ", rondenummer: " . $poule->getRound()->getNumberAsValue() . " )";
            throw new Exception($prefix . " bevat geen plekken", E_ERROR);
        }
    }

    /**
     * @param list<Place> $places
     * @throws Exception
     */
    public function checkPlacesNumberGap(array $places): void
    {
        $startNumber = 1;

        uasort($places, function (Place $placeA, Place $placeB): int {
            return $placeA->getPlaceNr() < $placeB->getPlaceNr() ? -1 : 1;
        });

        foreach ($places as $place) {
            if ($place->getPlaceNr() !== $startNumber++) {
                throw new Exception("het nummer van de plek in de poule is onjuist", E_ERROR);
            }
        }
    }
}
