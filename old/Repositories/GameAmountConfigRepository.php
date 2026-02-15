<?php

declare(strict_types=1);

namespace old\Repositories;

use Doctrine\ORM\EntityRepository;
use Sports\Competition\CompetitionSport;
use Sports\Planning\GameAmountConfig as GameAmountConfigBase;
use Sports\Round\Number as RoundNumber;

/**
 * @template-extends EntityRepository<GameAmountConfigBase>
 */
final class GameAmountConfigRepository extends EntityRepository
{
    public function addObjects(CompetitionSport $competitionSport, RoundNumber $roundNumber): void
    {
        $gameAmountConfig = $roundNumber->getGameAmountConfig($competitionSport);
        if ($gameAmountConfig === null) {
            return;
        }
        $this->getEntityManager()->persist($gameAmountConfig);
        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null) {
            $this->addObjects($competitionSport, $nextRoundNumber);
        }
    }

    public function removeObjects(CompetitionSport $competitionSport): void
    {
        $gameAmountConfigs = $this->findBy(["competitionSport" => $competitionSport ]);
        foreach ($gameAmountConfigs as $config) {
            $this->getEntityManager()->remove($config);
        }
    }
}
