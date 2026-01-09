<?php

declare(strict_types=1);

namespace Sports\SerializationHandler\Game;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;

use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Poule;
use Sports\Score\AgainstScore as AgainstScore;
use Sports\SerializationHandler\DummyCreator;

/**
 * @psalm-type _AgainstGamePlace = array{placeNr: int, side: string}
 * @psalm-type _PlaceLocationArray = array{pouleNr: int, placeNr: int}
 * @psalm-type _StructureLocationPlaceArray = array{categoryNr: int, pathNode: string, placeLocation: _PlaceLocationArray}
 * @psalm-type _FieldValue = array{poule: Poule, batchNr: int, startDateTime: string, competitionSportId: int, cycleNr: int, cyclePartNr: int, fieldId: int, refereeId: int, state: string, refereeStructureLocation: _StructureLocationPlaceArray|null, places: list<_AgainstGamePlace>, scores: list<AgainstScore>}
 */
final class AgainstGameHandler extends GameHandler implements SubscribingHandlerInterface
{
    public function __construct(DummyCreator $dummyCreator)
    {
        parent::__construct($dummyCreator);
    }

    #[\Override]
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(AgainstGame::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param _FieldValue $fieldValue
     * @param array<string, int|string> $type
     * @param Context $context
     * @return AgainstGame
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): AgainstGame {
        $arrPlaceNrs = array_map( function(array $arrGamePlace): int {
            return $arrGamePlace['placeNr'];
        }, $fieldValue['places'] );
        $maxPlaceNr = $this->getMaxPlaceNr($arrPlaceNrs);
        $poule = $this->createPoule($maxPlaceNr);

        $competition = $this->dummyCreator->createCompetition();
        $competitionSport = $this->dummyCreator->createCompetitionSport(
            $competition, $fieldValue['competitionSportId']
        );

        $game = new AgainstGame(
            $poule,
            $fieldValue['batchNr'],
            new \DateTimeImmutable($fieldValue['startDateTime']),
            $competitionSport,
            $fieldValue['cycleNr'],
            $fieldValue['cyclePartNr']);

        $arrPlaces = $fieldValue['places'];
        unset($fieldValue['places']);
        $arrScores = $fieldValue['scores'];
        unset($fieldValue['scores']);
        unset($fieldValue['cycleNr']);
        unset($fieldValue['cyclePartNr']);
        $this->setGameProperties($game, $fieldValue, $type, $context);

        // _GameProps
        foreach ($arrPlaces as $arrGamePlace) {
            $fieldValue['gamePlace'] = $arrGamePlace;
            $fieldValue['gamePlace']['game'] = $game;
            $this->getProperty(
                $visitor,
                $fieldValue,
                'gamePlace',
                AgainstGamePlace::class
            );
        }

        foreach ($arrScores as $arrScore) {
            $fieldValue['score'] = $arrScore;
            /** @var AgainstScore $score */
            $score = $this->getProperty(
                $visitor,
                $fieldValue,
                'score',
                AgainstScore::class
            );
            $game->getScores()->add($score);
        }

        return $game;
    }
}
