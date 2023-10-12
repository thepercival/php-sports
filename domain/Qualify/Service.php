<?php

declare(strict_types=1);

namespace Sports\Qualify;

use Sports\Game\State as GameState;
use Sports\Place;
use Sports\Poule;
use Sports\Qualify\PlaceMapping as QualifyPlaceMapping;
use Sports\Qualify\ReservationService as QualifyReservationService;
use Sports\Qualify\Rule\Horizontal\Multiple as HorizontalMultipleQualifyRule;
use Sports\Qualify\Rule\Horizontal\Single as HorizontalSingleQualifyRule;
use Sports\Qualify\Rule\Vertical\Multiple as VerticalMultipleQualifyRule;
use Sports\Qualify\Rule\Vertical\Single as VerticalSingleQualifyRule;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Ranking\Calculator\Round as RoundRankingCalculator;
use Sports\Round;

class Service
{
    private RoundRankingCalculator $rankingCalculator;
    /**
     * @var array<int|string,bool>
     */
    private array $finishedPouleMap = [];
    private bool|null $roundFinished = null;

    public function __construct(private Round $round)
    {
        $this->rankingCalculator = new RoundRankingCalculator();
    }

    /**
     * @param Poule|null $filterPoule
     * @return list<Place>
     */
    public function resetQualifiers(Poule $filterPoule = null): array
    {
        /** @var list<Place> $changedPlaces */
        $changedPlaces = [];
        $resetQualifiersForSingleRule = function (HorizontalSingleQualifyRule | VerticalSingleQualifyRule $singleQualifyRule) use ($filterPoule, &$changedPlaces): void {
            foreach ($singleQualifyRule->getMappings() as $qualifyPlaceMapping) {
                $fromPlace = $qualifyPlaceMapping->getFromPlace();
                if ($filterPoule !== null && $fromPlace->getPoule() !== $filterPoule) {
                    continue;
                }
                $qualifyPlaceMapping->getToPlace()->setQualifiedPlace(null);
                $qualifyPlaceMapping->getToPlace()->setExtraPoints(0);
                /** @var list<Place> $changedPlaces */
                array_push($changedPlaces, $qualifyPlaceMapping->getToPlace());
            }
        };
        foreach ($this->round->getQualifyGroups() as $qualifyGroup) {

//            if( $qualifyGroup->getDistribution() === Distribution::HorizontalSnake) {
                $singleRule = $qualifyGroup->getFirstSingleRule();
                while ($singleRule !== null) {
                    $resetQualifiersForSingleRule($singleRule);
                    $singleRule = $singleRule->getNext();
                }
                $multipleRule = $qualifyGroup->getMultipleRule();
                /** @var array<int|string, Place> $changedPlaces */
                if ($multipleRule !== null) {
                    $toPlaces = $multipleRule->getToPlaces();
                    foreach ($toPlaces as $toPlace) {
                        $toPlace->setQualifiedPlace(null);
                        $toPlace->setExtraPoints(0);
                        $changedPlaces[] = $toPlace;
                    }
                }
//            } else { // QualifyDistribution::Vertical
//                $verticalRule = $qualifyGroup->getFirstVerticalRule();
//                while ($verticalRule !== null) {
//                    if( $verticalRule instanceof VerticalSingleQualifyRule) {
//                        $resetQualifiersForSingleRule($verticalRule);
//                    } else {
//                        /** @var array<int|string, Place> $changedPlaces */
//                        $toPlaces = $verticalRule->getToPlaces();
//                        foreach ($toPlaces as $toPlace) {
//                            $toPlace->setQualifiedPlace(null);
//                            $toPlace->setExtraPoints(0);
//                            $changedPlaces[] = $toPlace;
//                        }
//                    }
//                    $verticalRule = $verticalRule->getNext();
//                }
//            }



        }
        /** @var list<Place> $changedPlaces */
        return $changedPlaces;
    }

    /**
     * @param Poule|null $filterPoule
     * @return list<Place>
     */
    public function setQualifiers(Poule $filterPoule = null): array
    {
        /** @var array<int|string, Place> $changedPlaces */
        $changedPlaces = [];
        $setQualifiersForSingleRule = function (
            HorizontalSingleQualifyRule $singleQualifyRule,
            QualifyReservationService $reservationService
        ) use ($filterPoule, &$changedPlaces): void {
            foreach ($singleQualifyRule->getMappings() as $qualifyPlaceMapping) {
                $fromPlace = $qualifyPlaceMapping->getFromPlace();
                if ($filterPoule !== null && $fromPlace->getPoule() !== $filterPoule) {
                    continue;
                }
                /** @var array<int|string, Place> $changedPlaces */
                $this->setQualifierForPlaceMappingAndReserve($qualifyPlaceMapping, $reservationService);
                array_push($changedPlaces, $qualifyPlaceMapping->getToPlace());
            }
        };
        foreach ($this->round->getQualifyGroups() as $qualifyGroup) {
            $reservationService = new QualifyReservationService($qualifyGroup->getChildRound());

            $singleRule = $qualifyGroup->getFirstSingleRule();
            while ($singleRule !== null) {
                /** @var array<int|string, Place> $changedPlaces */
                if( $singleRule instanceof HorizontalSingleQualifyRule ) {
                    $setQualifiersForSingleRule($singleRule, $reservationService);
                } else {
                    $changedPlaces = array_merge(
                        $changedPlaces,
                        $this->setQualifiersForRankedRuleAndReserve(
                            $singleRule,
                            $reservationService
                        )
                    );
                }

                $singleRule = $singleRule->getNext();
            }
            $multipleRule = $qualifyGroup->getMultipleRule();
            /** @var array<int|string, Place> $changedPlaces */
            if ($multipleRule !== null) {
                $changedPlaces = array_merge(
                    $changedPlaces,
                    $this->setQualifiersForRankedRuleAndReserve(
                        $multipleRule,
                        $reservationService
                    )
                );
            }
        }
        /** @var array<int|string, Place> $changedPlaces */
        return array_values($changedPlaces);
    }

    protected function setQualifierForPlaceMappingAndReserve(
        QualifyPlaceMapping $qualifyPlaceMapping,
        QualifyReservationService $reservationService
    ): void {
        $poule = $qualifyPlaceMapping->getFromPlace()->getPoule();
        $rank = $qualifyPlaceMapping->getFromPlace()->getPlaceNr();
        $qualifiedPlace = $this->getQualifiedPlace($poule, $rank);
        $qualifyPlaceMapping->getToPlace()->setQualifiedPlace($qualifiedPlace);
        $reservationService->reserve($qualifyPlaceMapping->getToPlace()->getPoule()->getNumber(), $poule);
    }

    /**
     * @param HorizontalMultipleQualifyRule | VerticalMultipleQualifyRule | VerticalSingleQualifyRule $rankedRule
     * @param QualifyReservationService $reservationService
     * @return list<Place>
     */
    protected function setQualifiersForRankedRuleAndReserve(
        HorizontalMultipleQualifyRule | VerticalMultipleQualifyRule  | VerticalSingleQualifyRule $rankedRule,
        QualifyReservationService $reservationService
    ): array {
        $changedPlaces = [];
        $toPlaces = $rankedRule->getToPlaces();
        if (!$this->isRoundFinished()) {
            foreach ($toPlaces as $toPlace) {
                $toPlace->setQualifiedPlace(null);
                $changedPlaces[] = $toPlace;
            }
            return $changedPlaces;
        }
        $round = $rankedRule->getFromRound();
        $rankedPlaceLocations = $this->rankingCalculator->getPlaceLocationsForRankedRule($rankedRule);

        while (count($rankedPlaceLocations) > count($toPlaces)) {
            $rankedRule->getQualifyTarget() === QualifyTarget::Winners ? array_pop($rankedPlaceLocations) : array_shift($rankedPlaceLocations);
        }
        foreach ($toPlaces as $toPlace) {
            $toPouleNumber = $toPlace->getPoule()->getNumber();
            $rankedPlaceLocation = $reservationService->getFreeAndLeastAvailabe($toPouleNumber, $round, $rankedPlaceLocations);
            $toPlace->setQualifiedPlace($round->getPlace($rankedPlaceLocation));
            $changedPlaces[] = $toPlace;
            $index = array_search($rankedPlaceLocation, $rankedPlaceLocations, true);
            if ($index !== false) {
                array_splice($rankedPlaceLocations, $index, 1);
            }
        }
        /** @var list<Place> $changedPlaces */
        return $changedPlaces;
    }

    protected function getQualifiedPlace(Poule $poule, int $rank): ?Place
    {
        if( count($poule->getPlaces()) === 1 ) {
            return $poule->getPlace(1);
        }
        if (!$this->isPouleFinished($poule)) {
            return null;
        }
        $pouleRankingItems = $this->rankingCalculator->getItemsForPoule($poule);
        $rankingItem = $this->rankingCalculator->getItemByRank($pouleRankingItems, $rank);
        if ($rankingItem === null) {
            return null;
        }
        return $poule->getPlace($rankingItem->getPlace()->getPlaceNr());
    }

    protected function isRoundFinished(): bool
    {
        if ($this->roundFinished === null) {
            $this->roundFinished = true;
            foreach ($this->round->getPoules() as $poule) {
                if (!$this->isPouleFinished($poule)) {
                    $this->roundFinished = false;
                    break;
                }
            }
        }
        return $this->roundFinished;
    }

    protected function isPouleFinished(Poule $poule): bool
    {
        if (!array_key_exists($poule->getNumber(), $this->finishedPouleMap)) {
            $this->finishedPouleMap[$poule->getNumber()] = ($poule->getGamesState() === GameState::Finished);
        }
        return $this->finishedPouleMap[$poule->getNumber()];
    }
}
