<?php

declare(strict_types=1);

namespace Sports\Qualify\Rule;

use Exception;
use Sports\Qualify\PossibleFromMap;
use Sports\Round;
use Sports\Qualify\Target as QualifyTarget;

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

    public function create(Round $parentRound, Round|null $grandParentRound, bool $tpyeCheckTmp): void
    {
        if ($grandParentRound !== null) {
            $this->createForParentRound($grandParentRound);
        }
        $this->createForParentRound($parentRound);
    }

    public function createForParentRound(Round $parentRound): void
    {
        foreach ([QualifyTarget::Winners, QualifyTarget::Losers] as $target) {
            $fromRoundHorPoules = $parentRound->getHorizontalPoules($target)->slice(0);
            foreach ($parentRound->getTargetQualifyGroups($target) as $qualifyGroup) {
                $childRound = $qualifyGroup->getChildRound();
                $defaultRuleCreator = new DefaultCreator($childRound);
                $nrOfChildRoundPlaces = $childRound->getNrOfPlaces();
                $fromHorPoules = [];
                while ($nrOfChildRoundPlaces > 0) {
                    $fromRoundHorPoule = array_shift($fromRoundHorPoules);
                    if ($fromRoundHorPoule === null) {
                        throw new Exception('fromRoundHorPoule should not be null', E_ERROR);
                    }
                    array_push($fromHorPoules, $fromRoundHorPoule);
                    $nrOfChildRoundPlaces -= $fromRoundHorPoule->getPlaces()->count();
                }
                $defaultRuleCreator->createRules($fromHorPoules, $qualifyGroup);
            }
        }
    }
}
