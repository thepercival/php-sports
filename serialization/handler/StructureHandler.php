<?php
declare(strict_types=1);

namespace Sports\SerializationHandler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Structure;

class StructureHandler extends Handler implements SubscribingHandlerInterface
{
    public function __construct(protected DummyCreator $dummyCreator) {
    }

    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(Structure::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param array<string, array> $fieldValue
     * @param array<string, int|string> $type
     * @param Context $context
     * @return Structure
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): Structure {
        $firstRoundNumber = $this->getProperty(
            $visitor,
            $fieldValue,
            "firstRoundNumber",
            RoundNumber::class);
        $fieldValue["rootRound"]["roundNumber"] = $firstRoundNumber;
        $rootRound = $this->getProperty(
            $visitor,
            $fieldValue,
            "rootRound",
            Round::class);

        return new Structure($firstRoundNumber,$rootRound);
    }



    //function postSerialize( Structure $structure, Competition $competition ) {
//    deserializeFromJson( $structure->getRootRound(), $structure->getFirstRoundNumber(), $competition );
//}
//
//    private function deserializeFromJson( Round $round, RoundNumber $roundNumber, Competition $competition, RoundNumber $previousRoundNumber = null ) {
//        $refCl = new \ReflectionClass($round);
//        $refClPropNumber = $refCl->getProperty("number");
//        $refClPropNumber->setAccessible(true);
//        $refClPropNumber->setValue($round, $roundNumber);
//        $refClPropNumber->setAccessible(false);
//
//        $roundNumber->getRounds()->add($round);
//        foreach( $round->getPoules() as $poule ) {
//            $poule->setRound($round);
//            foreach( $poule->getPlaces() as $poulePlace ) {
//                $poulePlace->setPoule($poule);
//            }
//            if( $poule->getGames() === null ) {
//                $poule->setGames([]);
//            }
//            foreach( $poule->getGames() as $game ) {
//                foreach( $game->getPoulePlaces() as $gamePoulePlace ) {
//                    $gamePoulePlace->setPoulePlace($poule->getPlace($gamePoulePlace->getPoulePlaceNr()));
//                }
//                $game->setPoule($poule);
//                foreach ($game->getScores() as $gameScore) {
//                    $gameScore->setGame($game);
//                }
//            }
//        }
//        foreach( $round->getChildren() as $childRound ) {
//            $childRound->setParent($round);
//            postSerializeHelper( $childRound, $roundNumber->getNext(), $competition, $roundNumber );
//        }
//    }
}
