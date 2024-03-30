<?php

declare(strict_types=1);

namespace Sports\Round\Number;

use Doctrine\ORM\EntityRepository;
use Sports\Game\Order;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\Repository as BaseRepository;

/**
 * @template-extends EntityRepository<RoundNumber>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<RoundNumber>
     */
    use BaseRepository;

    public function removePlanning(RoundNumber $roundNumber): void
    {
        foreach ($roundNumber->getPoules() as $poule) {
            $games = $poule->getAgainstGames();
            while ($game = $games->first()) {
                $games->removeElement($game);
                $this->getEntityManager()->remove($game);
            }
            $games = $poule->getTogetherGames();
            while ($game = $games->first()) {
                $games->removeElement($game);
                $this->getEntityManager()->remove($game);
            }
        }
        $this->getEntityManager()->flush();
    }

    public function savePlanning(RoundNumber $roundNumber): void
    {
        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
            $this->getEntityManager()->persist($game);
        }
        $this->getEntityManager()->flush();
    }
}
