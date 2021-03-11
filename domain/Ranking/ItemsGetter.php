<?php
declare(strict_types=1);

namespace Sports\Ranking;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Score\Config\Service as ScoreConfigService;
use Sports\Place;
use Sports\Round;
use Sports\Ranking\Item\Round\SportUnranked as UnrankedSportRoundItem;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Against as AgainstGame;

abstract class ItemsGetter
{
    protected ScoreConfigService $scoreConfigService;

    public function __construct(protected Round $round, protected CompetitionSport $competitionSport)
    {
        $this->round = $round;
        $this->scoreConfigService = new ScoreConfigService();
    }

    protected static function getIndex(Place $place): string
    {
        return $place->getPoule()->getNumber() . '-' . $place->getNumber();
    }

    /**
     * @param array | Place[] $places
     * @param array | TogetherGame[] | AgainstGame[] $games
     * @return array | UnrankedSportRoundItem[]
     */
    abstract public function getUnrankedItems(array $places, array $games): array;

    /**
     * @param array|AgainstGame[] | TogetherGame[] $games
     * @return array|AgainstGame[] | TogetherGame[]
     */
    protected function getFilteredGames(array $games): array
    {
        return array_filter($games, function (AgainstGame | TogetherGame $game) {
            return $this->competitionSport === $game->getCompetitionSport();
        });
    }

    /**
     * @param array|UnrankedSportRoundItem[] $unrankedItems
     * @return UnrankedSportRoundItem[]|array
     */
    protected function getUnrankedMap(array $unrankedItems): array
    {
        $map = [];
        foreach ($unrankedItems as $unrankedItem) {
            $map[$unrankedItem->getPlaceLocation()->getLocationId()] = $unrankedItem;
        }
        return $map;
    }
}
