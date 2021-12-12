<?php

declare(strict_types=1);

namespace Sports\Output;

use Sports\Output\StructureOutput\RangeCalculator;
use Psr\Log\LoggerInterface;
use Sports\Output\StructureOutput\DrawHelper;
use Sports\Output\Grid\Drawer as GridDrawer;
use Sports\Round\Number as RoundNumber;
use Sports\Structure;
use SportsHelpers\Output as OutputBase;

final class StructureOutput extends OutputBase
{
    /**
     * @var array<int, string>
     */
    protected array $outputLines = [];
    protected RangeCalculator $rangeCalculator;

    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->rangeCalculator = new RangeCalculator();
    }

    public function output(Structure $structure): void
    {
        $grid = $this->getGrid($structure);
        $drawer = new GridDrawer($grid);
        $coordinate = new Coordinate(0, 0);
        $drawHelper = new DrawHelper($drawer);
        $drawHelper->drawStructure($structure, $coordinate);

//        $batchColor = $this->useColors() ? ($batchNr % 10) : -1;
//        $retVal = 'batch ' . ($batchNr < 10 ? ' ' : '') . $batchNr;
//        return $this->outputColor($batchColor, $retVal);
        $grid->output();
    }

    protected function getGrid(Structure $structure): Grid
    {
        $width = $this->rangeCalculator->getStructureWidth($structure);
        $height = $this->rangeCalculator->getStructureHeight($structure);
        return new Grid($height, $width);
    }
}
