<?php
declare(strict_types=1);

namespace Sports\Ranking;

use Sports\Sport\ScoreConfig\Service as SportScoreConfigService;
use Sports\Place;
use Sports\Round;
use Sports\Ranking\RoundItem\Unranked as UnrankedRoundItem;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Against as AgainstGame;

/* tslint:disable:no-bitwise */

abstract class ItemsGetter
{
    protected Round $round;
    protected int $gameStates;
    protected SportScoreConfigService $sportScoreConfigService;

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
     * @param array | TogetherGame[] | AgainstGame[] $games
     * @return array | UnrankedRoundItem[]
     */
    abstract public function getUnrankedItems(array $places, array $games): array;
}
