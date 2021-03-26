<?php
declare(strict_types=1);

namespace Sports\Output\Grid;

use SportsHelpers\Output\Color;
use Sports\Output\Coordinate;
use Sports\Output\Grid;

final class Drawer
{
    public const ALIGN_LEFT = 1;
    public const ALIGN_CENTER = 2;
    public const ALIGN_RIGHT = 3;

    use Color;

    public function __construct(protected Grid $grid)
    {
    }

    public function drawHorizontal(Coordinate $coordinate, string $value): Coordinate
    {
        $valueAsArray = str_split($value);
        while ($char = array_shift($valueAsArray)) {
            $coordinate = $this->grid->setHorizontal($coordinate, $char);
        }
        return $coordinate->decrementX();
    }

    public function drawHorizontalLine(Coordinate $coordinate, int $length, string $value = '-'): Coordinate
    {
        return $this->drawHorizontal($coordinate, $this->initString($length, $value));
    }

    public function drawVertical(Coordinate $coordinate, string $value): Coordinate
    {
        $valueAsArray = str_split($value);
        while ($char = array_shift($valueAsArray)) {
            $coordinate = $this->grid->setVertical($coordinate, $char);
        }
        return $coordinate->decrementY();
    }

    public function drawVerticalLine(Coordinate $coordinate, int $length, string $value = '|'): Coordinate
    {
        return $this->drawVertical($coordinate, $this->initString($length, $value));
    }

    public function drawHorizontalCell(Coordinate $coordinate, string $text, int $width, int $align): Coordinate
    {
        $char = ' ';
        if (strlen($text) > $width) {
            $text = substr($text, 0, $width);
        }
        if ($align === self::ALIGN_CENTER) {
            $align = self::ALIGN_LEFT;
            while (strlen($text) < $width) {
                $text = $this->addToString($text, $char, $align);
                $align = $align === self::ALIGN_LEFT ? self::ALIGN_RIGHT : self::ALIGN_LEFT;
            }
        } else {
            while (strlen($text) < $width) {
                $text = $this->addToString($text, $char, $align);
            }
        }
        return $this->drawHorizontal($coordinate, $text);
    }

    public function initString(int $length, string $char = ' '): string
    {
        $retVal = '';
        while ($length--) {
            $retVal .= $char;
        }
        return $retVal;
    }

    public function addToString(string $text, string $char, int $side): string
    {
        if ($side === self::ALIGN_RIGHT) {
            return $text . $char;
        }
        return $char . $text;
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
