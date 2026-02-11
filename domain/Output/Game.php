<?php

declare(strict_types=1);

namespace Sports\Output;

use Psr\Log\LoggerInterface;
use Sports\Competition\CompetitionField;
use Sports\Competition\CompetitionReferee;
use Sports\Competitor\StartLocationMap;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Game\Together as TogetherGame;
use Sports\Structure\NameService as StructureNameService;
use Sports\Place;
use Sports\Score\Config\Service as ScoreConfigService;
use SportsHelpers\Output\Color;
use SportsHelpers\Output\OutputAbstract;

abstract class Game extends OutputAbstract
{
    protected StructureNameService $structureNameService;
    protected StartLocationMap|null $startLocationMap;
    protected ScoreConfigService $scoreConfigService;

    public function __construct(StartLocationMap $startLocationMap = null, LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->structureNameService = new StructureNameService($startLocationMap);
        $this->startLocationMap = $startLocationMap;
        $this->scoreConfigService = new ScoreConfigService();
    }

    protected function getGameRoundNrAsString(int $gameRoundNr): string
    {
        $gameRoundNrColor = ($gameRoundNr % 10);
        $gameRoundNrColor = $this->convertNumberToColor($gameRoundNrColor);
        $retVal = ($gameRoundNr < 10 ? ' ' : '') . $gameRoundNr;
        return $this->getColoredString($gameRoundNrColor, $retVal);
    }

    protected function getBatchNrAsString(int $batchNr): string
    {
        $batchColor = ($batchNr % 10);
        $retVal = 'batch ' . ($batchNr < 10 ? ' ' : '') . $batchNr;
        $batchColor = $this->convertNumberToColor($batchColor);
        return $this->getColoredString($batchColor, $retVal);
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

    protected function getFieldAsString(CompetitionField $field = null): string
    {
        if ($field === null) {
            return '';
        }
        $priority = $field->getPriority();
        $fieldColor = ($priority % 10);
        $retVal = 'field ' . ($priority < 10 ? ' ' : '') . $priority;
        $fieldColor = $this->convertNumberToColor($fieldColor);
        return $this->getColoredString($fieldColor, $retVal);
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
        $placeColor = ($place->getPlaceNr() % 10);
        $placeColor = $this->convertNumberToColor($placeColor);
        return $this->getColoredString($placeColor, $retVal);
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


        $refereeColor = ($refNr % 10);
        $refereeColor = $this->convertNumberToColor($refereeColor);
        return $this->getColoredString($refereeColor, $refereeDescription);
    }

    protected function getRefereeDescription(CompetitionReferee|null $referee, Place|null $refPlace): string
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

    protected function getRefereeNumber(CompetitionReferee|null $referee, Place|null $refPlace): int
    {
        $refNr = -1;
        if ($referee === null && $refPlace === null) {
            return $refNr;
        }
        if ($refPlace !== null) {
            return $refPlace->getPlaceNr();
        }
        return $referee->getPriority();
    }
}
