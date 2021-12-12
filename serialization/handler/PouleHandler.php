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
use Sports\Ranking\PointsCalculation;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Score\Config as ScoreConfig;
use Sports\Qualify\AgainstConfig as AgainstQualifyConfig;

class PouleHandler extends Handler implements SubscribingHandlerInterface
{
    public function __construct(protected DummyCreator $dummyCreator)
    {
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
    ): Poule {
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
    ): ScoreConfig {
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
     * @param array<string, int|bool|array<string, int|bool|PointsCalculation>> $arrConfig
     * @param CompetitionSport $competitionSport
     * @param Round $round
     * @return AgainstQualifyConfig
     */
    protected function createAgainstQualifyConfig(
        array $arrConfig,
        CompetitionSport $competitionSport,
        Round $round
    ): AgainstQualifyConfig {
        $config = new AgainstQualifyConfig($competitionSport, $round, $arrConfig["pointsCalculation"]);
        $config->setWinPoints($arrConfig["winPoints"]);
        $config->setWinPointsExt($arrConfig["winPointsExt"]);
        $config->setDrawPoints($arrConfig["drawPoints"]);
        $config->setDrawPointsExt($arrConfig["drawPointsExt"]);
        $config->setLosePointsExt($arrConfig["losePointsExt"]);
        return $config;
    }
}
