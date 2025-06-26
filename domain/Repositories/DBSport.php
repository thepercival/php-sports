<?php

namespace Sports\Repositories;

use SportsHelpers\Identifiable;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;

abstract class DBSport extends Identifiable
{
    public readonly int $nrOfHomePlaces;
    public readonly int $nrOfAwayPlaces;
    public readonly int|null $nrOfGamePlaces;

    public function __construct(AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport $sport) {
        if( $sport instanceof TogetherSport) {
            $this->nrOfHomePlaces = 0;
            $this->nrOfAwayPlaces = 0;
            $this->nrOfGamePlaces = $sport->nrOfGamePlaces;
        } else if( $sport instanceof AgainstOneVsOne) {
            $this->nrOfHomePlaces = 1;
            $this->nrOfAwayPlaces = 1;
            $this->nrOfGamePlaces = 0;
        } else if( $sport instanceof AgainstOneVsTwo) {
            $this->nrOfHomePlaces = 1;
            $this->nrOfAwayPlaces = 2;
            $this->nrOfGamePlaces = 0;
        } else { // if( $sport instanceof AgainstTwoVsTwo) {
            $this->nrOfHomePlaces = 2;
            $this->nrOfAwayPlaces = 2;
            $this->nrOfGamePlaces = 0;
        }
    }

    public function createSport(): AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport
    {
        if( $this->nrOfGamePlaces !== 0) {
            return new TogetherSport($this->nrOfGamePlaces);
        }
        if( $this->nrOfHomePlaces === 1 && $this->nrOfAwayPlaces === 1) {
            return new AgainstOneVsOne();
        }
        if( $this->nrOfHomePlaces === 1 && $this->nrOfAwayPlaces === 2) {
            return new AgainstOneVsTwo();
        }
        if( $this->nrOfHomePlaces === 2 && $this->nrOfAwayPlaces === 2) {
            return new AgainstTwoVsTwo();
        }
        throw new \Exception('unknown sport in dbsport');
    }
}