<?php

declare(strict_types=1);

namespace Sports\SerializationHandler\Planning;

use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use Sports\Planning\Config as PlanningConfig;
use Sports\Planning\EditMode;
use Sports\Round\Number as RoundNumber;
use Sports\SerializationHandler\DummyCreator;
use Sports\SerializationHandler\Handler;
use SportsHelpers\GameMode;
use SportsHelpers\SelfReferee;
use SportsPlanning\Combinations\GamePlaceStrategy;

class ConfigHandler extends Handler implements SubscribingHandlerInterface
{
    public function __construct(protected DummyCreator $dummyCreator)
    {
    }

    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(PlanningConfig::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param array<string, int|bool|RoundNumber|GameMode|SelfReferee|GamePlaceStrategy> $fieldValue
     * @param array<string, int|string> $type
     * @param Context $context
     * @return PlanningConfig
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): PlanningConfig {
        if (!isset($fieldValue["roundNumber"])) {
            $fieldValue["roundNumber"] = $this->dummyCreator->createRoundNumber();
        }
        $planningConfig = new PlanningConfig(
            $fieldValue["roundNumber"],
            EditMode::from($fieldValue["editMode"]),
            GamePlaceStrategy::from($fieldValue["gamePlaceStrategy"]),
            $fieldValue["extension"],
            $fieldValue["enableTime"],
            $fieldValue["minutesPerGame"],
            $fieldValue["minutesPerGameExt"],
            $fieldValue["minutesBetweenGames"],
            $fieldValue["minutesAfter"],
            SelfReferee::from($fieldValue["selfReferee"])
        );

        return $planningConfig;
    }
}
