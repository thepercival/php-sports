<?php
declare(strict_types=1);

namespace Sports\SerializationHandler;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Place;
use Sports\Poule;
use Sports\Qualify\AgainstConfig\Service as AgainstQualifyConfigService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Score\Config as ScoreConfig;
use Sports\Qualify\AgainstConfig as AgainstQualifyConfig;

class PouleHandler extends Handler implements SubscribingHandlerInterface
{
    public function __construct(protected DummyCreator $dummyCreator) {
    }

    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(Poule::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param array<string, bool|int|Round|array> $fieldValue
     * @param array<string, array> $type
     * @param Context $context
     * @return Poule
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): Poule
    {
        if (!isset($fieldValue["round"])) {
            throw new \Exception('malformd json => poule', E_ERROR);
        }
        $round = $fieldValue["round"];
        $poule = new Poule($round, $fieldValue["number"]);

        foreach ($fieldValue["places"] as $arrPlace) {
            $fieldValue["place"] = $arrPlace;
            $fieldValue["place"]["poule"] = $poule;
            new Place($poule);
            /*$this->getProperty(
                $visitor,
                $fieldValue,
                "place",
                Place::class
            );*/
        }
        return $poule;
    }
//
//        $roundNumber = $type["params"]["roundNumber"];
//        $parentQualifyGroup = null;
//        if (isset($type["params"]['parentQualifyGroup'])) {
//            $parentQualifyGroup = $type["params"]['parentQualifyGroup'];
//        }
//        $round = new Round($roundNumber, $parentQualifyGroup);
//
//        if (isset($fieldValue["scoreConfigs"])) {
//            $competitionSportCreator = new CompetitionSportCreator();
//            foreach ($fieldValue["scoreConfigs"] as $arrScoreConfig) {
//                $competitionSport = $competitionSportCreator->create(
//                    $round->getCompetition(),
//                    (int) $arrScoreConfig["competitionSport"]["id"],
//                    (int) $arrScoreConfig["competitionSport"]["sport"]["id"]
//                );
//                $this->createScoreConfig($arrScoreConfig, $competitionSport, $round);
//            }
//        }
////        if (isset($fieldValue["againstQualifyConfigs"])) {
////            $competitionSportCreator = new CompetitionSportCreator();
////            foreach ($fieldValue["againstQualifyConfigs"] as $arrAgainstQualifyConfig) {
////                $competitionSport = $competitionSportCreator->create(
////                    $round->getCompetition(),
////                    (int) $arrAgainstQualifyConfig["competitionSport"]["id"],
////                    (int) $arrAgainstQualifyConfig["competitionSport"]["sport"]["id"]
////                );
////                $this->createAgainstQualifyConfig($arrAgainstQualifyConfig, $competitionSport, $round);
////            }
////        }
//
//        $nextRoundNumber = $roundNumber->getNext();
//        if ($nextRoundNumber !== null && isset($fieldValue["qualifyGroups"])) {
//            $this->getProperty(
//                $visitor,
//                $fieldValue,
//                "qualifyGroups",
//                ArrayCollection::class,
//                ["round" => $round, "nextRoundNumber" => $nextRoundNumber]
//            );
//        }
//        return $round;



        // set poules
//        foreach ($arrRound["poules"] as $arrPoule) {
//            $poule = new Poule($round, $arrPoule["number"]);
//            foreach ($arrPoule["places"] as $arrPlace) {
//                $place = new Place($poule, $arrPlace["number"]);
//                $place->setPenaltyPoints($arrPlace["penaltyPoints"]);
//
//                if (!isset($arrPlace["qualifiedPlace"])) {
//                    continue;
//                }
                // @TODO DEPRECATED
//                $round->getParentQualifyGroup()->getRound()->getPoule()
//                $competitor = new Competitor($association, "dummy");
//                $competitor->setName($arrPlace["competitor"]["name"]);
//                if (array_key_exists("registered", $arrPlace["competitor"])) {
//                    $competitor->setRegistered($arrPlace["competitor"]["registered"]);
//                }
//                $place->setQualifiedPlace($qualifiedPlace);
//            }

    /**
     * @param array<string, int|bool|array<string, int|bool>> $arrConfig
     * @param CompetitionSport $competitionSport
     * @param Round $round
     * @param ScoreConfig|null $previous
     * @return ScoreConfig
     */
    protected function createScoreConfig(
        array $arrConfig,
        CompetitionSport $competitionSport,
        Round $round,
        ScoreConfig $previous = null
    ): ScoreConfig
    {
        $config = new ScoreConfig(
            $competitionSport,
            $round,
            $arrConfig["direction"],
            $arrConfig["maximum"],
            $arrConfig["enabled"],
            $previous
        );
        if (isset($arrConfig["next"])) {
            $this->createScoreConfig($arrConfig["next"], $competitionSport, $round, $config);
        }
        return $config;
    }

    /**
     * @param array<string, int|bool|array<string, int|bool>> $arrConfig
     * @param CompetitionSport $competitionSport
     * @param Round $round
     * @return AgainstQualifyConfig
     */
    protected function createAgainstQualifyConfig(
        array $arrConfig,
        CompetitionSport $competitionSport,
        Round $round
    ): AgainstQualifyConfig
    {
        $config = new AgainstQualifyConfig($competitionSport, $round, $arrConfig["pointsCalculation"]);
        $config->setWinPoints($arrConfig["winPoints"]);
        $config->setWinPointsExt($arrConfig["winPointsExt"]);
        $config->setDrawPoints($arrConfig["drawPoints"]);
        $config->setDrawPointsExt($arrConfig["drawPointsExt"]);
        $config->setLosePointsExt($arrConfig["losePointsExt"]);
        return $config;
    }
}
