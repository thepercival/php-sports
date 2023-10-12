<?php

declare(strict_types=1);

namespace Sports\Qualify\Rule;

use Sports\Qualify\Distribution as QualifyDistribution;
use Sports\Qualify\Rule\Creator\Horizontal as HorizontalQualifyRuleCreator;
use Sports\Qualify\Rule\Creator\Vertical as VerticalQualifyRuleCreator;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Round;

class Creator
{
    /**
     * @param Round|null ...$parentRounds
     */
    public function remove(Round|null ...$parentRounds): void
    {
        foreach ($parentRounds as $parentRound) {
            if ($parentRound === null) {
                return;
            }
            foreach ($parentRound->getQualifyGroups() as $qualifyGroup) {
                $qualifyGroup->detachRules();
            }
        }
    }

    public function create(Round $parentRound, Round|null $grandParentRound): void
    {
        if ($grandParentRound !== null) {
            $this->createForParentRound($grandParentRound);
        }
        $this->createForParentRound($parentRound);
    }

    public function createForParentRound(Round $parentRound): void
    {
        foreach ([QualifyTarget::Winners, QualifyTarget::Losers] as $target) {
            $fromRoundHorPoules = array_slice( $parentRound->getHorizontalPoules($target)->toArray(), 0 );
            foreach ($parentRound->getTargetQualifyGroups($target) as $qualifyGroup) {
                if( $qualifyGroup->getDistribution() === QualifyDistribution::HorizontalSnake ) {
                    $childRound = $qualifyGroup->getChildRound();
                    $creator = new HorizontalQualifyRuleCreator($childRound);
                } else {
                    $creator = new VerticalQualifyRuleCreator();

                }
                $creator->createRules(array_values( $fromRoundHorPoules), $qualifyGroup);

//                $childRound = $qualifyGroup->getChildRound();
//                $defaultRuleCreator = new Creator($childRound);
//                $nrOfChildRoundPlaces = $childRound->getNrOfPlaces();
//                $fromHorPoules = [];
//                while ($nrOfChildRoundPlaces > 0) {
//                    $fromRoundHorPoule = array_shift($fromRoundHorPoules);
//                    if ($fromRoundHorPoule === null) {
//                        throw new Exception('fromRoundHorPoule should not be null', E_ERROR);
//                    }
//                    array_push($fromHorPoules, $fromRoundHorPoule);
//                    $nrOfChildRoundPlaces -= $fromRoundHorPoule->getPlaces()->count();
//                }
//                $defaultRuleCreator->createRules($fromHorPoules, $qualifyGroup);
            }
        }
    }
}
