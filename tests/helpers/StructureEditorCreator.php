<?php

declare(strict_types=1);

namespace Sports\TestHelper;

use Sports\Competition\Sport\Editor as CompetitionSportEditor;
use Sports\Planning\PlanningConfigService as PlanningConfigService;
use Sports\Structure\StructureEditor as StructureEditor;
use SportsHelpers\PlaceRanges;

trait StructureEditorCreator
{
    protected function createStructureEditor(PlaceRanges|null $placeRanges = null): StructureEditor
    {
        $editor = new StructureEditor(new CompetitionSportEditor(), new PlanningConfigService());
        if ($placeRanges !== null) {
            $editor->setPlaceRanges($placeRanges);
        }
        return $editor;
    }
}
