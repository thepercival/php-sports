<?php

declare(strict_types=1);

namespace Sports\Tests\Round\Number;

use PHPUnit\Framework\TestCase;
use Sports\Round\Number\PlanningInputCreator;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\StructureEditorCreator;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;

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

        $inputCreator = new PlanningInputCreator();
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $input = $inputCreator->create($firstRoundNumber, count($competition->getReferees()));

        $sportWithReducedFields = $input->getSport(1);
        self::assertEquals(1, $sportWithReducedFields->getNrOfFields());
    }
}
