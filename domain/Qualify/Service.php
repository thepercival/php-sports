<?php
declare(strict_types=1);

namespace Sports\Qualify;

use Sports\Qualify\Target as QualifyTarget;
use Sports\Ranking\Calculator\Round as RoundRankingCalculator;
use Sports\Qualify\ReservationService as QualifyReservationService;
use Sports\Qualify\PlaceMapping as QualifyPlaceMapping;
use Sports\Poule;
use Sports\Place;
use Sports\Round;
use Sports\State;
use Sports\Qualify\Rule\Single as SingleQualifyRule;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;

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
        /** @var array<int|string, Place> $changedPlaces */
        $changedPlaces = [];
        $resetQualifiersForSingleRule = function (SingleQualifyRule $singleQualifyRule) use ($filterPoule, &$changedPlaces): void {
            foreach ($singleQualifyRule->getMappings() as $qualifyPlaceMapping) {
                $fromPlace = $qualifyPlaceMapping->getFromPlace();
                if ($filterPoule !== null && $fromPlace->getPoule() !== $filterPoule) {
                    continue;
                }
                $qualifyPlaceMapping->getToPlace()->setQualifiedPlace(null);
                /** @var array<int|string, Place> $changedPlaces */
                array_push($changedPlaces, $qualifyPlaceMapping->getToPlace());
            }
        };
        foreach ($this->round->getQualifyGroups() as $qualifyGroup) {
            $singleRule = $qualifyGroup->getFirstSingleRule();
            while ($singleRule !== null) {
                $resetQualifiersForSingleRule($singleRule);
                $singleRule = $singleRule->getNext();
            }
            $multipleRule = $qualifyGroup->getMultipleRule();
            /** @var array<int|string, Place> $changedPlaces */
            if ($multipleRule !== null) {
                foreach ($multipleRule->getToPlaces() as $toPlace) {
                    $toPlace->setQualifiedPlace(null);
                    array_push($changedPlaces, $toPlace);
                }
            }
        }
        /** @var array<int|string, Place> $changedPlaces */
        return array_values($changedPlaces);
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
            SingleQualifyRule $singleQualifyRule,
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
                $setQualifiersForSingleRule($singleRule, $reservationService);
                $singleRule = $singleRule->getNext();
            }
            $multipleRule = $qualifyGroup->getMultipleRule();
            /** @var array<int|string, Place> $changedPlaces */
            if ($multipleRule !== null) {
                $changedPlaces = array_merge(
                    $changedPlaces,
                    $this->setQualifiersForMultipleRuleAndReserve(
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
     * @param MultipleQualifyRule $multipleRule
     * @param QualifyReservationService $reservationService
     * @return list<Place>
     */
    protected function setQualifiersForMultipleRuleAndReserve(
        MultipleQualifyRule $multipleRule,
        QualifyReservationService $reservationService
    ): array {
        $changedPlaces = [];
        $toPlaces = $multipleRule->getToPlaces();
        if (!$this->isRoundFinished()) {
            foreach ($toPlaces as $toPlace) {
                $toPlace->setQualifiedPlace(null);
                $changedPlaces[] = $toPlace;
            }
            return $changedPlaces;
        }
        $round = $multipleRule->getFromRound();
        $rankedPlaceLocations = $this->rankingCalculator->getPlaceLocationsForMultipleRule($multipleRule);

        while (count($rankedPlaceLocations) > count($toPlaces)) {
            $multipleRule->getQualifyTarget() === QualifyTarget::WINNERS ? array_pop($rankedPlaceLocations) : array_shift($rankedPlaceLocations);
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
        return $changedPlaces;
    }

    protected function getQualifiedPlace(Poule $poule, int $rank): ?Place
    {
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
            $this->finishedPouleMap[$poule->getNumber()] = ($poule->getState() === State::Finished);
        }
        return $this->finishedPouleMap[$poule->getNumber()];
    }
}
