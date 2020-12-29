<?php

namespace Sports\Output;

use Psr\Log\LoggerInterface;
use Sports\Competition\Field;
use Sports\Game\Against as AgainstGameBase;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\NameService;
use Sports\Place;
use SportsHelpers\Output as OutputBase;
use Sports\Place\Location\Map as PlaceLocationMap;
use Sports\Ranking\ItemsGetter\Against as AgainstItemsGetter;
use Sports\Sport\ScoreConfig\Service as SportScoreConfigService;
use Sports\State;

class AgainstGame extends OutputBase
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

    public function __construct(PlaceLocationMap $placeLocationMap = null, LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->nameService = new NameService();
        $this->placeLocationMap = $placeLocationMap;
        $this->sportScoreConfigService = new SportScoreConfigService();
    }

    public function output(AgainstGameBase $game, string $prefix = null)
    {
        $field = $game->getField();

        $this->logger->info(
            ($prefix !== null ? $prefix : '') .
            $game->getStartDateTime()->format("Y-m-d H:i") . " " .
            $this->getBatchNrAsString($game->getBatchNr()) . " " .
            'poule ' . $game->getPoule()->getNumber()
            . ', ' . $this->getPlacesAsString($game, AgainstGameBase::HOME)
            . ' ' . $this->getScoreAsString($game) . ' '
            . $this->getPlacesAsString($game, AgainstGameBase::AWAY)
            . ' , ' . $this->getRefereeAsString($game)
            . ', ' . $this->getFieldAsString($field)
            . ', ' . $game->getCompetitionSport()->getSport()->getName()
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

    protected function getPlacesAsString(AgainstGameBase $game, bool $homeAway): string
    {
        $placesAsArrayOfStrings = $game->getPlaces($homeAway)->map(
            function (AgainstGamePlace $gamePlace): string {
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

    protected function getScoreAsString( AgainstGameBase $game ): string {
        $score = ' - ';
        if ($game->getState() !== State::Finished) {
            return $score;
        }
        $finalScore = $this->sportScoreConfigService->getFinalAgainstScore($game);
        if ($finalScore === null) {
            return $score;
        }
        $retVal = $finalScore->getHomeScore() . $score . $finalScore->getAwayScore();
        if( $game->getFinalPhase() === AgainstGameBase::PHASE_EXTRATIME ) {
            $retVal .= ' nv';
        }
        while( strlen( $retVal ) < 10 ) {
            $retVal .=  ' ';
        }
        return $retVal;
    }

    protected function getPointsAsString( AgainstGameBase $game ): string {
        $score = ' - ';
        if ($game->getState() !== State::Finished) {
            return $score;
        }
        $itemGetter = new AgainstItemsGetter( $game->getRound(), State::Finished );
        $finalScore = $this->sportScoreConfigService->getFinalAgainstScore($game);
        if ($finalScore === null) {
            return $score;
        }
        $homePoints = $itemGetter->getNrOfPoints($finalScore, AgainstGameBase::HOME,$game);
        $awayPoints = $itemGetter->getNrOfPoints($finalScore, AgainstGameBase::AWAY,$game);
        return $homePoints . 'p' . $score . $awayPoints . 'p';
    }

    protected function getRefereeAsString( AgainstGameBase $game ): string {
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
