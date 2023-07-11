<?php

declare(strict_types=1);

namespace Sports\Output\StructureOutput;

use Doctrine\Common\Collections\ArrayCollection;
use Sports\Category;
use Sports\Poule;
use Sports\Round\Number as RoundNumber;
use Sports\NameService;
use Sports\Qualify\Target as QualifyTarget;
use Sports\Structure;
use Sports\Structure\Cell as StructureCell;
use Sports\Round;
use SportsHelpers\PouleStructure;

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
        $maxHeight = 0;
        foreach( $structure->getCategories() as $category ) {
            $height = $this->getCategoryHeight($category);
            if( $height > $maxHeight ) {
                $maxHeight = $height;
            }
        }
        return $maxHeight;
    }

    public function getCategoryHeight(Category $category): int
    {
        $structureCell = $category->getFirstStructureCell();
        $height = self::BORDER;
        $height += $this->getRoundNumberHeight($structureCell->getRoundNumber());

        $structureCell = $structureCell->getNext();
        while( $structureCell !== null ) {
            $height += $this->getQualifyGroupsHeight();
            $height += $this->getRoundNumberHeight($structureCell->getRoundNumber());
            $structureCell = $structureCell->getNext();
        }
        return $height + self::BORDER;
    }

//    public function getStructureCellHeight(StructureCell $structureCell): int {
//        return $this->getPouleStructureHeight($structureCell->createPouleStructure());
//    }

    public function getRoundNumberHeight(RoundNumber $roundNumber): int {
        return $this->getPouleStructureHeight($roundNumber->createPouleStructure());
    }

    public function getQualifyGroupsHeight(): int {
        return 3;
    }

    public function getPouleStructureHeight(PouleStructure $pouleStructure): int
    {
        $biggestPoule = $pouleStructure->getBiggestPoule();

        $pouleNameHeight = 1;
        $seperatorHeight = 1;
        $height = self::BORDER + $pouleNameHeight + $seperatorHeight + $biggestPoule + self::BORDER;

        return $height;
    }

    public function getStructureWidth(Structure $structure): int
    {
        $width = 0;
        foreach( $structure->getCategories() as $category ) {
            $width += $this->getCategoryWidth($category) + self::PADDING;
        }
        return $width - self::PADDING;
    }

    public function getCategoryTitle(Category $category): string {
        return $category->getName();
    }

    public function getCategoryWidth(Category $category): int
    {
        $structureCell = $category->getFirstStructureCell();
        $maxWidth = $this->getStructureCellWidth($structureCell);
        while  ($structureCell = $structureCell->getNext()) {
            $currentWidth = $this->getStructureCellWidth($structureCell);
            if( $currentWidth > $maxWidth) {
                $maxWidth = $currentWidth;
            }
        }

        $titleWidth = mb_strlen($this->getCategoryTitle($category));
        return $titleWidth > $maxWidth ? $titleWidth : $maxWidth;
    }

    public function getStructureCellWidth(Structure\Cell $structureCell): int
    {
        $width = self::PADDING;
        foreach ($structureCell->getRounds() as $round) {
            $width += $this->getRoundWidth($round) + self::PADDING;
        }
        return $width;
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
