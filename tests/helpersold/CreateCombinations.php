<?php

$totalCompetitors = 14;
$competitorsPerIt = 4;
$competitorsPerTwo = getCombinations($competitorsPerIt, 2);
$nrOfCompetitorsPerTwo = count($competitorsPerTwo);

 function getCombinations(int $total, int $amountPerCombination): array
 {
     $combinations = [];

     $numbers = [];
     for ($i = 1 ; $i <= $total ; $i++) {
         $numbers[] = $i;
     }

     $itemSuccess = function (int $newNumber): bool {
         return true; // ($newNumber % 2) === 1;
     };
     /**
      * @param array|int[] $batch
      * @return bool
      */
     $endSuccess = function (array $batch) use ($amountPerCombination) : bool {
         return $amountPerCombination === count($batch);
//         if ($amountPerCombination !== count( $batch ) ) {
//             return false;
//         }
//         $sum = 0;
//         foreach( $batch as $number ) { $sum += $number; }
//         return $sum === 3;
     };

     /**
      * @param array|int[] $list
      * @param array|int[] $batch
      * @return bool
      */
     $showC = function (array $list, array $batch = []) use (&$showC, $itemSuccess, $endSuccess, $amountPerCombination, &$combinations) : bool {
         if ($endSuccess($batch)) {
             $combinations[] = $batch; // echo implode( ',', $batch) . PHP_EOL;
             return true;
         }
         if ((count($list) + count($batch)) < $amountPerCombination) {
             return false;
         }
         $numberToTry = array_shift($list);
         if ($numberToTry !== null && $itemSuccess($numberToTry)) {
             $batch[] = $numberToTry;
             if ($showC(array_slice($list, 0), $batch) === true) {
                 // return true; // uncomment for one solution
             }
             array_pop($batch);
             return $showC($list, $batch);
         }
         return $showC($list, $batch);
     };

     $showC($numbers);
     return $combinations;
 }

$combinations = getCombinations($totalCompetitors, $competitorsPerIt);
echo count($combinations) . " combinations found" . PHP_EOL;

function getTwentySix(array &$vs, array &$nrInBatches, array $combinationIt, int $totalCompetitors, int $nrOfCompetitorsPerTwo, array &$successFulFnc) : bool
{
    $allAreTwentySix = function (array $nrInBatches) use ($nrOfCompetitorsPerTwo): bool {
        foreach ($nrInBatches as $number) {
            if ($number !== 26) {
                return false;
            }
        }
        return true;
    };
    $lessThanXDiff = function (array $nrInBatches, array $combination, int $x) : bool {
        foreach ($combination as $number) {
            $newAmount = array_key_exists($number, $nrInBatches) ? $nrInBatches[$number] : 0;
            foreach ($nrInBatches as $nrIt) {
                if (($nrIt+$x) < $newAmount) {
                    return false;
                }
            }
        }
        return true;
    };
    $mapVsCombination = function (array $vsCombinations, array $combination) : array {
        $mappedVsCombination = [];
        // als 2 tegenstanders al zes keer tegen elkaar dan return false
        foreach ($vsCombinations as $vsCombination) {
            $mappedVsCombination[] = [ $combination[ $vsCombination[0]-1 ], $combination[ $vsCombination[1]-1 ] ];
        }
        return $mappedVsCombination;
    };
    $lessThanXDiffVS = function (array $vs, array $vsCombinations, int $minimalVS, int $x) : bool {
        foreach ($vsCombinations as $vsCombination) {
            if (($vs[ $vsCombination[0] ][ $vsCombination[1] ] - $x) > $minimalVS) {
                return false;
            }
        }
        return true;
    };

    $allLessThanXVS = function (array $vs, array $vsCombinations, int $nrVs) : bool {
        // als 2 tegenstanders al zes keer tegen elkaar dan return false
        foreach ($vsCombinations as $vsCombination) {
            if ($vs[ $vsCombination[0] ][ $vsCombination[1] ] === $nrVs) {
                return false;
            }
        }
        return true;
    };
    $allHaveSixVs = function (array $vs, int $nrVs) : bool {
        foreach ($vs as $competitorNr => $competitor) {
            foreach ($competitor as $opponentVs) {
                if ($opponentVs !== $nrVs) {
                    return false;
                }
            }
        }
        return true;
    };
    $getMinimalVS = function (array $vs) : int {
        $minimalVS = 100000;
        foreach ($vs as $competitorNr => $competitor) {
            foreach ($competitor as $opponentNr => $nrVs) {
                if ($nrVs < $minimalVS) {
                    $minimalVS = $nrVs;
                }
            }
        }
        return $minimalVS;
    };

    $xDiff = 2;
    $minimalVS = $getMinimalVS($vs);
    $r = 0;
    $combinationItFailed = [];
    foreach ($combinationIt as $combination) {
        $r++;
        $vsCombinations = $mapVsCombination(getCombinations(count($combination), 2), $combination);
        if (!$lessThanXDiff($nrInBatches, $combination, $xDiff) /*|| !$lessThanXDiffVS($vs, $vsCombinations, $minimalVS, $xDiff+2 )*/) {
            $combinationIt/*Failed*/[] = $combination;
            continue;
        }

        foreach ($vsCombinations as $vsCombination) {
            $vs[ $vsCombination[0] ][ $vsCombination[1] ]++;
            $vs[ $vsCombination[1] ][ $vsCombination[0] ]++;
        }

        $minimalVS = $getMinimalVS($vs);

        foreach ($combination as $competitorNr) {
            if (!array_key_exists($competitorNr, $nrInBatches)) {
                $nrInBatches[$competitorNr] = 0;
            }
            $nrInBatches[$competitorNr]++;

            if ($nrInBatches[$competitorNr] > 24) {
                $xDiff = 1;
            }
            if ($nrInBatches[$competitorNr] > 26) {
                $s = "";
                foreach ($nrInBatches as $idx => $competitorNr) {
                    $s .= $idx . '=>' . $competitorNr .",";
                }
                echo "failed " . $s . PHP_EOL;
                return false;
            }
        }
        $successFulFnc[] = $combination;
        if (count($nrInBatches) === $totalCompetitors && $allAreTwentySix($nrInBatches)) {
            echo "major succes!" . PHP_EOL;
            // && $allHaveSixVs($vs, 6)
            return true;
        } // else {

         // }
    }

//    if( count( $combinationIt ) > count( $combinationItFailed ) ) {
//        return getTwentySix( $vs, $nrInBatches, $combinationItFailed, $totalCompetitors, $nrOfCompetitorsPerTwo, $successFulFnc );
//    }
//
//
//    $s = ""; foreach( $vs as $competitorNr => $competitor ) { $s .= "   competitor " . $competitorNr; foreach( $competitor as $opponentNr => $nrVs ) { $s .= ", " . $opponentNr . ' => ' . $nrVs;  } $s .= PHP_EOL; };
    echo "failed all comb tried " . PHP_EOL;
    return false;
}

function getVs(int $totalCompetitors): array
{
    $vs = [];
    for ($competitorNr = 1 ; $competitorNr <= $totalCompetitors ; $competitorNr++) {
        for ($opponentNr = 1 ; $opponentNr <= $totalCompetitors ; $opponentNr++) {
            if ($opponentNr === $competitorNr) {
                continue;
            }
            $vs[$competitorNr][$opponentNr] = 0;
        }
    }
    return $vs;
}

$successFul = [];
$vs = getVs($totalCompetitors);
$nrInBatches = [];
while (!getTwentySix($vs, $nrInBatches, $combinations, $totalCompetitors, $nrOfCompetitorsPerTwo, $successFul)) {
    shuffle($combinations);
    $successFul = [];
    $vs = getVs($totalCompetitors);
    $successFul = [];
    usleep(100);
}

$max = (($totalCompetitors-1) * ($totalCompetitors/2)); // * $nrOfCompetitorsPerTwo;
$batchNr = 1;
foreach ($successFul as $combination) {
    foreach ($competitorsPerTwo as $competitors) {
        echo 'batch ' . $batchNr . ', ' . $combination[$competitors[0]-1] . ', ' . $combination[$competitors[1]-1] . PHP_EOL;
    }
    if ($batchNr === $max) {
        break;
    }
    $batchNr++;
}
