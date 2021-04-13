<?php
declare(strict_types=1);

namespace Sports\Qualify\Rule;

use Exception;
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

    public function create(Round | null ...$parentRounds): void
    {
        foreach ([QualifyTarget::WINNERS, QualifyTarget::LOSERS] as $target) {
            foreach ($parentRounds as $parentRound) {
                if ($parentRound === null) {
                    continue;
                }
                $fromRoundHorPoules = $parentRound->getHorizontalPoules($target)->slice(0);
                foreach ($parentRound->getTargetQualifyGroups($target) as $qualifyGroup) {
                    $nrOfChildRoundPlaces = $qualifyGroup->getChildRound()->getNrOfPlaces();
                    $fromHorPoules = [];
                    while ($nrOfChildRoundPlaces > 0) {
                        $fromRoundHorPoule = array_shift($fromRoundHorPoules);
                        if ($fromRoundHorPoule === null) {
                            throw new Exception('fromRoundHorPoule should not be null', E_ERROR);
                        }
                        array_push($fromHorPoules, $fromRoundHorPoule);
                        $nrOfChildRoundPlaces -= $fromRoundHorPoule->getPlaces()->count();
                    }
                    $c = new DefaultCreator();
                    $c->createRules($fromHorPoules, $qualifyGroup);
                }
            }
        }
    }
}
