<?php

declare(strict_types=1);

namespace Sports\SerializationHandler\Game;

use JMS\Serializer\Context;
use Sports\Category;
use Sports\Game\Against as AgainstGame;
use Sports\Game\GameState as GameState;
use Sports\Game\Together as TogetherGame;
use Sports\Place;
use Sports\Poule;
use Sports\Round;
use Sports\Structure\StructureCell as StructureCell;
use Sports\SerializationHandler\DummyCreator;
use Sports\SerializationHandler\Handler;
use Sports\Structure\Locations\StructureLocationPlace;
use Sports\Structure\PathNodeConverter;

/**
 * @psalm-type _PlaceLocationArray = array{pouleNr: int, placeNr: int}
 * @psalm-type _StructureLocationPlaceArray = array{categoryNr: int, pathNode: string, placeLocation: _PlaceLocationArray}
 * @psalm-type _FieldValue = array{poule: Poule, batchNr: int, startDateTime: string, competitionSportId: int, fieldId: int, refereeId: int|null, state: string, refereeStructureLocation: _StructureLocationPlaceArray|null}
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

        // hier via serializer aanroepen en dan handler voor StructureLocationPlace

        if( array_key_exists('refereeStructureLocation', $fieldValue) ) {
            $arrRefereeStructureLocation = $fieldValue['refereeStructureLocation'];
            if (is_array($arrRefereeStructureLocation)) {
                $pathNode = (new PathNodeConverter())->createPathNode($arrRefereeStructureLocation['pathNode']);
                if( $pathNode !== null ) {
                    $refereeStructureLocation = new StructureLocationPlace(
                        $arrRefereeStructureLocation['categoryNr'],
                        $pathNode,
                        new Place\Location(
                            $arrRefereeStructureLocation['placeLocation']['pouleNr'],
                            $arrRefereeStructureLocation['placeLocation']['placeNr']
                        )
                    );
                    $refereePlace = $this->dummyCreator->createRefereePlace($refereeStructureLocation);
                    $game->setRefereePlace($refereePlace);
                }
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

    protected function createPoule(int $nrOfPlaces): Poule
    {
        $competition = $this->dummyCreator->createCompetition();
        $category = new Category($competition, Category::DEFAULTNAME);
        $structureCell = new StructureCell($category, $this->dummyCreator->createFirstRoundNumberIfNotExists() );
        $round = new Round($structureCell);
        $poule = new Poule( $round );
        for( $placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++ ) {
            new Place($poule);
        }
        return $poule;
    }

}
