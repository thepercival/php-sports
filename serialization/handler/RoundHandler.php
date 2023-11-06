<?php

declare(strict_types=1);

namespace Sports\SerializationHandler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Poule;
use Sports\Ranking\PointsCalculation;
use Sports\Round;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Round\Number as RoundNumber;
use Sports\Score\Config as ScoreConfig;
use Sports\Qualify\AgainstConfig as AgainstQualifyConfig;
use Sports\Structure\Cell;

/**
 * @psalm-type _Sport = array{id: int|string}
 * @psalm-type _CompetitionSport = array{id: int|string, sport: _Sport}
 * @psalm-type _AgainstQualifyConfig = array{pointsCalculation: int, winPoints: float, drawPoints: float, winPointsExt: float, drawPointsExt: float, losePointsExt: float, competitionSport: _CompetitionSport}
 * @psalm-type _ScoreConfig = array{direction: int, maximum: int, enabled: bool, competitionSport: _CompetitionSport}
 * @psalm-type _ScoreConfigFieldValue = array{direction: int, maximum: int, enabled: bool, competitionSport: _CompetitionSport, next: _ScoreConfig|null}
 * @psalm-type _Poule = array{round: Round}
 * @psalm-type _QualifyGroup = array{parentRound: Round, nextStructureCell: Cell}
 * @psalm-type _FieldValue = array{parentQualifyGroup: QualifyGroup|null, structureCell: Cell, poules: list<_Poule>, qualifyGroups: list<_QualifyGroup>, againstQualifyConfigs: list<_AgainstQualifyConfig>, scoreConfigs: list<_ScoreConfigFieldValue>}
 */
class RoundHandler extends Handler implements SubscribingHandlerInterface
{
    public function __construct(protected DummyCreator $dummyCreator)
    {
    }

    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(Round::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param _FieldValue $fieldValue
     * @param array<string, array<string, RoundNumber>> $type
     * @param Context $context
     * @return Round
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): Round {
        $parentQualifyGroup = null;
        if (isset($fieldValue["parentQualifyGroup"])) {
            $parentQualifyGroup = $fieldValue["parentQualifyGroup"];
        }
        if ($parentQualifyGroup instanceof QualifyGroup) {
            $round = $parentQualifyGroup->getChildRound();
        } else {
            $round = new Round($fieldValue["structureCell"], null);
        }
        $structureCell = $round->getStructureCell();

        if (isset($fieldValue["scoreConfigs"])) {
            foreach ($fieldValue["scoreConfigs"] as $arrScoreConfig) {
                $competitionSport = $this->dummyCreator->createCompetitionSport(
                    $round->getCompetition(),
                    (int) $arrScoreConfig["competitionSport"]["id"],
                    (int) $arrScoreConfig["competitionSport"]["sport"]["id"]
                );
                $this->createScoreConfig($arrScoreConfig, $competitionSport, $round);
            }
        }
        if (isset($fieldValue["againstQualifyConfigs"])) {
            foreach ($fieldValue["againstQualifyConfigs"] as $arrAgainstQualifyConfig) {
                $competitionSport = $this->dummyCreator->createCompetitionSport(
                    $round->getCompetition(),
                    (int) $arrAgainstQualifyConfig["competitionSport"]["id"],
                    (int) $arrAgainstQualifyConfig["competitionSport"]["sport"]["id"]
                );
                $this->createAgainstQualifyConfig($arrAgainstQualifyConfig, $competitionSport, $round);
            }
        }

        foreach ($fieldValue["poules"] as $arrPoule) {
            $fieldValue["poule"] = $arrPoule;
            $fieldValue["poule"]["round"] = $round;
            $this->getProperty(
                $visitor,
                $fieldValue,
                "poule",
                Poule::class
            );
        }

        $nextStructureCell = $structureCell->getNext();
        if ($nextStructureCell !== null && isset($fieldValue["qualifyGroups"])) {
            foreach ($fieldValue["qualifyGroups"] as $arrQualifyGroup) {
                $fieldValue["qualifyGroup"] = $arrQualifyGroup;
                $fieldValue["qualifyGroup"]["parentRound"] = $round;
                $fieldValue["qualifyGroup"]["nextStructureCell"] = $nextStructureCell;
                $this->getProperty(
                    $visitor,
                    $fieldValue,
                    "qualifyGroup",
                    QualifyGroup::class
                );
            }
        }
        return $round;
    }

    /**
     * @param _ScoreConfigFieldValue $arrScoreConfig
     * @param CompetitionSport $competitionSport
     * @param Round $round
     * @param ScoreConfig|null $previous
     * @return ScoreConfig
     */
    protected function createScoreConfig(
        array $arrScoreConfig,
        CompetitionSport $competitionSport,
        Round $round,
        ScoreConfig $previous = null
    ): ScoreConfig {
        $config = new ScoreConfig(
            $competitionSport,
            $round,
            $arrScoreConfig["direction"],
            $arrScoreConfig["maximum"],
            $arrScoreConfig["enabled"],
            $previous
        );
        if (isset($arrScoreConfig["next"])) {
            /** @psalm-suppress InvalidArgument */
            $this->createScoreConfig($arrScoreConfig["next"], $competitionSport, $round, $config);
        }
        return $config;
    }

    /**
     * @param _AgainstQualifyConfig $arrConfig
     * @param CompetitionSport $competitionSport
     * @param Round $round
     * @return AgainstQualifyConfig
     */
    protected function createAgainstQualifyConfig(
        array $arrConfig,
        CompetitionSport $competitionSport,
        Round $round
    ): AgainstQualifyConfig {
        return new AgainstQualifyConfig(
            $competitionSport,
            $round,
            PointsCalculation::from($arrConfig['pointsCalculation']),
            $arrConfig['winPoints'],
            $arrConfig['drawPoints'],
            $arrConfig['winPointsExt'],
            $arrConfig['drawPointsExt'],
            $arrConfig['losePointsExt']
        );
    }
}
