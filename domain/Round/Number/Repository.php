<?php
declare(strict_types=1);

namespace Sports\Round\Number;

use Doctrine\ORM\EntityRepository;
use Sports\Round\Number as RoundNumber;

/**
 * @template-extends EntityRepository<RoundNumber>
 */
class Repository extends EntityRepository
{
    use \Sports\Repository;

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
        $roundNumber->setHasPlanning(false);
        $this->_em->persist($roundNumber);

        $this->_em->flush();
    }

    public function savePlanning(RoundNumber $roundNumber, bool $hasPlanning = null): void
    {
        foreach ($roundNumber->getGames() as $game) {
            $this->_em->persist($game);
        }
        if ($hasPlanning !== null) {
            $roundNumber->setHasPlanning($hasPlanning);
            $this->_em->persist($roundNumber);
        }

        $this->_em->flush();
    }
}
