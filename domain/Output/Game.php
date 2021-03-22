<?php
declare(strict_types=1);

namespace Sports\Output;

use Psr\Log\LoggerInterface;
use Sports\Competition\Field;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\NameService;
use Sports\Place;
use SportsHelpers\Output as OutputBase;
use Sports\Competitor\Map as CompetitorMap;
use Sports\Competition\Referee;
use Sports\Score\Config\Service as ScoreConfigService;

abstract class Game extends OutputBase
{
    protected NameService $nameService;
    protected CompetitorMap|null $competitorMap;
    protected ScoreConfigService $scoreConfigService;

    public function __construct(CompetitorMap $competitorMap = null, LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->nameService = new NameService();
        $this->competitorMap = $competitorMap;
        $this->scoreConfigService = new ScoreConfigService();
    }

    protected function getBatchNrAsString(int $batchNr): string
    {
        $batchColor = $this->useColors() ? ($batchNr % 10) : -1;
        $retVal = 'batch ' . ($batchNr < 10 ? ' ' : '') . $batchNr;
        return $this->outputColor($batchColor, $retVal);
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
        return $this->outputColor($fieldColor, $retVal);
    }

    protected function getPlaceAsString(Place $place): string
    {
        $retVal = $this->nameService->getPlaceFromName($place, false, false);
        if ($this->competitorMap !== null) {
            $competitor = $this->competitorMap->getCompetitor($place->getStartLocation());
            if ($competitor !== null) {
                $retVal .= ' ' . $competitor->getName();
            }
        }
        while (strlen($retVal) < 10) {
            $retVal .=  ' ';
        }
        if (strlen($retVal) > 10) {
            $retVal = substr($retVal, 0, 10);
        }
        $useColors = $this->useColors() && $place->getPoule()->getNumber() === 1;
        $placeColor = $useColors ? ($place->getNumber() % 10) : -1;
        return $this->outputColor($placeColor, $retVal);
    }

    protected function getRefereeAsString(AgainstGame|TogetherGame $game): string
    {
        $refereePlace = $game->getRefereePlace();
        $referee = $game->getReferee();
        if ($referee === null && $refereePlace === null) {
            return '';
        }
        $refereeDescription = $this->getRefereeDescription($referee, $refereePlace);
        $refNr = -1;
        if ($this->useColors()) {
            if ($refereePlace !== null) {
                $refNr = $refereePlace->getNumber();
            } else if ($referee !== null) {
                $refNr = $referee->getPriority();
            }
        }

        $refereeColor = $this->useColors() ? ($refNr % 10) : -1;
        return $this->outputColor($refereeColor, $refereeDescription);
    }

    protected function getRefereeDescription(Referee|null $referee, Place|null $refPlace): string
    {
        if ($referee === null && $refPlace === null) {
            return '';
        }
        $description = '';
        if ($refPlace !== null) {
            $description = $this->nameService->getPlaceFromName($refPlace, false, false);
        } else {
            /** @phpstan-ignore-next-line  */
            $description = $referee->getInitials();
        }
        while (strlen($description) < 3) {
            $description .=  ' ';
        }
        return $description;
    }
}
