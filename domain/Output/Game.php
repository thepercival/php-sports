<?php
declare(strict_types=1);

namespace Sports\Output;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use Sports\Competition\Field;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\NameService;
use Sports\Place;
use SportsHelpers\Output as OutputBase;
use Sports\Place\Location\Map as PlaceLocationMap;
use Sports\Ranking\ItemsGetter\Against as AgainstItemsGetter;
use Sports\Sport\ScoreConfig\Service as SportScoreConfigService;
use Sports\State;

abstract class Game extends OutputBase
{
    protected NameService $nameService;
    /**
     * @var PlaceLocationMap|null
     */
    protected $placeLocationMap;
    protected SportScoreConfigService $sportScoreConfigService;

    public function __construct(PlaceLocationMap $placeLocationMap = null, LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->nameService = new NameService();
        $this->placeLocationMap = $placeLocationMap;
        $this->sportScoreConfigService = new SportScoreConfigService();
    }

    /**
     * @param AgainstGame|TogetherGame $game
     * @param string|null $prefix
     */
    public function output($game, string $prefix = null)
    {
        $field = $game->getField();

        $this->logger->info(
            ($prefix !== null ? $prefix : '') .
            $game->getStartDateTime()->format("Y-m-d H:i") . " " .
            $this->getBatchNrAsString($game->getBatchNr()) . " " .
            'poule ' . $game->getPoule()->getNumber()
            . ', ' . $this->getDescriptionAsString($game)
            . ' , ' . $this->getRefereeAsString($game)
            . ', ' . $this->getFieldAsString($field)
            . ', ' . $game->getCompetitionSport()->getSport()->getName()
            . ' ' . $this->getPointsAsString($game) . ' '
        );
    }

    /**
     * @param AgainstGame|TogetherGame $game
     * @return string
     */
    abstract protected function getDescriptionAsString($game): string;
    /**
     * @param AgainstGame|TogetherGame $game
     * @return string
     */
    abstract protected function getScoreAsString( $game ): string;
    /**
     * @param AgainstGame|TogetherGame $game
     * @return string
     */
    abstract protected function getPointsAsString( $game ): string;

    protected function getBatchNrAsString( int $batchNr ): string {
        $batchColor = $this->useColors() ? ($batchNr % 10) : -1;
        $retVal = 'batch ' . ( $batchNr < 10 ? ' ' : '') . $batchNr;
        return $this->outputColor($batchColor, $retVal);
    }

    /**
     * @param Collection|TogetherGamePlace[]|AgainstGamePlace[] $gamePlaces
     * @return string
     */
    protected function getPlacesAsString($gamePlaces): string
    {
        return implode(' & ', $gamePlaces->map(
            function (AgainstGamePlace $gamePlace): string {
                return $this->getPlaceAsString($gamePlace->getPlace());
            }
        )->toArray() );
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

    /**
     * @param AgainstGame|TogetherGame $game
     * @return string
     */
    protected function getRefereeAsString( $game ): string {
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
