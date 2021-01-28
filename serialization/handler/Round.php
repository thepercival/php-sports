<?php

namespace Sports\SerializationHandler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Round as RoundBase;
use Sports\Poule;
use Sports\Place;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Score\Config as ScoreConfig;
use Sports\Qualify\AgainstConfig as QualifyAgainstConfig;

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
        $parentQualifyGroup = null;
        if (array_key_exists("parentqualifygroup", $type["params"]) && $type["params"]["parentqualifygroup"] !== null) {
            $parentQualifyGroup = $type["params"]["parentqualifygroup"];
        }

        $round = new RoundBase($type["params"]["roundnumber"], $parentQualifyGroup);
        // set poules
        foreach ($arrRound["poules"] as $arrPoule) {
            $poule = new Poule($round, $arrPoule["number"]);
            foreach ($arrPoule["places"] as $arrPlace) {
                $place = new Place($poule, $arrPlace["number"]);
                $place->setPenaltyPoints($arrPlace["penaltyPoints"]);

                if (!isset($arrPlace["qualifiedPlace"])) {
                    continue;
                }
                // @TODO DEPRECATED
//                $round->getParentQualifyGroup()->getRound()->getPoule()
//                $competitor = new Competitor($association, "dummy");
//                $competitor->setName($arrPlace["competitor"]["name"]);
//                if (array_key_exists("registered", $arrPlace["competitor"])) {
//                    $competitor->setRegistered($arrPlace["competitor"]["registered"]);
//                }
//                $place->setQualifiedPlace($qualifiedPlace);
            }
        }

        if (array_key_exists("scoreConfigs", $arrRound)) {
            $competitionSportCreator = new CompetitionSportCreator();
            foreach ($arrRound["scoreConfigs"] as $arrScoreConfig) {
                $competitionSport = $competitionSportCreator->create(
                    $round->getCompetition(),
                    (int) $arrScoreConfig["competitionSport"]["id"],
                    (int) $arrScoreConfig["competitionSport"]["sport"]["id"]
                );
                $this->createScoreConfig($arrScoreConfig, $competitionSport, $round);
            }
        }
        if (array_key_exists("qualifyAgainstConfigs", $arrRound)) {
            $competitionSportCreator = new CompetitionSportCreator();
            foreach ($arrRound["qualifyAgainstConfigs"] as $arrQualifyAgainstConfig) {
                $competitionSport = $competitionSportCreator->create(
                    $round->getCompetition(),
                    (int) $arrQualifyAgainstConfig["competitionSport"]["id"],
                    (int) $arrQualifyAgainstConfig["competitionSport"]["sport"]["id"]
                );
                $this->createQualifyAgainstConfig($arrQualifyAgainstConfig, $competitionSport, $round);
            }
        }

        foreach ($arrRound["qualifyGroups"] as $arrQualifyGroup) {
            $qualifyGroup = new QualifyGroup($round, $arrQualifyGroup["winnersOrLosers"]);
            $qualifyGroup->setNumber($arrQualifyGroup["number"]);
            $metadataConfig = new StaticPropertyMetadata('Sports\Round', "childRound", $arrQualifyGroup);
            $metadataConfig->setType(['name' => 'Sports\Round', "params" => [ "roundnumber" => $round->getNumber()->getNext(), "parentqualifygroup" => $qualifyGroup ]]);
            $qualifyGroup->setChildRound($visitor->visitProperty($metadataConfig, $arrQualifyGroup));
        }

        return $round;
    }

    protected function createScoreConfig(array $arrConfig, CompetitionSport $competitionSport, RoundBase $round, ScoreConfig $previous = null)
    {
        $config = new ScoreConfig($competitionSport, $round, $previous);
        $config->setDirection($arrConfig["direction"]);
        $config->setMaximum($arrConfig["maximum"]);
        $config->setEnabled($arrConfig["enabled"]);
        if (array_key_exists("next", $arrConfig) && $arrConfig["next"] !== null) {
            $this->createScoreConfig($arrConfig["next"], $competitionSport, $round, $config);
        }
    }

    protected function createQualifyAgainstConfig(array $arrConfig, CompetitionSport $competitionSport, RoundBase $round)
    {
        $config = new QualifyAgainstConfig($competitionSport, $round, $arrConfig["pointsCalculation"]);
        $config->setWinPoints($arrConfig["winPoints"]);
        $config->setWinPointsExt($arrConfig["winPointsExt"]);
        $config->setDrawPoints($arrConfig["drawPoints"]);
        $config->setDrawPointsExt($arrConfig["drawPointsExt"]);
        $config->setLosePointsExt($arrConfig["losePointsExt"]);
    }
}
