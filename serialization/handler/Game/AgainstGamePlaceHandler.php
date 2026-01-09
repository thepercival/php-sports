<?php

declare(strict_types=1);

namespace Sports\SerializationHandler\Game;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;

use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\SerializationHandler\Handler;
use SportsHelpers\Against\AgainstSide;

/**
 * @psalm-type _FieldValue = array{game: AgainstGame, id: int, side: string, placeNr: int}
 */
final class AgainstGamePlaceHandler extends Handler implements SubscribingHandlerInterface
{
    #[\Override]
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(AgainstGamePlace::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param _FieldValue $fieldValue
     * @param array<string, int|string> $type
     * @param Context $context
     * @return AgainstGamePlace
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): AgainstGamePlace {
        $game = $fieldValue['game'];
        $place = $game->getPoule()->getPlace( $fieldValue['placeNr'] );

        $gamePlace = new AgainstGamePlace( $game, $place, AgainstSide::from($fieldValue['side']));
        $gamePlace->id = $fieldValue['id'];
        return $gamePlace;
    }
}
