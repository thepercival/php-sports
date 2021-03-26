<?php
declare(strict_types=1);

namespace Sports\Output\StructureOutput;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Output\Coordinate;
use Sports\Poule;
use Sports\Round\Number as RoundNumber;
use Psr\Log\LoggerInterface;
use Sports\NameService;
use Sports\Structure;
use Sports\Round;

final class RangeCalculator
{
    // protected const MARGIN = 1;
    public const PADDING = 1;
    public const BORDER = 1;
    protected const PLACEWIDTH = 3;
    public const QUALIFYGROUPHEIGHT = 3;

    protected NameService $nameService;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->nameService = new NameService();
    }

    public function getStructureHeight(Structure $structure): int
    {
        $height = 0;
        $roundNumber = $structure->getFirstRoundNumber();
        while ($roundNumber !== null) {
            $height += $this->getRoundNumberHeight($roundNumber);
            $roundNumber = $roundNumber->getNext();
            if ($roundNumber !== null) {
                $height += self::QUALIFYGROUPHEIGHT;
            }
        }
        return $height;
    }

    public function getStructureWidth(Structure $structure): int
    {
        $maxWidth = 0;
        $roundNumber = $structure->getFirstRoundNumber();
        while ($roundNumber !== null) {
            $width = $this->getRoundNumberWidth($roundNumber);
            if ($width > $maxWidth) {
                $maxWidth = $width;
            }
            $roundNumber = $roundNumber->getNext();
        }
        return $maxWidth;
    }

    public function getRoundNumberHeight(RoundNumber $roundNumber): int
    {
        $biggestPoule = $roundNumber->createPouleStructure()->getBiggestPoule();

        $pouleNameHeight = 1;
        $seperatorHeight = 1;
        $height = self::BORDER + $pouleNameHeight + $seperatorHeight + $biggestPoule + self::BORDER;

        return $height;
    }

    public function getRoundNumberWidth(RoundNumber $roundNumber): int
    {
        $rounds = $roundNumber->getRounds();
        $width = 0;
        foreach ($rounds as $round) {
            $width += $this->getRoundWidth($round) + self::PADDING;
        }
        return $width - self::PADDING;
    }

    public function getRoundWidth(Round $round): int
    {
        $widthPoules = self::BORDER + self::PADDING + $this->getPoulesWidth($round->getPoules()) + self::PADDING + self::BORDER;

        $qualifyGroups = $round->getQualifyGroups();
        $widthQualifyGroups = 0;
        foreach ($qualifyGroups as $qualifyGroup) {
            $widthQualifyGroups += $this->getRoundWidth($qualifyGroup->getChildRound()) + self::PADDING;
        }
        $widthQualifyGroups -= self::PADDING;

        return $widthPoules > $widthQualifyGroups ? $widthPoules : $widthQualifyGroups;
    }

    /**
     * @param ArrayCollection<int|string, Poule> $poules
     * @return int
     */
    public function getPoulesWidth(ArrayCollection $poules): int
    {
        $width = 0;
        foreach ($poules as $poule) {
            $width += $this->getPouleWidth($poule) + self::PADDING;
        }
        return $width - self::PADDING;
    }

    public function getPouleWidth(Poule $poule): int
    {
        return self::PLACEWIDTH;
    }
}
