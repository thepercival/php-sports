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
     * @param array<TogetherGamePlace|AgainstGamePlace> $gamePlaces
     * @return string
     */
    protected function getPlacesAsString(array $gamePlaces): string
    {
        return implode(' & ', array_map( function (AgainstGamePlace $gamePlace): string {
                return $this->getPlaceAsString($gamePlace->getPlace());
            }, $gamePlaces
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
        $refereeDescription = '';
        if ($game->getRefereePlace() !== null) {
            $refereeDescription = $this->nameService->getPlaceFromName($game->getRefereePlace(), false, false);
        } elseif ($game->getReferee() !== null) {
            $refereeDescription = $game->getReferee()->getInitials();
        } else {
            return $refereeDescription;
        }
        while (strlen($refereeDescription) < 3) {
            $refereeDescription .=  ' ';
        }

        $refNr = -1;
        if ($this->useColors()) {
            if ($game->getRefereePlace() !== null) {
                $refNr = $game->getRefereePlace()->getNumber();
            } else {
                $refNr = $game->getReferee()->getPriority();
            }
        }

        $refereeColor = $this->useColors() ? ($refNr % 10) : -1;
        return $this->outputColor($refereeColor, $refereeDescription);
    }
}
