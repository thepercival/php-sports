<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 9-6-19
 * Time: 13:12
 */

namespace Sports\SerializationHandler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;

use Sports\Round as RoundBase;
use Sports\Poule;
use Sports\Place;
use Sports\Competitor;
use Sports\Qualify\Group as QualifyGroup;

class Round implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return [
//            [
//                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
//                'format' => 'json',
//                'type' => 'DateTime',
//                'method' => 'serializeToJson',
//            ],
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => 'Sports\Round',
                'method' => 'deserializeFromJson',
            ],
        ];
    }

    public function deserializeFromJson(JsonDeserializationVisitor $visitor, $arrRound, array $type, Context $context)
    {
        $roundNumber = $type["params"]["roundnumber"];
        $parentQualifyGroup = null;
        if (array_key_exists("parentqualifygroup", $type["params"]) && $type["params"]["parentqualifygroup"] !== null) {
            $parentQualifyGroup = $type["params"]["parentqualifygroup"];
        }

        $round = new RoundBase($roundNumber, $parentQualifyGroup);
        $association = $round->getNumber()->getCompetition()->getLeague()->getAssociation();

        // set poules
        foreach ($arrRound["poules"] as $arrPoule) {
            $poule = new Poule($round, $arrPoule["number"]);
            foreach ($arrPoule["places"] as $arrPlace) {
                $place = new Place($poule, $arrPlace["number"]);
                $place->setPenaltyPoints($arrPlace["penaltyPoints"]);

                if (!array_key_exists("competitor", $arrPlace)) {
                    continue;
                }
                $competitor = new Competitor($association, "dummy");
                $competitor->setName($arrPlace["competitor"]["name"]);
                if (array_key_exists("registered", $arrPlace["competitor"])) {
                    $competitor->setRegistered($arrPlace["competitor"]["registered"]);
                }
                $place->setCompetitor($competitor);
            }
        }

        foreach ($arrRound["qualifyGroups"] as $arrQualifyGroup) {
            $qualifyGroup = new QualifyGroup($round, $arrQualifyGroup["winnersOrLosers"]);
            $qualifyGroup->setNumber($arrQualifyGroup["number"]);
            $metadataConfig = new StaticPropertyMetadata('Sports\Round', "childRound", $arrQualifyGroup);
            $metadataConfig->setType(['name' => 'Sports\Round', "params" => [ "roundnumber" => $roundNumber->getNext(), "parentqualifygroup" => $qualifyGroup ]]);
            $qualifyGroup->setChildRound($visitor->visitProperty($metadataConfig, $arrQualifyGroup));
        }

        return $round;
    }
}
