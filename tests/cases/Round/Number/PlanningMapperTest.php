<?php
declare(strict_types=1);

namespace Sports\Tests\Round\Number;

use Sports\Qualify\Target as QualifyTarget;
use PHPUnit\Framework\TestCase;
use SportsHelpers\Sport\VariantWithFields;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Input;
use Sports\Round\Number\GamesValidator;
use Sports\Round\Number\PlanningMapper;
use Sports\TestHelper\CompetitionCreator;
use Sports\TestHelper\GamesCreator;
use Sports\Structure\Editor as StructureService;
use Sports\TestHelper\StructureEditorCreator;
use SportsHelpers\PouleStructure;
use SportsHelpers\SelfReferee;

final class PlanningMapperTest extends TestCase
{
    use CompetitionCreator, StructureEditorCreator;

    public function testValid(): void
    {
        $competition = $this->createCompetition();

        $structureEditor = $this->createStructureEditor();
        $structure = $structureEditor->create($competition, [4,4]);
        $rootRound = $structure->getRootRound();

        $winnersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::WINNERS, [2]);
        $losersRound = $structureEditor->addChildRound($rootRound, QualifyTarget::LOSERS, [2]);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();

        $input = new Input(
            new PouleStructure(2, 2),
            [new VariantWithFields($competition->getSingleSport()->createVariant(), 2)],
            GamePlaceStrategy::EquallyAssigned,
            0,
            SelfReferee::DISABLED
        );

        $planningMapper = new PlanningMapper($secondRoundNumber, $input);
        $winnersPoule = $planningMapper->getPoule($input->getPoule(1));
        $losersPoule = $planningMapper->getPoule($input->getPoule(2));

        self::assertSame($winnersRound, $winnersPoule->getRound());
        self::assertSame($losersRound, $losersPoule->getRound());
    }
}
