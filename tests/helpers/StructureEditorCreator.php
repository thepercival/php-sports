<?php
declare(strict_types=1);

namespace Sports\TestHelper;

use Sports\Planning\Config\Service as PlanningConfigService;
use Sports\Structure\Editor as StructureEditor;
use Sports\Competition\Sport\Service as CompetitionSportService;
use SportsHelpers\PlaceRanges;

trait StructureEditorCreator
{
    protected function createStructureEditor(PlaceRanges|null $placeRanges = null): StructureEditor
    {
        $editor = new StructureEditor(new CompetitionSportService(), new PlanningConfigService());
        if ($placeRanges !== null) {
            $editor->setPlaceRanges($placeRanges);
        }
        return $editor;
    }
}
