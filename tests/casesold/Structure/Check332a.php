<?php

namespace Sports\Tests\Structure;

use Sports\Structure;
use Sports\Qualify\Group as QualifyGroup;

trait Check332a
{
    protected function check332astructure(Structure $structure)
    {
        // roundnumbers
        $this->assertNotSame($structure->getFirstRoundNumber(), null);

        $firstRoundNumber = $structure->getFirstRoundNumber();
        $this->assertSame($firstRoundNumber->getRounds()->count(), 1);

        $this->assertSame($firstRoundNumber->hasNext(), true);

        $secondRoundNumber = $firstRoundNumber->getNext();
        $this->assertSame($secondRoundNumber->getRounds()->count(), 2);

        $this->assertSame($secondRoundNumber->hasNext(), true);

        $thirdRoundNumber = $secondRoundNumber->getNext();
        $this->assertSame($thirdRoundNumber->getRounds()->count(), 4);

        $this->assertSame($thirdRoundNumber->hasNext(), false);

        // round 1
        $this->assertNotSame($structure->getRootRound(), null);
        $rootRound = $structure->getRootRound();

        $this->assertSame($rootRound->getQualifyGroups(QualifyGroup::WINNERS)->count(), 1);

        $this->assertSame(count($rootRound->getHorizontalPoules(QualifyGroup::WINNERS)), 3);
        $this->assertSame(count($rootRound->getHorizontalPoules(QualifyGroup::LOSERS)), 3);

        // second rounds
        foreach ([QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers) {
            $this->assertNotSame($rootRound->getBorderQualifyGroup($winnersOrLosers), null);
            $qualifyGroup = $rootRound->getBorderQualifyGroup($winnersOrLosers);

            $this->assertNotSame($qualifyGroup->getBorderPoule(), null);

            $borderPoule = $qualifyGroup->getBorderPoule();
            $this->assertSame($borderPoule->getQualifyGroup(), $qualifyGroup);

            $this->assertNotSame($qualifyGroup->getChildRound(), null);
            $secondRound = $qualifyGroup->getChildRound();

            $this->assertSame($secondRound->getPoules()->count(), 2);
            $this->assertSame(count($secondRound->getHorizontalPoules(QualifyGroup::WINNERS)), 2);
            $this->assertSame(count($secondRound->getHorizontalPoules(QualifyGroup::LOSERS)), 2);
            $this->assertSame($secondRound->getNrOfPlaces(), 4);

            // third rounds
            foreach ([QualifyGroup::WINNERS, QualifyGroup::LOSERS] as $winnersOrLosers2) {
                $this->assertNotSame($secondRound->getBorderQualifyGroup($winnersOrLosers2), null);
                $qualifyGroup2 = $secondRound->getBorderQualifyGroup($winnersOrLosers2);

                $this->assertNotSame($qualifyGroup2->getBorderPoule(), null);
                $borderPoule2 = $qualifyGroup2->getBorderPoule();
                $this->assertSame($borderPoule2->getQualifyGroup(), $qualifyGroup2);

                $this->assertNotSame($qualifyGroup2->getChildRound(), null);

                $thirdRound = $qualifyGroup2->getChildRound();

                $this->assertSame($thirdRound->getPoules()->count(), 1);
                $this->assertSame(count($thirdRound->getHorizontalPoules(QualifyGroup::WINNERS)), 2);
                $this->assertSame(count($thirdRound->getHorizontalPoules(QualifyGroup::LOSERS)), 2);
                $this->assertSame($thirdRound->getNrOfPlaces(), 2);
            }
        }
    }
}
