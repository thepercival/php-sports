<?php

namespace Sports\Exceptions;

use Exception;

class RefereesPriorityNotCorrectlyAppliedInGamesException extends Exception
{
    public function __construct(int $roundNumber, int $orderedGameNr, int $refereePriority) {
        parent::__construct(
                    'for roundNumber "'.$roundNumber.'" ' .
                    ', gamePriority "'.$orderedGameNr.'" ' .
                    'and refereePriority "'.$refereePriority.'" should be the same', E_ERROR);
    }
}