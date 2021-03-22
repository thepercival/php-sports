<?php
declare(strict_types=1);

namespace Sports\SerializationHandler\Round;

use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use Sports\Round\Number as RoundNumber;
use Sports\SerializationHandler\CompetitionSportCreator;
use Sports\Planning\GameAmountConfig;
use Sports\Competition\Sport as CompetitionSport;

class Number implements SubscribingHandlerInterface
{
    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods()
    {
        return [
//            [
//                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
//                'format' => 'json',
//                'type' => 'Sports\Round\Number',
//                'method' => 'serializeToJson',
//            ],
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'Sports\Round\Number',
                'method' => 'deserializeFromJson',
            ],
        ];
    }

//    public function serializeToJson(JsonSerializationVisitor $visitor, RoundNumber $roundNumber, array $type, Context $context)
//    {
//        return $roundNumber;
//    }

    /**
     * Previous moet gezet worden, zou ook met slechts 1 reflection-actie gedaan kunnen worden, lijkt me wel makkelijker
     *
     * @param JsonDeserializationVisitor $visitor
     * @param array<string, int|string|array> $arrRoundNumber
     * @param array<string, int|string|array|null> $type
     * @param Context $context
     * @return RoundNumber
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array$arrRoundNumber,
        array $type,
        Context $context
    ): RoundNumber {
        $roundNumber = null;
        $params = $type["params"];
        if ($params !== null  && isset($params['previous'])) {
            $roundNumber = $params["previous"]->createNext();
        } else {
            $roundNumber = new RoundNumber($params["competition"], null);
        }

//        if( array_key_exists( "id", $arrRoundNumber) ) {
//            $roundNumber->setId($arrRoundNumber["id"]);
//        }

        if (array_key_exists("planningConfig", $arrRoundNumber)) {
            $metadataConfig = new StaticPropertyMetadata('Sports\Planning\Config', "planningConfig", $arrRoundNumber["planningConfig"]);
            $metadataConfig->setType(['name' => 'Sports\Planning\Config', "params" => [ "roundnumber" => $roundNumber]]);
            $roundNumber->setPlanningConfig($visitor->visitProperty($metadataConfig, $arrRoundNumber));
        }

        if (array_key_exists("gameAmountConfigs", $arrRoundNumber)) {
            $competitionSportCreator = new CompetitionSportCreator();
            foreach ($arrRoundNumber["gameAmountConfigs"] as $arrGameAmountConfig) {
                $competitionSport = $competitionSportCreator->create(
                    $roundNumber->getCompetition(),
                    (int) $arrGameAmountConfig["competitionSport"]["id"],
                    (int) $arrGameAmountConfig["competitionSport"]["sport"]["id"]
                );
                $this->createGameAmountConfig($arrGameAmountConfig, $competitionSport, $roundNumber);
            }
        }
        // qualifyAgainst en gameAmountConfigs ook toevoegen!!!

        if (isset($arrRoundNumber["next"])) {
            $arrRoundNumber["next"]["previous"] = $roundNumber;
            $metadataNext = new StaticPropertyMetadata('Sports\Round\Number', "next", $arrRoundNumber["next"]);
            $metadataNext->setType(['name' => 'Sports\Round\Number', "params" => [
                "competition" => $roundNumber->getCompetition(),
                "previous" => $roundNumber
            ]]);
            $next = $visitor->visitProperty($metadataNext, $arrRoundNumber);
        }

        return $roundNumber;
    }

    /**
     * @param array<string, int> $arrConfig
     * @param CompetitionSport $competitionSport
     * @param RoundNumber $roundNumber
     * @return GameAmountConfig
     */
    protected function createGameAmountConfig(
        array $arrConfig,
        CompetitionSport $competitionSport,
        RoundNumber $roundNumber
    ): GameAmountConfig {
        return new GameAmountConfig($competitionSport, $roundNumber, $arrConfig["amount"]);
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
//        $roundNumber->setCompetition($competition);
//        $roundNumber->getRounds()->add($round);
//        $roundNumber->setPrevious( $previousRoundNumber );
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
