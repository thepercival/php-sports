<?php
declare(strict_types=1);

namespace Sports\Ranking;

use Sports\Game;
use Sports\Score\Against as AgainstScore;
use Sports\Score\AgainstHelper;
use Sports\Sport\ScoreConfig\Service as SportScoreConfigService;
use Sports\Place;
use Sports\Round;
use Sports\Ranking\RoundItem\Unranked as UnrankedRoundItem;

/* tslint:disable:no-bitwise */

class ItemsGetter
{

    /**
     * @var Round
     */
    private $round;
    /**
     * @var int
     */
    private $gameStates;
    /**
     * @var SportScoreConfigService
     */
    private $sportScoreConfigService;

    public function __construct(Round $round, int $gameStates)
    {
        $this->round = $round;
        $this->gameStates = $gameStates;
        $this->sportScoreConfigService = new SportScoreConfigService();
    }

    protected static function getIndex(Place $place): string
    {
        return $place->getPoule()->getNumber() . '-' . $place->getNumber();
    }

    /**
     * @param array | Place[] $places
     * @param array | Game[] $games
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
            $finalScore = $this->sportScoreConfigService->getFinalScore($game);
            $finalSubScore = $useSubScore ? $this->sportScoreConfigService->getFinalSubScore($game) : null;

            // $finalScore = $this->sportScoreConfigService->getFinal($game);
            foreach ([Game::HOME, Game::AWAY] as $homeAway) {
                $points = $this->getNrOfPoints($finalScore, $homeAway, $game);
                $scored = $this->getNrOfUnits($finalScore, $homeAway, AgainstScore::SCORED);
                $received = $this->getNrOfUnits($finalScore, $homeAway, AgainstScore::RECEIVED);
                $subScored = 0;
                $subReceived = 0;
                if ($useSubScore) {
                    $subScored = $this->getNrOfUnits($finalSubScore, $homeAway, AgainstScore::SCORED);
                    $subReceived = $this->getNrOfUnits($finalSubScore, $homeAway, AgainstScore::RECEIVED);
                }

                foreach ($game->getPlaces($homeAway) as $gamePlace) {
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

    public function getNrOfPoints(?AgainstHelper $finalScore, bool $homeAway, Game $game): float
    {
        if ($finalScore === null) {
            return 0;
        }
        if ($finalScore->getResult( $homeAway ) === Game::RESULT_WIN ) {
            if ($game->getFinalPhase() === Game::PHASE_REGULARTIME) {
                return $game->getQualifyConfig()->getWinPoints();
            } elseif ($game->getFinalPhase() === Game::PHASE_EXTRATIME) {
                return $game->getQualifyConfig()->getWinPointsExt();
            }
        } elseif ($finalScore->getResult( $homeAway ) === Game::RESULT_DRAW ) {
            if ($game->getFinalPhase() === Game::PHASE_REGULARTIME) {
                return $game->getQualifyConfig()->getDrawPoints();
            } elseif ($game->getFinalPhase() === Game::PHASE_EXTRATIME) {
                return $game->getQualifyConfig()->getDrawPointsExt();
            }
        } elseif ($game->getFinalPhase() === Game::PHASE_EXTRATIME) {
            return $game->getQualifyConfig()->getLosePointsExt();
        }
        return 0;
    }

    private function getNrOfUnits(?AgainstHelper $finalScore, bool $homeAway, int $scoredReceived): int
    {
        if ($finalScore === null) {
            return 0;
        }
        return $this->getGameScorePart($finalScore, $scoredReceived === AgainstScore::SCORED ? $homeAway : !$homeAway);
    }

    private function getGameScorePart(AgainstHelper $gameScoreHomeAway, bool $homeAway): int
    {
        return $homeAway === Game::HOME ? $gameScoreHomeAway->getHomeScore() : $gameScoreHomeAway->getAwayScore();
    }
}
