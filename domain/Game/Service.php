<?php

namespace Sports\Game;

use League\Period\Period;
use Sports\Game\Repository as GameRepository;
use Sports\Game\Score\Repository as GameScoreRepository;
use Sports\Place;
use Sports\Game;
use Sports\Planning\GameGenerator;
use Sports\Planning\Input;
use Sports\Referee;
use Sports\Field;
use Sports\Game\Score as GameScore;
use Sports\Round\Number as RoundNumber;

class Service
{
    public function __construct()
    {
    }

    /**
     * @param Game $game
     * @param Field|null $field
     * @param Referee|null $referee
     * @param Place|null $refereePlace
     * @return Game
     */
    public function editResource(Game $game, Field $field = null, Referee $referee = null, Place $refereePlace = null)
    {
        $game->setField($field);
        $game->setReferee($referee);
        $game->setRefereePlace($refereePlace);
        return $game;
    }

    /**
     * @param Game $game
     * @param array|GameScore[] $newGameScores
     */
    public function addScores(Game $game, array $newGameScores)
    {
        foreach ($newGameScores as $newGameScore) {
            new GameScore($game, $newGameScore->getHome(), $newGameScore->getAway(), $newGameScore->getPhase());
        }
    }


//    public function setBlockedPeriod(\DateTimeImmutable $startDateTime, int $durationInMinutes) {
//        $endDateTime = clone $startDateTime;
//        $endDateTime->modify("+" . $durationInMinutes . " minutes");
//        $this->blockedPeriod = new Period($startDateTime, $endDateTime);
//    }

   // protected $blockedPeriod;
//    public function getStartDateTime(): \DateTimeImmutable {
//        return $this->competition->getStartDateTime();
//}

//
//    public function reschedule( RoundNumber $roundNumber, \DateTimeImmutable $startDateTime = null )
//    {
//        if ($startDateTime === null && $this->canCalculateStartDateTime($roundNumber)) {
//            $startDateTime = $this->calculateStartDateTime($roundNumber);
//        }
//
//        $startNextRound = $this->rescheduleHelper($roundNumber, $startDateTime);
//        if ($roundNumber->hasNext()) {
//            $this->reschedule( $roundNumber->getNext(), $startNextRound );
//        }
//    }
//
//    public function create( RoundNumber $roundNumber, \DateTimeImmutable $startDateTime = null ) {
//        if ($startDateTime === null && $this->canCalculateStartDateTime($roundNumber)) {
//            $startDateTime = $this->calculateStartDateTime($roundNumber);
//        }
//        $this->removeNumber($roundNumber);
//
//        $startNextRound = $this->rescheduleHelper($roundNumber, $startDateTime);
//        if ($roundNumber->hasNext()) {
//            $this->create($roundNumber->getNext(), $startNextRound);
//        }
//    }
//
//    // get inputPlanning from roundNumber and add dates
//


//    protected function removeNumber(RoundNumber $roundNumber) {
//        $rounds = $roundNumber->getRounds();
//        foreach( $rounds as $round ) {
//            foreach( $round->getPoules() as $poule ) {
//                $poule->getGames()->clear();
//            }
//        }
//    }
}
