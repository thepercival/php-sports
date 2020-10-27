<?php

namespace Sports\Output;

use Psr\Log\LoggerInterface;
use Sports\Field;
use Sports\Game as GameBase;
use Sports\NameService;
use Sports\Place;
use SportsHelpers\Output as OutputBase;
use Sports\Place\Location\Map as PlaceLocationMap;
use Sports\Ranking\ItemsGetter;
use Sports\Sport\ScoreConfig\Service as SportScoreConfigService;
use Sports\State;

class Game extends OutputBase
{
    /**
     * @var NameService
     */
    protected $nameService;
    /**
     * @var PlaceLocationMap|null
     */
    protected $placeLocationMap;
    /**
     * @var SportScoreConfigService
     */
    private $sportScoreConfigService;

    public function __construct(PlaceLocationMap $placeLocationMap, LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->nameService = new NameService();
        $this->placeLocationMap = $placeLocationMap;
        $this->sportScoreConfigService = new SportScoreConfigService();
    }

    public function output(GameBase $game, string $prefix = null)
    {
        $field = $game->getField();

        $this->logger->info(
            ($prefix !== null ? $prefix : '') .
            $game->getStartDateTime()->format("Y-m-d H:i") . " " .
            $this->getBatchNrAsString($game->getBatchNr()) . " " .
            'poule ' . $game->getPoule()->getNumber()
            . ', ' . $this->getPlacesAsString($game, GameBase::HOME)
            . ' ' . $this->getScoreAsString($game) . ' '
            . $this->getPlacesAsString($game, GameBase::AWAY)
            . ' , ' . $this->getRefereeAsString($game)
            . ', ' . $this->getFieldAsString($field)
            . ', ' . $game->getSportConfig()->getSport()->getName()
            . ' ' . $this->getPointsAsString($game) . ' '
        );
    }

    protected function getBatchNrAsString( int $batchNr ): string {
        $batchColor = $this->useColors() ? ($batchNr % 10) : -1;
        $retVal = 'batch ' . ( $batchNr < 10 ? ' ' : '') . $batchNr;
        return $this->outputColor($batchColor, $retVal);
    }

    protected function getFieldAsString( Field $field = null ): string {
        if( $field === null ) {
            return '';
        }
        $priority = $field->getPriority();
        $fieldColor = $this->useColors() ? ($priority % 10) : -1;
        $retVal = 'field ' . ($priority < 10 ? ' ' : '') . $priority;
        return $this->outputColor($fieldColor, $retVal);
    }

    protected function getPlacesAsString(GameBase $game, bool $homeAway): string
    {
        $placesAsArrayOfStrings = $game->getPlaces($homeAway)->map(
            function (GameBase\Place $gamePlace): string {
                return $this->getPlaceAsString($gamePlace->getPlace());
            }
        )->toArray();
        return implode(' & ', $placesAsArrayOfStrings);
    }

    protected function getPlaceAsString(Place $place): string
    {
        $retVal = $this->nameService->getPlaceFromName( $place, false, false );
        if( $this->placeLocationMap !== null ) {
            $competitor = $this->placeLocationMap->getCompetitor( $place->getStartLocation() );
            if( $competitor !== null ) {
                $retVal .= ' ' . $competitor->getName();
            }
        }
        while( strlen( $retVal ) < 10 ) {
            $retVal .=  ' ';
        }
        if( strlen($retVal) > 10 ) {
            $retVal = substr( $retVal, 0, 10 );
        }
        $useColors = $this->useColors() && $place->getPoule()->getNumber() === 1;
        $placeColor = $useColors ? ($place->getNumber() % 10) : -1;
        return $this->outputColor($placeColor, $retVal);
    }

    protected function getScoreAsString( GameBase $game ): string {
        $score = ' - ';
        if ($game->getState() !== State::Finished) {
            return $score;
        }
        $finalScore = $this->sportScoreConfigService->getFinalScore($game);
        if ($finalScore === null) {
            return $score;
        }
        $retVal = $finalScore->getHome() . $score . $finalScore->getAway();
        if( $game->getFinalPhase() === GameBase::PHASE_EXTRATIME ) {
            $retVal .= ' nv';
        }
        while( strlen( $retVal ) < 10 ) {
            $retVal .=  ' ';
        }
        return $retVal;
    }

    protected function getPointsAsString( GameBase $game ): string {
        $score = ' - ';
        if ($game->getState() !== State::Finished) {
            return $score;
        }
        $itemGetter = new ItemsGetter( $game->getRound(), State::Finished );
        $finalScore = $this->sportScoreConfigService->getFinalScore($game);
        if ($finalScore === null) {
            return $score;
        }
        $homePoints = $itemGetter->getNrOfPoints($finalScore, GameBase::HOME,$game);
        $awayPoints = $itemGetter->getNrOfPoints($finalScore, GameBase::AWAY,$game);
        return $homePoints . 'p' . $score . $awayPoints . 'p';
    }

    protected function getRefereeAsString( GameBase $game ): string {
        $refereeDescription = '';
        if ( $game->getRefereePlace() !== null ) {
            $refereeDescription = $this->nameService->getPlaceFromName( $game->getRefereePlace(), false, false );
        } else if ( $game->getReferee() !== null ) {
            $refereeDescription = $game->getReferee()->getInitials();
        } else {
            return $refereeDescription;
        }
        while( strlen( $refereeDescription ) < 3 ) {
            $refereeDescription .=  ' ';
        }

        $refNr = -1;
        if ( $this->useColors() ) {
            if ( $game->getRefereePlace() !== null ) {
                $refNr = $game->getRefereePlace()->getNumber();
            } else {
                $refNr = $game->getReferee()->getPriority();
            }
        }

        $refereeColor = $this->useColors() ? ($refNr % 10) : -1;
        return $this->outputColor($refereeColor, $refereeDescription);
    }
}
