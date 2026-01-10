<?php

declare(strict_types=1);

namespace Sports\Qualify\ReservationService;

use Sports\Poule;

final class PouleNumberReservations
{
    /**
     * @param int $toPouleNr
     * @param list<Poule> $fromPoules
     */
    public function __construct(public int $toPouleNr, public array $fromPoules)
    {
    }
}
