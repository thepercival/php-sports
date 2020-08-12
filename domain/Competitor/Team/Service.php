<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-1-19
 * Time: 7:52
 */

namespace Sports\Competitor\Team;

use Sports\Round;
use Sports\Association;
use Sports\Structure;
use Sports\Competitor\Base as CompetitorBase;
use Sports\Competitor\Service as CompetitorService;

class Service // implements CompetitorService
{
    // move to competitor\team\service with interfacee with this function
//    /**
//     * @param Round $rootRound
//     * @param Association $association
//     * @return array|CompetitorBase[]
//     */
//    public function createCompetitorsFromRound(Round $rootRound, Association $association): array
//    {
//        $competitors = [];
//        $places = $rootRound->getPlaces();
//        foreach ($places as $place) {
//            $competitor = $place->getCompetitor();
//            if ($competitor !== null) {
//                $newCompetitor = new CompetitorBase($association, $competitor->getName());
//                $newCompetitor->setAbbreviation($competitor->getAbbreviation());
//                $newCompetitor->setImageUrl($competitor->getImageUrl());
//                $newCompetitor->setInfo($competitor->getInfo());
//                $competitors[] = $newCompetitor;
//            }
//        }
//        return $competitors;
//    }

//    /**
//     * @param Structure $newStructure
//     * @param array|CompetitorBase[] $newCompetitors
//     */
//    public function assignCompetitors(Structure $newStructure, array $newCompetitors)
//    {
//        foreach ($newStructure->getRootRound()->getPlaces() as $place) {
//            $place->setCompetitor(null);
//            $place->setCompetitor(array_shift($newCompetitors));
//        }
//        foreach ($newStructure->getRootRound()->getChildren() as $childRound) {
//            $this->removeQualifiedCompetitors($childRound);
//        }
//    }
//
//    protected function removeQualifiedCompetitors(Round $round)
//    {
//        foreach ($round->getPoules() as $poule) {
//            foreach ($poule->getPlaces() as $place) {
//                $place->setCompetitor(null);
//            }
//        }
//        foreach ($round->getChildren() as $childRound) {
//            $this->removeQualifiedCompetitors($childRound);
//        }
//    }
}
