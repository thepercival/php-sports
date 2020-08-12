<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 2-1-19
 * Time: 16:34
 */

use Sports\Structure;
use Sports\Round;
use Sports\Competition;
use Sports\Round\Number as RoundNumber;

//function postSerialize( Structure $structure, Competition $competition ) {
//    postSerializeHelper( $structure->getRootRound(), $structure->getFirstRoundNumber(), $competition );
//}
//
//function postSerializeHelper( Round $round, RoundNumber $roundNumber, Competition $competition, RoundNumber $previousRoundNumber = null ) {
//    $refCl = new \ReflectionClass($round);
//    $refClPropNumber = $refCl->getProperty("number");
//    $refClPropNumber->setAccessible(true);
//    $refClPropNumber->setValue($round, $roundNumber);
//    $refClPropNumber->setAccessible(false);
//    $roundNumber->setCompetition($competition);
//    $roundNumber->getRounds()->add($round);
//    $roundNumber->setPrevious( $previousRoundNumber );
//    foreach( $round->getPoules() as $poule ) {
//        $poule->setRound($round);
//        foreach( $poule->getPlaces() as $poulePlace ) {
//            $poulePlace->setPoule($poule);
//        }
//        if( $poule->getGames() === null ) {
//            $poule->setGames([]);
//        }
//        foreach( $poule->getGames() as $game ) {
//            foreach( $game->getPoulePlaces() as $gamePoulePlace ) {
//                $gamePoulePlace->setPoulePlace($poule->getPlace($gamePoulePlace->getPoulePlaceNr()));
//            }
//            $game->setPoule($poule);
//            foreach ($game->getScores() as $gameScore) {
//                $gameScore->setGame($game);
//            }
//        }
//    }
//    foreach( $round->getChildren() as $childRound ) {
//        $childRound->setParent($round);
//        postSerializeHelper( $childRound, $roundNumber->getNext(), $competition, $roundNumber );
//    }
//}
