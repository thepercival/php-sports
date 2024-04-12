<?php

declare(strict_types=1);

namespace Sports\SerializationHandler\Game;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;

use Sports\Game\Together as TogetherGame;

use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Score\Together as TogetherScore;
use Sports\SerializationHandler\Handler;

/**
 * @psalm-type _FieldValue = array{game: TogetherGame, id: int, placeNr: int, gameRoundNumber: int, scores: list<TogetherScore>}
 */
class TogetherGamePlaceHandler extends Handler implements SubscribingHandlerInterface
{
    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(TogetherGamePlace::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param _FieldValue $fieldValue
     * @param array<string, int|string> $type
     * @param Context $context
     * @return TogetherGamePlace
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): TogetherGamePlace {
        $game = $fieldValue['game'];
        $place = $game->getPoule()->getPlace( $fieldValue['placeNr'] );

        $gamePlace = new TogetherGamePlace( $game, $place, $fieldValue['gameRoundNumber']);
        $gamePlace->setId($fieldValue['id']);

        foreach ($fieldValue['scores'] as $arrScore) {
            $fieldValue['score'] = $arrScore;
            /** @var TogetherScore $score */
            $score = $this->getProperty(
                $visitor,
                $fieldValue,
                'score',
                TogetherScore::class
            );
            $gamePlace->getScores()->add($score);
        }

        return $gamePlace;
    }
}
