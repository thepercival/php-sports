<?php
declare(strict_types=1);

namespace Sports\Qualify\Rule;

use Exception;
use Sports\Round;
use Sports\Qualify\Target as QualifyTarget;

class Creator {
    /**
     * @param list<Round | null>
     */
    public function remove(Round|null ...$parentRounds) {
        foreach($parentRounds as $parentRound) {
            if ($parentRound === null) {
                return;
            }
            foreach( $parentRound->getQualifyGroups() as $qualifyGroup) {
                $qualifyGroup->detachRules();
            }
        }
    }

    public function create(Round | null ...$parentRounds) {
        foreach( [QualifyTarget::WINNERS, QualifyTarget::LOSERS] as $target) {
            foreach($parentRounds as $parentRound) {
                if ($parentRound === null) {
                    continue;
                }
                $fromRoundHorPoules = $parentRound->getHorizontalPoules($target)->slice(0);
                foreach( $parentRound->getQualifyGroups($target) as $qualifyGroup) {
                    $nrOfChildRoundPlaces = $qualifyGroup->getChildRound()->getNrOfPlaces();
                    $fromHorPoules = [];
                    while ($nrOfChildRoundPlaces > 0) {
                        $fromRoundHorPoule = $fromRoundHorPoules->shift();
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