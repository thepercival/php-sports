<?php
declare(strict_types=1);

namespace Sports\Planning\GameAmountConfig;

use SportsHelpers\Repository as BaseRepository;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Round\Number as RoundNumber;
use Doctrine\ORM\EntityRepository;
use Sports\Planning\GameAmountConfig as GameAmountConfigBase;

/**
 * @template-extends EntityRepository<GameAmountConfigBase>
 */
class Repository extends EntityRepository
{
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
