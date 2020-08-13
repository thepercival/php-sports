<?php

namespace Sports\TestHelper;

use SportsPlanning\Batch;
use SportsPlanning\Place as PlanningPlace;
use SportsPlanning\Game as PlanningGame;
use SportsPlanning\Field as PlanningField;
use SportsPlanning\Referee as PlanningReferee;
use SportsPlanning\Resource\RefereePlace\Replacer as RefereePlaceReplacer;

trait PlanningReplacer
{
    protected function replaceRefereePlace(
        bool $samePoule,
        Batch $firstBatch,
        PlanningPlace $replaced,
        PlanningPlace $replacement
    ) {
        (new RefereePlaceReplacer($samePoule))->replace($firstBatch, $replaced, $replacement);
    }

    protected function replaceField(
        Batch $batch,
        PlanningField $replacedField,
        PlanningField $replacedByField,
        int $amount = 1
    ): bool {
        return $this->replaceFieldHelper($batch->getNext(), $replacedField, $replacedByField, 0, $amount);
    }

    protected function replaceFieldHelper(
        Batch $batch,
        PlanningField $fromField,
        PlanningField $toField,
        int $amountReplaced,
        int $maxAmount
    ): bool {
        $batchHasToField = $this->hasBatchField($batch, $toField);
        /** @var PlanningGame $game */
        foreach ($batch->getGames() as $game) {
            if ($game->getField() !== $fromField || $batchHasToField) {
                continue;
            }
            $game->setField($toField);
            if (++$amountReplaced === $maxAmount) {
                return true;
            }
        }
        if ($batch->hasNext()) {
            return $this->replaceFieldHelper($batch->getNext(), $fromField, $toField, $amountReplaced, $maxAmount);
        }
        return false;
    }

    protected function hasBatchField(Batch $batch, PlanningField $field): bool
    {
        foreach ($batch->getGames() as $game) {
            if ($game->getField() === $field) {
                return true;
            }
        }
        return false;
    }

    protected function replaceReferee(
        Batch $batch,
        PlanningReferee $replacedReferee,
        PlanningReferee $replacedByReferee,
        int $amount = 1
    ): bool {
        return $this->replaceRefereeHelper($batch->getNext(), $replacedReferee, $replacedByReferee, 0, $amount);
    }

    protected function replaceRefereeHelper(
        Batch $batch,
        PlanningReferee $fromReferee,
        PlanningReferee $toReferee,
        int $amountReplaced,
        int $maxAmount
    ): bool {
        $batchHasToReferee = $this->hasBatchReferee($batch, $toReferee);
        /** @var PlanningGame $game */
        foreach ($batch->getGames() as $game) {
            if ($game->getReferee() !== $fromReferee || $batchHasToReferee) {
                continue;
            }
            $game->setReferee($toReferee);
            if (++$amountReplaced === $maxAmount) {
                return true;
            }
        }
        if ($batch->hasNext()) {
            return $this->replaceRefereeHelper(
                $batch->getNext(),
                $fromReferee,
                $toReferee,
                $amountReplaced,
                $maxAmount
            );
        }
        return false;
    }

    protected function hasBatchReferee(Batch $batch, PlanningReferee $referee): bool
    {
        foreach ($batch->getGames() as $game) {
            if ($game->getReferee() === $referee) {
                return true;
            }
        }
        return false;
    }
}


