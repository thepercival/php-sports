<?php

declare(strict_types=1);

namespace Sports\SerializationHandler\Game;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;

use Sports\Game\Together as TogetherGame;

use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Poule;
use Sports\SerializationHandler\DummyCreator;

/**
 * @psalm-type _TogetherGamePlace = array{placeNr: int, gameRoundNumber: int}
 * @psalm-type _FieldValue = array{poule: Poule, batchNr: int, startDateTime: string, competitionSportId: int, fieldId: int, refereeId: int, state: string, refereeStructureLocation: string, places: list<_TogetherGamePlace>}
 */
class TogetherGameHandler extends GameHandler implements SubscribingHandlerInterface
{
    public function __construct(DummyCreator $dummyCreator)
    {
        parent::__construct($dummyCreator);
    }

    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(TogetherGame::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param _FieldValue $fieldValue
     * @param array<string, int|string> $type
     * @param Context $context
     * @return TogetherGame
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): TogetherGame {
        $arrPlaceNrs = array_map( function(array $arrGamePlace): int {
            return $arrGamePlace['placeNr'];
        }, $fieldValue['places'] );
        $maxPlaceNr = $this->getMaxPlaceNr($arrPlaceNrs);
        $poule = $this->dummyCreator->createPoule($maxPlaceNr);

        $competition = $this->dummyCreator->createCompetition();
        $competitionSport = $this->dummyCreator->createCompetitionSport(
            $competition, $fieldValue['competitionSportId']
        );

        $game = new TogetherGame(
            $poule,
            $fieldValue['batchNr'],
            new \DateTimeImmutable($fieldValue['startDateTime']),
            $competitionSport );

        $arrPlaces = $fieldValue['places'];
        unset($fieldValue['places']);
        $this->setGameProperties($game, $fieldValue, $type, $context);

        // _GameProps
        foreach ($arrPlaces as $arrGamePlace) {
            $fieldValue['gamePlace'] = $arrGamePlace;
            $fieldValue['gamePlace']['game'] = $game;
            $this->getProperty(
                $visitor,
                $fieldValue,
                'gamePlace',
                TogetherGamePlace::class
            );
        }

        return $game;
    }
}
