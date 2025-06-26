<?php

declare(strict_types=1);

namespace Sports\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\Competition\CompetitionSport as CompetitionSport;
use Sports\Planning\GameAmountConfig as GameAmountConfigBase;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<GameAmountConfigBase>
 */
class GameAmountConfigRepository extends EntityRepository
{
    /**
     * @use BaseRepository<GameAmountConfigBase>
     */
    use BaseRepository;

    public function addObjects(CompetitionSport $competitionSport, RoundNumber $roundNumber): void
    {
        $gameAmountConfig = $roundNumber->getGameAmountConfig($competitionSport);
        if ($gameAmountConfig === null) {
            return;
        }
        $this->save($gameAmountConfig);
        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null) {
            $this->addObjects($competitionSport, $nextRoundNumber);
        }
    }

    public function removeObjects(CompetitionSport $competitionSport): void
    {
        $gameAmountConfigs = $this->findBy(["competitionSport" => $competitionSport ]);
        foreach ($gameAmountConfigs as $config) {
            $this->remove($config);
        }
    }
}
