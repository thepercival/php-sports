<?php

declare(strict_types=1);

namespace Sports\SerializationHandler\Game;

use JMS\Serializer\Context;

use Sports\Game\Against as AgainstGame;
use Sports\Game\Together as TogetherGame;
use Sports\Game\State as GameState;
use Sports\Poule;
use Sports\SerializationHandler\DummyCreator;
use Sports\SerializationHandler\Handler;

/**
 * @psalm-type _FieldValue = array{poule: Poule, batchNr: int, startDateTime: string, competitionSportId: int, fieldId: int, refereeId: int|null, state: string, refereeStructureLocation: string|null}
 */
class GameHandler extends Handler
{
    public function __construct(protected DummyCreator $dummyCreator)
    {

    }

    /**
     * @param AgainstGame|TogetherGame $game
     * @param _FieldValue $fieldValue
     * @param array<string, int|string> $type
     * @param Context $context
     * @return void
     */
    public function setGameProperties(
        AgainstGame|TogetherGame $game,
        array $fieldValue,
        array $type,
        Context $context
    ): void {
        $competition = $this->dummyCreator->createCompetition();
        $competitionSport = $this->dummyCreator->createCompetitionSport(
            $competition, $fieldValue['competitionSportId']
        );

        $game->setState(GameState::from($fieldValue['state']));

        $fieldId = $fieldValue['fieldId'];
        if( $fieldId ) {
            $field = $this->dummyCreator->createField($fieldId, $competitionSport);
            $game->setField($field);
        }

        if( array_key_exists('refereeId', $fieldValue) ) {
            $refereeId = (int)$fieldValue['refereeId'];
            if ($refereeId > 0) {
                $referee = $this->dummyCreator->createReferee($refereeId, $competition);
                $game->setReferee($referee);
            }
        }

        if( array_key_exists('refereeStructureLocation', $fieldValue) ) {
            $refereeStructureLocation = (string)$fieldValue['refereeStructureLocation'];
            if (!empty($refereeStructureLocation)) {
                $round = $game->getPoule()->getRound();
                $refereePlace = $this->dummyCreator->createRefereePlace($refereeStructureLocation, $round);
                $game->setRefereePlace($refereePlace);
            }
        }
    }

    /**
     * @param list<int> $placeNrs
     * @return int
     */
    protected function getMaxPlaceNr(array $placeNrs): int {
        $maxPlaceNr = 1;
        foreach( $placeNrs as $placeNr) {
            if( $placeNr > $maxPlaceNr ) {
                $maxPlaceNr = $placeNr;
            }
        }
        return $maxPlaceNr;
    }

}
