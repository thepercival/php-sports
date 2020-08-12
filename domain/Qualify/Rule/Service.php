<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 4-6-19
 * Time: 19:43
 */

namespace Sports\Qualify\Rule;

use Sports\Round;
use Sports\Place;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Rule\Queue as QualifyRuleQueue;
use Sports\Qualify\Rule\Multiple as QualifyRuleMultiple;
use Sports\Qualify\Rule\Single as QualifyRuleSingle;
use Sports\Qualify\Rule as QualifyRule;

use Sports\Qualify\ReservationService as QualifyReservationService;

class Service
{
    /**
     * @var Round
     */
    private $round;

    public function __construct(Round $round)
    {
        $this->round = $round;
    }

    public function recreateTo()
    {
        $this->removeTo($this->round);
        $this->createTo($this->round);
    }

    public function recreateFrom()
    {
        $parentRound = $this->round->getParent();
        if ($parentRound === null) {
            return;
        }
        $this->removeTo($parentRound);
        $this->createTo($parentRound);
    }

    protected function removeTo(Round $round)
    {
        foreach ($round->getPlaces() as $place) {
            $toQualifyRules = &$place->getToQualifyRules();
            foreach ($toQualifyRules as $toQualifyRule) {
                $toPlaces = [];
                if ($toQualifyRule->isMultiple()) {
                    $toPlaces = array_merge($toPlaces, $toQualifyRule->getToPlaces());
                } else {
                    $toPlaces[] = $toQualifyRule->getToPlace();
                }
                foreach ($toPlaces as $toPlace) {
                    $toPlace->setFromQualifyRule(null);
                }
            }
            $toQualifyRules = [];
        }
        foreach ([QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers) {
            foreach ($round->getHorizontalPoules($winnersOrLosers) as $horizontalPoule) {
                $horizontalPoule->setQualifyRuleMultiple(null);
            }
        }
    }

    protected function createTo(Round $round)
    {
        foreach ($round->getQualifyGroups() as $qualifyGroup) {
            $queue = new QualifyRuleQueue();
            $childRound = $qualifyGroup->getChildRound();
            $qualifyReservationService = new QualifyReservationService($childRound);

            // add rules and set from places
            {
                foreach ($qualifyGroup->getHorizontalPoules() as $horizontalPoule) {
                    if ($horizontalPoule->isBorderPoule() && $qualifyGroup->getNrOfToPlacesTooMuch() > 0) {
                        $nrOfToPlacesBorderPoule = $qualifyGroup->getChildRound()->getNrOfPlaces() % $round->getPoules()->count();
                        $queue->add(QualifyRuleQueue::START, new QualifyRuleMultiple($horizontalPoule, $nrOfToPlacesBorderPoule));
                    } else {
                        foreach ($horizontalPoule->getPlaces() as $place) {
                            $queue->add(QualifyRuleQueue::START, new QualifyRuleSingle($place, $qualifyGroup));
                        }
                    }
                }
            }
            $queue->shuffleIfUnevenAndNoMultiple($childRound->getPoules()->count());

            // update rules with to places
            $toHorPoules = $childRound->getHorizontalPoules($qualifyGroup->getWinnersOrLosers());
            $startEnd = QualifyRuleQueue::START;
            while (count($toHorPoules) > 0) {
                $toHorPoule = $startEnd === QualifyRuleQueue::START ? array_shift($toHorPoules) : array_pop($toHorPoules);
                foreach ($toHorPoule->getPlaces() as $place) {
                    $this->connectPlaceWithRule($place, $queue, $startEnd, $qualifyReservationService);
                }
                $startEnd = $queue->toggle($startEnd);
            }
        }
    }

    private function connectPlaceWithRule(Place $childPlace, QualifyRuleQueue $queue, int $startEnd, QualifyReservationService $reservationService)
    {

        /**
         * @param QualifyRuleSingle|QualifyRuleMultiple $qualifyRule
         */
        $setToPlacesAndReserve = function ($qualifyRule) use ($childPlace, $queue, $reservationService): void {
            if ($qualifyRule->isSingle()) {
                $setToPlacesAndReserveSingle = function (QualifyRuleSingle $qualifyRuleSingle) use ($childPlace, $reservationService): void {
                    $reservationService->reserve($childPlace->getPoule()->getNumber(), $qualifyRuleSingle->getFromPoule());
                    $qualifyRuleSingle->setToPlace($childPlace);
                };
                $setToPlacesAndReserveSingle($qualifyRule);
            } else {
                $setToPlacesAndReserveMultiple = function (QualifyRuleMultiple $qualifyRuleMultiple) use ($childPlace, $queue): void {
                    $qualifyRuleMultiple->addToPlace($childPlace);
                    if (!$qualifyRuleMultiple->toPlacesComplete()) {
                        $queue->add(QualifyRuleQueue::START, $qualifyRuleMultiple);
                    }
                };
                $setToPlacesAndReserveMultiple($qualifyRule);
            }
        };

        $unfreeQualifyRules = [];
        $oneQualifyRuleConnected = false;
        while (!$oneQualifyRuleConnected && !$queue->isEmpty()) {
            $qualifyRule = $queue->remove($startEnd);
            if (!$qualifyRule->isMultiple()
                && !$reservationService->isFree($childPlace->getPoule()->getNumber(), $qualifyRule->getFromPoule())) {
                $unfreeQualifyRules[] = $qualifyRule;
                continue;
            }
            $setToPlacesAndReserve($qualifyRule);
            $oneQualifyRuleConnected = true;
        }
        if ($startEnd === QualifyRuleQueue::END) {
            $unfreeQualifyRules = array_reverse($unfreeQualifyRules);
        }
        if (!$oneQualifyRuleConnected && count($unfreeQualifyRules) > 0) {
            $setToPlacesAndReserve(array_shift($unfreeQualifyRules));
        }

        while (count($unfreeQualifyRules) > 0) {
            $queue->add($startEnd, array_shift($unfreeQualifyRules));
        }
    }
}
