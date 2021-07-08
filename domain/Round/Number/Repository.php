<?php
declare(strict_types=1);

namespace Sports\Round\Number;

use Sports\Game\Order;
use SportsHelpers\Repository\SaveRemove as SaveRemoveRepository;
use SportsHelpers\Repository as BaseRepository;
use Doctrine\ORM\EntityRepository;
use Sports\Round\Number as RoundNumber;

/**
 * @template-extends EntityRepository<RoundNumber>
 * @template-implements SaveRemoveRepository<RoundNumber>
 */
class Repository extends EntityRepository implements SaveRemoveRepository
{
    use BaseRepository;

    public function removePlanning(RoundNumber $roundNumber): void
    {
        foreach ($roundNumber->getPoules() as $poule) {
            $games = $poule->getAgainstGames();
            while ($game = $games->first()) {
                $games->removeElement($game);
                $this->_em->remove($game);
            }
            $games = $poule->getTogetherGames();
            while ($game = $games->first()) {
                $games->removeElement($game);
                $this->_em->remove($game);
            }
        }
        // $roundNumber->setHasPlanning(false);
        // $this->_em->persist($roundNumber);

        $this->_em->flush();
    }

    public function savePlanning(RoundNumber $roundNumber): void
    {
        foreach ($roundNumber->getGames(Order::ByPoule) as $game) {
            $this->_em->persist($game);
        }
//        if ($hasPlanning !== null) {
//            $roundNumber->setHasPlanning($hasPlanning);
//            $this->_em->persist($roundNumber);
//        }

        $this->_em->flush();
    }
}
