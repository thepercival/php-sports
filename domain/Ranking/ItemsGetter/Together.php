<?php
declare(strict_types=1);

namespace Sports\Ranking\ItemsGetter;

use Sports\Ranking\ItemsGetter as ItemsGetterBase;
use Sports\Place;
use Sports\Round;
use Sports\Ranking\RoundItem\Unranked as UnrankedRoundItem;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Against as AgainstGame;

/* tslint:disable:no-bitwise */

class Together extends ItemsGetterBase
{
    public function __construct(Round $round, int $gameStates)
    {
        parent::__construct($round, $gameStates );
    }

    /**
     * @param array | Place[] $places
     * @param array | TogetherGame[] | AgainstGame[] $games
     * @return array | UnrankedRoundItem[]
     */
    public function getUnrankedItems(array $places, array $games): array
    {
        /** @var UnrankedRoundItem[]|array $items */
        $items = array_map(
            function ($place): UnrankedRoundItem {
                return new UnrankedRoundItem($this->round, $place, $place->getPenaltyPoints());
            },
            $places
        );
        foreach ($games as $game) {
            if (($game->getState() & $this->gameStates) === 0) {
                continue;
            }
            $useSubScore = $game->getSportScoreConfig()->useSubScore();
            foreach( $game->getPlaces() as $gamePlace ) {
                $finalScore = $this->sportScoreConfigService->getFinalTogetherScore($gamePlace);
                $foundItems = array_filter(
                    $items,
                    function (UnrankedRoundItem $item) use ($gamePlace): bool {
                        return $item->getPlaceLocation()->getPlaceNr() === $gamePlace->getPlace()->getPlaceNr()
                            && $item->getPlaceLocation()->getPouleNr() === $gamePlace->getPlace()->getPouleNr();
                    }
                );
                /** @var UnrankedRoundItem $item */
                $item = reset($foundItems);
                $item->addGame();
                $item->addPoints($finalScore);
                $item->addScored($finalScore);
                if( $useSubScore ) {
                    $finalSubScore = $this->sportScoreConfigService->getFinalTogetherSubScore($gamePlace);
                    $item->addSubScored($finalSubScore);
                }
            }
        };
        return $items;
    }

//    public function getNrOfPoints(?TogetherScore $finalScore): float
//    {
//        if ($finalScore === null) {
//            return 0;
//        }
//        if ($finalScore->getResult( $homeAway ) === AgainstGame::RESULT_WIN ) {
//            if ($game->getFinalPhase() === Game::PHASE_REGULARTIME) {
//                return $game->getQualifyConfig()->getWinPoints();
//            } elseif ($game->getFinalPhase() === Game::PHASE_EXTRATIME) {
//                return $game->getQualifyConfig()->getWinPointsExt();
//            }
//        } elseif ($finalScore->getResult( $homeAway ) === AgainstGame::RESULT_DRAW ) {
//            if ($game->getFinalPhase() === Game::PHASE_REGULARTIME) {
//                return $game->getQualifyConfig()->getDrawPoints();
//            } elseif ($game->getFinalPhase() === Game::PHASE_EXTRATIME) {
//                return $game->getQualifyConfig()->getDrawPointsExt();
//            }
//        } elseif ($game->getFinalPhase() === Game::PHASE_EXTRATIME) {
//            return $game->getQualifyConfig()->getLosePointsExt();
//        }
//        return 0;
//    }
//
//    private function getNrOfUnits(?AgainstHelper $finalScore, bool $homeAway, int $scoredReceived): int
//    {
//        if ($finalScore === null) {
//            return 0;
//        }
//        return $this->getGameScorePart($finalScore, $scoredReceived === AgainstScore::SCORED ? $homeAway : !$homeAway);
//    }


}
