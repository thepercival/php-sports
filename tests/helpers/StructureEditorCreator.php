<?php
declare(strict_types=1);

namespace Sports\TestHelper;

use Sports\Planning\Config\Service as PlanningConfigService;
use Sports\Structure\Editor as StructureEditor;
use Sports\Competition\Sport\Service as CompetitionSportService;
use SportsHelpers\Place\Range as PlaceRange;

trait StructureEditorCreator
{
    /**
     * @param list<PlaceRange> $placeRanges
     * @return StructureEditor
     */
    protected function createStructureEditor(array $placeRanges): StructureEditor
    {
        return new StructureEditor(new CompetitionSportService(), new PlanningConfigService(), $placeRanges);
    }

}
