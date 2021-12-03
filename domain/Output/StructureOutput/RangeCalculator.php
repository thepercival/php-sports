<?php
declare(strict_types=1);

namespace Sports\Output\StructureOutput;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Poule;
use Sports\Round\Number as RoundNumber;
use Sports\NameService;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Structure;
use Sports\Round;

final class RangeCalculator
{
    // protected const MARGIN = 1;
    public const PADDING = 1;
    public const BORDER = 1;
    protected const PLACEWIDTH = 3;
    protected const HORPLACEWIDTH = 3;
    public const QUALIFYGROUPHEIGHT = 3;

    protected NameService $nameService;

    public function __construct()
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
        $widthPoules = self::BORDER + self::PADDING
            + $this->getAllPoulesWidth($round)
            + self::PADDING + self::BORDER;

        $qualifyGroups = $round->getQualifyGroups();
        $widthQualifyGroups = 0;
        foreach ($qualifyGroups as $qualifyGroup) {
            $widthQualifyGroups += $this->getRoundWidth($qualifyGroup->getChildRound()) + self::PADDING;
        }
        $widthQualifyGroups -= self::PADDING;

        return $widthPoules > $widthQualifyGroups ? $widthPoules : $widthQualifyGroups;
    }

    public function getQualifyGroupsWidth(Round $parentRound): int
    {
        $qualifyGroups = $parentRound->getQualifyGroups();
        $widthQualifyGroups = 0;
        foreach ($qualifyGroups as $qualifyGroup) {
            $widthQualifyGroups += $this->getRoundWidth($qualifyGroup->getChildRound()) + RangeCalculator::PADDING;
        }
        return $widthQualifyGroups - RangeCalculator::PADDING;
    }

    public function getAllPoulesWidth(Round $round): int
    {
        $width = 0;
        foreach ($round->getPoules() as $poule) {
            $width += $this->getPouleWidth($poule) + self::PADDING;
        }
        $horPouleWidth = $this->getHorPoulesWidth($round);
        if ($horPouleWidth === 0) {
            return $width;
        }
        return $width + RangeCalculator::PADDING + $horPouleWidth;
    }

    public function getPouleWidth(Poule $poule): int
    {
        return self::PLACEWIDTH;
    }

    public function getHorPoulesWidth(Round $round): int
    {
        if ($round->getHorizontalPoules(QualifyTarget::Winners)->count() === 0
            && $round->getHorizontalPoules(QualifyTarget::Losers)->count() === 0) {
            return 0;
        }
        return RangeCalculator::BORDER + RangeCalculator::PADDING + RangeCalculator::HORPLACEWIDTH;
    }
}
