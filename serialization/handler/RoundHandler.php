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
     * @param array<string, bool|RoundNumber|QualifyGroup|array> $fieldValue
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
        $round = null;
        if ($parentQualifyGroup instanceof QualifyGroup) {
            $round = $parentQualifyGroup->getChildRound();
        } else {
            $round = new Round($fieldValue["category"], $fieldValue["roundNumber"], null);
        }
        $roundNumber = $round->getNumber();

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

        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null && isset($fieldValue["qualifyGroups"])) {
            foreach ($fieldValue["qualifyGroups"] as $arrQualifyGroup) {
                $fieldValue["qualifyGroup"] = $arrQualifyGroup;
                $fieldValue["qualifyGroup"]["parentRound"] = $round;
                $fieldValue["qualifyGroup"]["nextRoundNumber"] = $nextRoundNumber;
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
     * @param array<string, int|bool|array<string, float|PointsCalculation>> $arrConfig
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
