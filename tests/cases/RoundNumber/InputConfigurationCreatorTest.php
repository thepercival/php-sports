<?php

declare(strict_types=1);

namespace Sports\Tests\Round\Number;

use PHPUnit\Framework\TestCase;
use Sports\Round\Number\PlanningConfigurationCreator;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\StructureEditorCreator;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsPlanning\Input;

final class InputConfigurationCreatorTest extends TestCase
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
        $inputConfiguration = (new PlanningConfigurationCreator())->create(
            $firstRoundNumber, new RefereeInfo(count($competition->getReferees()))
        );

        $sportWithReducedFields = reset($inputConfiguration->sportVariantsWithFields);
        self::assertNotFalse($sportWithReducedFields);
        self::assertEquals(1, $sportWithReducedFields->getNrOfFields());
    }
}
