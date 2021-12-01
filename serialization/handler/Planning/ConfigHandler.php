<?php
declare(strict_types=1);

namespace Sports\SerializationHandler\Planning;

use Sports\Planning\Config as PlanningConfig;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\SerializationHandler\DummyCreator;
use Sports\SerializationHandler\Handler;
use SportsHelpers\GameMode;
use SportsHelpers\SelfReferee;
use SportsPlanning\Combinations\GamePlaceStrategy;

class ConfigHandler extends Handler implements SubscribingHandlerInterface
{
    public function __construct(protected DummyCreator $dummyCreator) {
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
    ): PlanningConfig
    {
        if (!isset($fieldValue["roundNumber"])) {
            $fieldValue["roundNumber"] = $this->dummyCreator->createRoundNumber();
        }
        $planningConfig = new PlanningConfig(
            $fieldValue["roundNumber"],
            $fieldValue["editMode"],
            $fieldValue["gamePlaceStrategy"],
            $fieldValue["extension"],
            $fieldValue["enableTime"],
            $fieldValue["minutesPerGame"],
            $fieldValue["minutesPerGameExt"],
            $fieldValue["minutesBetweenGames"],
            $fieldValue["minutesAfter"],
            $fieldValue["selfReferee"]
        );

        return $planningConfig;
    }
}
