<?php
declare(strict_types=1);

namespace Sports\Qualify\Rule;

use Sports\Round;
use Sports\Place;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Rule\Queue as QualifyRuleQueue;
use Sports\Qualify\Rule\Multiple as MultipleQualifyRule;
use Sports\Qualify\Rule\Single as SingleQualifyRule;

use Sports\Qualify\ReservationService as QualifyReservationService;

class Service
{
    public function __construct(private Round $round)
    {
    }

    public function recreateTo(): void
    {
        $this->removeTo($this->round);
        $this->createTo($this->round);
    }

    public function recreateFrom(): void
    {
        $parentRound = $this->round->getParent();
        if ($parentRound === null) {
            return;
        }
        $this->removeTo($parentRound);
        $this->createTo($parentRound);
    }

    public function removeTo(Round $round): void
    {
        foreach ($round->getPlaces() as $place) {
            $singleToQualifyRule = $place->getSingleToQualifyRule();
            if ($singleToQualifyRule === null) {
                continue;
            }
            $singleToQualifyRule->getToPlace()->setFromQualifyRule(null);
            $place->setSingleToQualifyRule(null);
        }
        foreach ([QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers) {
            foreach ($round->getHorizontalPoules2($winnersOrLosers) as $horizontalPoule) {
                $multipleRule = $horizontalPoule->getMultipleQualifyRule();
                if ($multipleRule === null) {
                    continue;
                }
                foreach ($multipleRule->getToPlaces() as $toPlace) {
                    $toPlace->setFromQualifyRule(null);
                }
                $horizontalPoule->setMultipleQualifyRule(null);
            }
        }
    }

    public function createTo(Round $round): void
    {
        foreach ($round->getQualifyGroups() as $qualifyGroup) {
            $queue = new QualifyRuleQueue();
            $childRound = $qualifyGroup->getChildRound();
            $qualifyReservationService = new QualifyReservationService($childRound);

            // add rules and set from places
            {
                foreach ($qualifyGroup->getHorizontalPoules2() as $horizontalPoule) {
                    if ($horizontalPoule->isBorderPoule() && $qualifyGroup->getNrOfToPlacesTooMuch() > 0) {
                        $nrOfToPlacesBorderPoule = $qualifyGroup->getChildRound()->getNrOfPlaces() % $round->getPoules()->count();
                        $queue->add(QualifyRuleQueue::START, new MultipleQualifyRule($horizontalPoule, $nrOfToPlacesBorderPoule));
                    } else {
                        foreach ($horizontalPoule->getPlaces2() as $place) {
                            $singleQualifyRule = new SingleQualifyRule($place, $qualifyGroup->getWinersOrLosers);
                            $queue->add(QualifyRuleQueue::START, $singleQualifyRule);
                        }
                    }
                }
            }
            if (($childRound->getPoules()->count() % 2) === 1) {
                $queue->moveCenterSingleRuleBack($childRound->getPoules()->count());
            }

            // update rules with to places
            $toHorPoules = $childRound->getHorizontalPoules2($qualifyGroup->getWinnersOrLosers());
            $startEnd = QualifyRuleQueue::START;
            while (count($toHorPoules) > 0) {
                $toHorPoule = $startEnd === QualifyRuleQueue::START ? array_shift($toHorPoules) : array_pop($toHorPoules);
                foreach ($toHorPoule->getPlaces2() as $place) {
                    $this->connectPlaceWithRule($place, $queue, $startEnd, $qualifyReservationService);
                }
                $startEnd = $queue->getOpposite($startEnd);
            }
        }
    }

    private function connectPlaceWithRule(Place $childPlace, QualifyRuleQueue $queue, int $startEnd, QualifyReservationService $reservationService): void
    {
        $setToPlacesAndReserve = function (SingleQualifyRule|MultipleQualifyRule $qualifyRule) use ($childPlace, $queue, $reservationService): void {
            if ($qualifyRule instanceof SingleQualifyRule) {
                $reservationService->reserve($childPlace->getPoule()->getNumber(), $qualifyRule->getFromPoule());
                $qualifyRule->setToPlace($childPlace);
            } else {
                $qualifyRule->addToPlace($childPlace);
                if (!$qualifyRule->toPlacesComplete()) {
                    $queue->add(QualifyRuleQueue::START, $qualifyRule);
                }
            }
        };

        $unfreeQualifyRules = [];
        $someQualifyRuleConnected = false;
        while (!$someQualifyRuleConnected && !$queue->isEmpty()) {
            $qualifyRule = $queue->remove($startEnd);
            if ($qualifyRule === null) {
                break;
            }
            if (!($qualifyRule instanceof MultipleQualifyRule)
                && !$reservationService->isFree($childPlace->getPoule()->getNumber(), $qualifyRule->getFromPoule())) {
                $unfreeQualifyRules[] = $qualifyRule;
                continue;
            }
            $setToPlacesAndReserve($qualifyRule);
            $someQualifyRuleConnected = true;
        }
        if ($startEnd === QualifyRuleQueue::END) {
            $unfreeQualifyRules = array_reverse($unfreeQualifyRules);
        }
        if (!$someQualifyRuleConnected && count($unfreeQualifyRules) > 0) {
            $setToPlacesAndReserve(array_shift($unfreeQualifyRules));
        }

        while (count($unfreeQualifyRules) > 0) {
            $queue->add($startEnd, array_shift($unfreeQualifyRules));
        }
    }
}
