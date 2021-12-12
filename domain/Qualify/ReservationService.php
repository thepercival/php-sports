<?php

declare(strict_types=1);

namespace Sports\Qualify;

use Sports\Qualify\ReservationService\PouleNumberReservations;
use Sports\Round;
use Sports\Poule;
use Sports\Place\Location as PlaceLocation;

class ReservationService
{
    /**
     * @var list<PouleNumberReservations>
     */
    private array $reservations = [];

    public function __construct(Round $childRound)
    {
        $this->reservations  = [];
        foreach ($childRound->getPoules() as $poule) {
            $this->reservations[] = new PouleNumberReservations($poule->getNumber(), []);
        }
    }

    public function isFree(int $toPouleNumber, Poule $fromPoule): bool
    {
        return array_search($fromPoule, $this->get($toPouleNumber)->fromPoules, true) === false;
    }

    public function reserve(int $toPouleNumber, Poule $fromPoule): void
    {
        $this->get($toPouleNumber)->fromPoules[] = $fromPoule;
    }

    protected function get(int $toPouleNumber): PouleNumberReservations
    {
        $filtered = array_filter($this->reservations, function ($reservationIt) use ($toPouleNumber): bool {
            return $reservationIt->toPouleNr === $toPouleNumber;
        });
        $first = array_shift($filtered);
        if ($first === null) {
            throw new \Exception('kan poule-reserveringen niet vinden', E_ERROR);
        }
        return $first;
    }

    /**
     * @param int $toPouleNumber
     * @param Round $fromRound
     * @param list<PlaceLocation> $fromPlaceLocations
     * @return PlaceLocation
     */
    public function getFreeAndLeastAvailabe(int $toPouleNumber, Round $fromRound, array $fromPlaceLocations): PlaceLocation
    {
        $retPlaceLocation = null;
        $leastNrOfPoulesAvailable = null;
        foreach ($fromPlaceLocations as $fromPlaceLocation) {
            $fromPoule = $fromRound->getPoule($fromPlaceLocation->getPouleNr());
            if (!$this->isFree($toPouleNumber, $fromPoule)) {
                continue;
            }
            $nrOfPoulesAvailable = $this->getNrOfPoulesAvailable($fromPoule, $toPouleNumber + 1);
            if ($leastNrOfPoulesAvailable === null || $nrOfPoulesAvailable < $leastNrOfPoulesAvailable) {
                $retPlaceLocation = $fromPlaceLocation;
                $leastNrOfPoulesAvailable = $nrOfPoulesAvailable;
            }
        }
        if ($retPlaceLocation === null) {
            return $fromPlaceLocations[0];
        }
        return $retPlaceLocation;
    }

    protected function getNrOfPoulesAvailable(Poule $fromPoule, int $toPouleNumber): int
    {
        $filtered = array_filter($this->reservations, function ($reservation) use ($fromPoule, $toPouleNumber): bool {
            return $reservation->toPouleNr >= $toPouleNumber && $this->isFree($reservation->toPouleNr, $fromPoule);
        });
        return count($filtered);
    }
}
