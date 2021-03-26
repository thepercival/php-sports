<?php
declare(strict_types=1);

namespace Sports\Output\StructureOutput;

use Sports\Output\Grid\Drawer;
use Sports\Output\Coordinate;
use Sports\NameService;
use Sports\Poule;
use Sports\Round;
use Sports\Structure;
use Sports\Round\Number as RoundNumber;
use Sports\Qualify\Group as QualifyGroup;

final class DrawHelper
{
    protected NameService $nameService;
    protected RangeCalculator $rangeCalculator;

    public function __construct(protected Drawer $drawer)
    {
        $this->nameService = new NameService();
        $this->rangeCalculator = new RangeCalculator();
    }

    public function drawStructure(Structure $structure, Coordinate $origin): Coordinate
    {
        $roundNumberHeight = $this->rangeCalculator->getRoundNumberHeight($structure->getFirstRoundNumber());
        $roundCoordinate = $this->getRoundStartCoordinate($origin, $structure->getFirstRoundNumber(), $structure);
        $rounds = [$structure->getRootRound()];
        foreach ($rounds as $round) {
            $roundCoordinate = $this->drawRound($round, $roundCoordinate, $roundNumberHeight);
        }
        return $roundCoordinate;
    }

    protected function getRoundStartCoordinate(Coordinate $origin, RoundNumber $roundNumber, Structure $structure): Coordinate
    {
        $structureWidth = $this->rangeCalculator->getStructureWidth($structure);
        $roundNumberWidth = $this->rangeCalculator->getRoundNumberWidth($roundNumber);
        $delta = (int)floor(($structureWidth - $roundNumberWidth) / 2);
        return $origin->addX($delta);
    }

    public function drawRound(Round $round, Coordinate $origin, int $roundNumberHeight): Coordinate
    {
        $this->drawRoundBorder($round, $origin, $roundNumberHeight);

        $pouleCoordinate = $this->getPoulesStartCoordinate($origin, $round);
        foreach ($round->getPoules() as $poule) {
            $pouleCoordinate = $this->drawPoule($poule, $pouleCoordinate);
        }

        $this->drawRoundBorder($round, $origin, $roundNumberHeight);
        $nextRoundNumber = $round->getNumber()->getNext();
        if ($nextRoundNumber !== null) {
            $nextRoundNumberHeight = $this->rangeCalculator->getRoundNumberHeight($nextRoundNumber);
            $this->drawQualifyGroups($round, $origin->addY($roundNumberHeight), $nextRoundNumberHeight);
        }
        $roundWidth = $this->rangeCalculator->getRoundWidth($round);
        return $origin->addX($roundWidth + RangeCalculator::PADDING);


//        $batchColor = $this->useColors() ? ($batchNr % 10) : -1;
//        $retVal = 'batch ' . ($batchNr < 10 ? ' ' : '') . $batchNr;
//        return $this->outputColor($batchColor, $retVal);
    }

    protected function getPoulesStartCoordinate(Coordinate $origin, Round $round): Coordinate
    {
        $newCoordinate = $origin->add(RangeCalculator::BORDER, RangeCalculator::BORDER);

        $innerRoundWidth = $this->rangeCalculator->getRoundWidth($round) - (2 * RangeCalculator::BORDER);
        $poulesWidth = $this->rangeCalculator->getPoulesWidth($round->getPoules());
        $delta = (int)floor(($innerRoundWidth - $poulesWidth) / 2);
        return $newCoordinate->addX($delta);
    }

    protected function drawRoundBorder(Round $round, Coordinate $origin, int $roundNumberHeight): void
    {
        $width = $this->rangeCalculator->getRoundWidth($round);

        $topLeft = $origin->addX($width-1);
        $this->drawer->drawVerticalLine($topLeft, $roundNumberHeight);
        $bottomLeft = $this->drawer->drawVerticalLine($origin, $roundNumberHeight);
        $this->drawer->drawHorizontalLine($origin, $width);
        $this->drawer->drawHorizontalLine($bottomLeft, $width);
    }

    protected function drawPoule(Poule $poule, Coordinate $origin): Coordinate
    {
        $pouleWidth = $this->rangeCalculator->getPouleWidth($poule);
        $pouleName = $this->nameService->getPouleName($poule, false);
        $nextPouleCoordrinate = $this->drawer->drawHorizontalCell($origin, $pouleName, $pouleWidth, Drawer::ALIGN_CENTER);

        $this->drawer->drawHorizontalLine($origin->addY(1), $pouleWidth);

        $placeCoordinate = $origin->addY(2);
        foreach ($poule->getPlaces() as $place) {
            $placeName = $this->nameService->getPlaceFromName($place, false);
            $this->drawer->drawHorizontalCell($placeCoordinate, $placeName, $pouleWidth, Drawer::ALIGN_CENTER);
            $placeCoordinate = $placeCoordinate->incrementY();
        }

        return $nextPouleCoordrinate->addX(RangeCalculator::PADDING + 1);
    }

    protected function drawQualifyGroups(Round $round, Coordinate $coordinate, int $nextRoundNumberHeight): void
    {
        foreach ($round->getQualifyGroups() as $qualifyGroup) {
            $coordinate = $this->drawQualifyGroup($qualifyGroup, $coordinate, $nextRoundNumberHeight);
        }
    }

    protected function drawQualifyGroup(QualifyGroup $qualifyGroup, Coordinate $origin, int $nextRoundNumberHeight): Coordinate
    {
        $roundWidth = $this->rangeCalculator->getRoundWidth($qualifyGroup->getChildRound());

        $selfCoordinate = $origin;
        $this->drawer->drawHorizontalCell($selfCoordinate, '|', $roundWidth, Drawer::ALIGN_CENTER);
        $selfCoordinate = $selfCoordinate->incrementY();
        $qualifyGroupName = $qualifyGroup->getWinnersOrLosers() === QualifyGroup::WINNERS ? 'w' : 'l';
        $qualifyGroupName .= $qualifyGroup->getNumber();
        $this->drawer->drawHorizontalCell($selfCoordinate, $qualifyGroupName, $roundWidth, Drawer::ALIGN_CENTER);
        $this->drawer->drawHorizontalCell($selfCoordinate->incrementY(), '|', $roundWidth, Drawer::ALIGN_CENTER);

        $childRoundCoordinate = $origin->addY(RangeCalculator::QUALIFYGROUPHEIGHT);
        $this->drawRound($qualifyGroup->getChildRound(), $childRoundCoordinate, $nextRoundNumberHeight);

        return $origin->addX($roundWidth + RangeCalculator::PADDING);
    }

//
//
//
//    /**
//     * @param list<TogetherGamePlace|AgainstGamePlace> $gamePlaces
//     * @return string
//     */
//    protected function getPlacesAsString(array $gamePlaces): string
//    {
//        return implode(' & ', array_map(
//            function (TogetherGamePlace|AgainstGamePlace $gamePlace): string {
//                return $this->getPlaceAsString($gamePlace->getPlace());
//            },
//            $gamePlaces
//        ));
//    }
//
//    protected function getFieldAsString(Field $field = null): string
//    {
//        if ($field === null) {
//            return '';
//        }
//        $priority = $field->getPriority();
//        $fieldColor = $this->useColors() ? ($priority % 10) : -1;
//        $retVal = 'field ' . ($priority < 10 ? ' ' : '') . $priority;
//        return $this->outputColor($fieldColor, $retVal);
//    }
//
//    protected function getPlaceAsString(Place $place): string
//    {
//        $retVal = $this->nameService->getPlaceFromName($place, false, false);
//        if ($this->competitorMap !== null) {
//            $competitor = $this->competitorMap->getCompetitor($place->getStartLocation());
//            if ($competitor !== null) {
//                $retVal .= ' ' . $competitor->getName();
//            }
//        }
//        while (strlen($retVal) < 10) {
//            $retVal .=  ' ';
//        }
//        if (strlen($retVal) > 10) {
//            $retVal = substr($retVal, 0, 10);
//        }
//        $useColors = $this->useColors() && $place->getPoule()->getNumber() === 1;
//        $placeColor = $useColors ? ($place->getNumber() % 10) : -1;
//        return $this->outputColor($placeColor, $retVal);
//    }
//
//    protected function getRefereeAsString(AgainstGame|TogetherGame $game): string
//    {
//        $refereePlace = $game->getRefereePlace();
//        $referee = $game->getReferee();
//        if ($referee === null && $refereePlace === null) {
//            return '';
//        }
//        $refereeDescription = $this->getRefereeDescription($referee, $refereePlace);
//        $refNr = -1;
//        if ($this->useColors()) {
//            if ($refereePlace !== null) {
//                $refNr = $refereePlace->getNumber();
//            } elseif ($referee !== null) {
//                $refNr = $referee->getPriority();
//            }
//        }
//
//        $refereeColor = $this->useColors() ? ($refNr % 10) : -1;
//        return $this->outputColor($refereeColor, $refereeDescription);
//    }
//
//    protected function getRefereeDescription(Referee|null $referee, Place|null $refPlace): string
//    {
//        if ($referee === null && $refPlace === null) {
//            return '';
//        }
//        $description = '';
//        if ($refPlace !== null) {
//            $description = $this->nameService->getPlaceFromName($refPlace, false, false);
//        } else {
//            /** @phpstan-ignore-next-line  */
//            $description = $referee->getInitials();
//        }
//        while (strlen($description) < 3) {
//            $description .=  ' ';
//        }
//        return $description;
//    }
}
