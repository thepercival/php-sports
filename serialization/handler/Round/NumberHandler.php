<?php

declare(strict_types=1);

namespace Sports\SerializationHandler\Round;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use Sports\Association;
use Sports\Competition;
use Sports\League;
use Sports\Round\Number as RoundNumber;
use Sports\Season;
use Sports\SerializationHandler\DummyCreator;
use Sports\SerializationHandler\Handler;
use Sports\Planning\GameAmountConfig;
use Sports\Planning\Config as PlanningConfig;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Structure\Cell;

/**
 * @psalm-type _Sport = array{id: int|string}
 * @psalm-type _CompetitionSport = array{id: int|string, sport: _Sport}
 * @psalm-type _PlanningConfig = array{roundNumber: RoundNumber}
 * @psalm-type _GameAmountConfig = array{amount: int, competitionSportId: int}
 * @psalm-type _RoundNumber = array{previous: RoundNumber|null, planningConfig: _PlanningConfig|null, gameAmountConfigs: list<_GameAmountConfig>}
 * @psalm-type _FieldValue = array{previous: RoundNumber|null, planningConfig: _PlanningConfig|null, gameAmountConfigs: list<_GameAmountConfig>, next: _RoundNumber|null}
 *
 **/
class NumberHandler extends Handler implements SubscribingHandlerInterface
{
    public function __construct(protected DummyCreator $dummyCreator)
    {
    }

    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(RoundNumber::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param _FieldValue $fieldValue
     * @param array<string, array<string, RoundNumber>> $type
     * @param Context $context
     * @return RoundNumber
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): RoundNumber {
        if (isset($fieldValue["previous"])) {
            $roundNumber = $fieldValue["previous"]->createNext();
        } else {
            $roundNumber = new RoundNumber($this->dummyCreator->createCompetition(), null);
        }

        if (isset($fieldValue["planningConfig"])) {
            $fieldValue["planningConfig"]["roundNumber"] = $roundNumber;
            $this->getProperty(
                $visitor,
                $fieldValue,
                "planningConfig",
                PlanningConfig::class
            );
        }

        if (isset($fieldValue["gameAmountConfigs"])) {
            foreach ($fieldValue["gameAmountConfigs"] as $arrGameAmountConfig) {
                $competitionSport = $this->dummyCreator->createCompetitionSport(
                    $roundNumber->getCompetition(),
                    $arrGameAmountConfig["competitionSportId"]
                );
                new GameAmountConfig(
                    $competitionSport,
                    $roundNumber,
                    $arrGameAmountConfig["amount"]
                );
            }
        }

        if (isset($fieldValue["next"])) {
            $fieldValue["next"]["previous"] = $roundNumber;
            $this->getProperty(
                $visitor,
                $fieldValue,
                "next",
                RoundNumber::class
            );
        }

        return $roundNumber;
    }
}
