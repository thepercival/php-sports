<?php

declare(strict_types=1);

namespace Sports\Output;

use Psr\Log\LoggerInterface;
use Sports\Competition\Field;
use Sports\Competition\Referee;
use Sports\Competitor\StartLocationMap;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Game\Together as TogetherGame;
use Sports\Place;
use Sports\Score\ScoreConfigService as ScoreConfigService;
use Sports\Structure\NameService as StructureNameService;
use SportsHelpers\Output as OutputBase;
use SportsHelpers\Output\Color;

abstract class Game extends OutputBase
{
    protected StructureNameService $structureNameService;
    protected StartLocationMap|null $startLocationMap;
    protected ScoreConfigService $scoreConfigService;

    public function __construct(StartLocationMap $startLocationMap = null, LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->structureNameService = new StructureNameService($startLocationMap);
        $this->startLocationMap = $startLocationMap;
        $this->scoreConfigService = new ScoreConfigService();
    }

    protected function getGameRoundNrAsString(int $gameRoundNr): string
    {
        $gameRoundNrColor = $this->useColors() ? ($gameRoundNr % 10) : -1;
        $gameRoundNrColor = $this->convertNumberToColor($gameRoundNrColor);
        $retVal = ($gameRoundNr < 10 ? ' ' : '') . $gameRoundNr;
        return Color::getColored($gameRoundNrColor, $retVal);
    }

    protected function getBatchNrAsString(int $batchNr): string
    {
        $batchColor = $this->useColors() ? ($batchNr % 10) : -1;
        $retVal = 'batch ' . ($batchNr < 10 ? ' ' : '') . $batchNr;
        $batchColor = $this->convertNumberToColor($batchColor);
        return Color::getColored($batchColor, $retVal);
    }

    /**
     * @param list<TogetherGamePlace|AgainstGamePlace> $gamePlaces
     * @return string
     */
    protected function getPlacesAsString(array $gamePlaces): string
    {
        return implode(' & ', array_map(
            function (TogetherGamePlace|AgainstGamePlace $gamePlace): string {
                return $this->getPlaceAsString($gamePlace->getPlace());
            },
            $gamePlaces
        ));
    }

    protected function getFieldAsString(Field $field = null): string
    {
        if ($field === null) {
            return '';
        }
        $priority = $field->getPriority();
        $fieldColor = $this->useColors() ? ($priority % 10) : -1;
        $retVal = 'field ' . ($priority < 10 ? ' ' : '') . $priority;
        $fieldColor = $this->convertNumberToColor($fieldColor);
        return Color::getColored($fieldColor, $retVal);
    }

    protected function getPlaceAsString(Place $place): string
    {
        $retVal = $this->structureNameService->getPlaceFromName($place, false, false);
        $startLocation = $place->getStartLocation();
        if ($this->startLocationMap !== null && $startLocation !== null) {
            $competitor = $this->startLocationMap->getCompetitor($startLocation);
            if ($competitor !== null) {
                $retVal .= ' ' . $competitor->getName();
            }
        }
        while (strlen($retVal) < 10) {
            $retVal .= ' ';
        }
        if (strlen($retVal) > 10) {
            $retVal = substr($retVal, 0, 10);
        }
        $useColors = $this->useColors() && $place->getPoule()->getNumber() === 1;
        $placeColor = $useColors ? ($place->getPlaceNr() % 10) : -1;
        $placeColor = $this->convertNumberToColor($placeColor);
        return Color::getColored($placeColor, $retVal);
    }

    protected function getRefereeAsString(AgainstGame|TogetherGame $game): string
    {
        $refereePlace = $game->getRefereePlace();
        $referee = $game->getReferee();
        if ($referee === null && $refereePlace === null) {
            return '';
        }
        $refereeDescription = $this->getRefereeDescription($referee, $refereePlace);
        $refNr = $this->getRefereeNumber($referee, $refereePlace);


        $refereeColor = $this->useColors() ? ($refNr % 10) : -1;
        $refereeColor = $this->convertNumberToColor($refereeColor);
        return Color::getColored($refereeColor, $refereeDescription);
    }

    protected function getRefereeDescription(Referee|null $referee, Place|null $refPlace): string
    {
        if ($referee === null && $refPlace === null) {
            return '';
        }
        if ($refPlace !== null) {
            $description = $this->structureNameService->getPlaceFromName($refPlace, false, false);
        } else {
            $description = $referee->getInitials();
        }
        while (strlen($description) < 3) {
            $description .=  ' ';
        }
        return $description;
    }

    protected function getRefereeNumber(Referee|null $referee, Place|null $refPlace): int
    {
        $refNr = -1;
        if (!$this->useColors()) {
            return $refNr;
        }
        if ($referee === null && $refPlace === null) {
            return $refNr;
        }
        if ($refPlace !== null) {
            return $refPlace->getPlaceNr();
        }
        return $referee->getPriority();
    }
}
