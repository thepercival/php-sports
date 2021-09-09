<?php
declare(strict_types=1);

namespace Sports\Competition;

use Exception;
use Sports\Competition;

class Validator
{
    public function __construct()
    {
    }

    public function checkValidity(Competition $competition): void
    {
        $message = "competition:" . $competition->getName() . " => ";

        foreach ($competition->getSports() as $competitionSport) {
            try {
                $competitionSport->createVariant();
            } catch (Exception $e) {
                $message .=  $e->getMessage();
                throw new Exception($message, E_ERROR);
            }
        }
    }
}
