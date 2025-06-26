<?php

declare(strict_types=1);

namespace Sports\SerializationHandler;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;

use Sports\Competition\CompetitionSport as CompetitionSport;
use Sports\Place;
use Sports\Poule;
use Sports\Ranking\PointsCalculation;
use Sports\Round;
use Sports\Score\ScoreConfig as ScoreConfig;
use Sports\Qualify\AgainstConfig as AgainstQualifyConfig;
use Sports\Structure\Locations\StructureLocationPlace;
use Sports\Structure\PathNodeConverter;

/**
 * @psalm-type _Place = array{poule: Poule}
 */
class StructureLocationPlaceHandler extends Handler implements SubscribingHandlerInterface
{
    public function __construct(protected DummyCreator $dummyCreator)
    {
    }

    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(StructureLocationPlace::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param array{categoryNr: int, pathNode: string, placeLocation: Place\Location} $fieldValue
     * @param array<string, array> $type
     * @param Context $context
     * @return StructureLocationPlace|null
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): StructureLocationPlace|null {

        $categoryNr = $fieldValue['categoryNr'];
        $pathNode = (new PathNodeConverter())->createPathNode($fieldValue['pathNode']);
        if( $pathNode === null ) {
            return null;
        }
        $placeLocation = $fieldValue['placeLocation'];
        return new StructureLocationPlace( $categoryNr, $pathNode, $placeLocation );
    }

//    /**
//     * @param array<string, int|bool|array<string, int|bool>> $arrConfig
//     * @param CompetitionSport $competitionSport
//     * @param Round $round
//     * @param ScoreConfig|null $previous
//     * @return ScoreConfig
//     */
//    protected function createScoreConfig(
//        array $arrConfig,
//        CompetitionSport $competitionSport,
//        Round $round,
//        ScoreConfig $previous = null
//    ): ScoreConfig {
//        $config = new ScoreConfig(
//            $competitionSport,
//            $round,
//            $arrConfig["direction"],
//            $arrConfig["maximum"],
//            $arrConfig["enabled"],
//            $previous
//        );
//        if (isset($arrConfig["next"])) {
//            $this->createScoreConfig($arrConfig["next"], $competitionSport, $round, $config);
//        }
//        return $config;
//    }
//
//    /**
//     * @param array<string, int|bool|array<string, int|bool|PointsCalculation>> $arrConfig
//     * @param CompetitionSport $competitionSport
//     * @param Round $round
//     * @return AgainstQualifyConfig
//     */
//    protected function createAgainstQualifyConfig(
//        array $arrConfig,
//        CompetitionSport $competitionSport,
//        Round $round
//    ): AgainstQualifyConfig {
//        $config = new AgainstQualifyConfig($competitionSport, $round, $arrConfig["pointsCalculation"]);
//        $config->setWinPoints($arrConfig["winPoints"]);
//        $config->setWinPointsExt($arrConfig["winPointsExt"]);
//        $config->setDrawPoints($arrConfig["drawPoints"]);
//        $config->setDrawPointsExt($arrConfig["drawPointsExt"]);
//        $config->setLosePointsExt($arrConfig["losePointsExt"]);
//        return $config;
//    }
}
