<?php

namespace Sports\Round\Number;

use Sports\Round\Number as RoundNumber;

class Repository extends \Sports\Repository
{
    public function removePlanning(RoundNumber $roundNumber)
    {
        foreach ($roundNumber->getPoules() as $poule) {
            $games = $poule->getGames();
            while ($games->count() > 0) {
                $game = $games->first();
                $games->removeElement($game);
                $this->_em->remove($game);
            }
        }
        $roundNumber->setHasPlanning(false);
        $this->_em->persist($roundNumber);

        $this->_em->flush();
    }

    public function savePlanning(RoundNumber $roundNumber, bool $hasPlanning = null)
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
