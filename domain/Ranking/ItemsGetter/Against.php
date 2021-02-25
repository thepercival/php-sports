<?php
declare(strict_types=1);

namespace Sports\Ranking\ItemsGetter;

use Sports\Game;
use Sports\Score\Against as AgainstGameScore;
use Sports\Score\AgainstHelper as AgainstScoreHelper;
use Sports\Score\Against as AgainstScore;
use Sports\Ranking\ItemsGetter as ItemsGetterBase;
use Sports\Place;
use Sports\Round;
use Sports\Ranking\RoundItem\Unranked as UnrankedRoundItem;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Against as AgainstGame;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Against\Result as AgainstResult;


/* tslint:disable:no-bitwise */

class Against extends ItemsGetterBase
{
    public function __construct(Round $round, int $gameStates)
    {
        parent::__construct($round, $gameStates);
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
            $useSubScore = $game->getScoreConfig()->useSubScore();
            $finalScore = $this->scoreConfigService->getFinalAgainstScore($game);
            $finalSubScore = $useSubScore ? $this->scoreConfigService->getFinalAgainstSubScore($game) : null;

            // $finalScore = $this->scoreConfigService->getFinal($game);
            foreach ([AgainstSide::HOME, AgainstSide::AWAY] as $side) {
                $points = $this->getNrOfPoints($finalScore, $side, $game);
                $scored = $this->getNrOfUnits($finalScore, $side, AgainstGameScore::SCORED);
                $received = $this->getNrOfUnits($finalScore, $side, AgainstGameScore::RECEIVED);
                $subScored = 0;
                $subReceived = 0;
                if ($useSubScore) {
                    $subScored = $this->getNrOfUnits($finalSubScore, $side, AgainstGameScore::SCORED);
                    $subReceived = $this->getNrOfUnits($finalSubScore, $side, AgainstGameScore::RECEIVED);
                }

                foreach ($game->getPlaces($side) as $gamePlace) {
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
                    $item->addPoints($points);
                    $item->addScored($scored);
                    $item->addReceived($received);
                    $item->addSubScored($subScored);
                    $item->addSubReceived($subReceived);
                }
            }
        };
        return $items;
    }

    public function getNrOfPoints(?AgainstScoreHelper $finalScore, int $side, AgainstGame $game): float
    {
        if ($finalScore === null) {
            return 0;
        }
        $qualifyAgainstConfig = $game->getQualifyAgainstConfig();
        if ($finalScore->getResult($side) === AgainstResult::WIN) {
            if ($game->getFinalPhase() === Game::PHASE_REGULARTIME) {
                return $qualifyAgainstConfig->getWinPoints();
            } elseif ($game->getFinalPhase() === Game::PHASE_EXTRATIME) {
                return $qualifyAgainstConfig->getWinPointsExt();
            }
        } elseif ($finalScore->getResult($side) === AgainstResult::DRAW) {
            if ($game->getFinalPhase() === Game::PHASE_REGULARTIME) {
                return $qualifyAgainstConfig->getDrawPoints();
            } elseif ($game->getFinalPhase() === Game::PHASE_EXTRATIME) {
                return $qualifyAgainstConfig->getDrawPointsExt();
            }
        } elseif ($game->getFinalPhase() === Game::PHASE_EXTRATIME) {
            return $qualifyAgainstConfig->getLosePointsExt();
        }
        return 0;
    }

    private function getNrOfUnits(?AgainstScoreHelper $finalScore, int $side, int $scoredReceived): int
    {
        if ($finalScore === null) {
            return 0;
        }
        $opposite = $side === AgainstSide::HOME ? AgainstSide::AWAY : AgainstSide::HOME;
        return $this->getGameScorePart($finalScore, $scoredReceived === AgainstScore::SCORED ? $side : $opposite);
    }

    private function getGameScorePart(AgainstScoreHelper $againstGameScore, int $side): int
    {
        return $side === AgainstSide::HOME ? $againstGameScore->getHome() : $againstGameScore->getAway();
    }
}
