<?php

declare(strict_types=1);

namespace Sports\SerializationHandler\Planning;

use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use Sports\Planning\PlanningConfig as PlanningConfig;
use Sports\Planning\EditMode;
use Sports\Round\Number as RoundNumber;
use Sports\SerializationHandler\DummyCreator;
use Sports\SerializationHandler\Handler;
use SportsHelpers\SelfReferee;

final class ConfigHandler extends Handler implements SubscribingHandlerInterface
{
    public function __construct(protected DummyCreator $dummyCreator)
    {
    }

    #[\Override]
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(PlanningConfig::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param array{roundNumber: RoundNumber, editMode: string, extension: bool, enableTime: bool, minutesPerGame: int, minutesPerGameExt: int, minutesBetweenGames: int, minutesAfter: int, perPoule: bool, selfReferee: string, nrOfSimSelfRefs: int, bestLast: bool} $fieldValue
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
        if (!isset($fieldValue['roundNumber'])) {
            $fieldValue['roundNumber'] = $this->dummyCreator->createFirstRoundNumberIfNotExists();
        }
        return new PlanningConfig(
            $fieldValue['roundNumber'],
            EditMode::from($fieldValue['editMode']),
            $fieldValue['extension'],
            $fieldValue['enableTime'],
            $fieldValue['minutesPerGame'],
            $fieldValue['minutesPerGameExt'],
            $fieldValue['minutesBetweenGames'],
            $fieldValue['minutesAfter'],
            $fieldValue['perPoule'],
            SelfReferee::from($fieldValue['selfReferee']),
            $fieldValue['nrOfSimSelfRefs'],
            $fieldValue['bestLast']
        );
    }
}
