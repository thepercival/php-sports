<?php

declare(strict_types=1);

namespace Sports\Tests\Round\Number;

use PHPUnit\Framework\TestCase;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\StructureEditorCreator;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsPlanning\Input;

final class PlanningInputCreatorTest extends TestCase
{
    use CompetitionCreator;
    use StructureEditorCreator;

    public function testMultipleSports(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(2, 1, 1, 9),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 9),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 9)
        ];
        $competition = $this->createCompetition($sportVariantsWithFields);

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [10]);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $input = new Input(
            $firstRoundNumber->createPouleStructure(),
            $competition->createSportVariantsWithFields(),
            new RefereeInfo(count($competition->getReferees())),
            false );

        $sportWithReducedFields = $input->getSport(1);
        self::assertEquals(1, $sportWithReducedFields->getNrOfFields());
    }
}
