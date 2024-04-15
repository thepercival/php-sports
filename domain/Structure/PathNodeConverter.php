<?php

declare(strict_types=1);

namespace Sports\Structure;

use Sports\Qualify\Target;
use Sports\Structure\PathNode as StructurePathNode;

class PathNodeConverter
{
    public function __construct() {
    }

    /**
     * @param string $pathNodeAsString
     * @return PathNode
     */
    public function createPathNode(string $pathNodeAsString): StructurePathNode|null
    {
        if( strlen($pathNodeAsString) === 0) {
            return null;
        }
        return $this->createRootPathNode($pathNodeAsString);
    }

    /**
     * @param  $pathNodeAsString
     * @return PathNode
     */
    private function createRootPathNode(string $pathNodeAsString): StructurePathNode
    {
        // root
        $qualifyTargetPos = $this->getPosQualifyTargetCharacter($pathNodeAsString, 0);
        if( $qualifyTargetPos === false ) {
            $rootNodeRoundNumber = (int)$pathNodeAsString;
            return new StructurePathNode(null, $rootNodeRoundNumber, null);
        }
        $rootNodeRoundNumber = (int)substr($pathNodeAsString, 0, $qualifyTargetPos);
        $rootPathNode = new StructurePathNode(null, $rootNodeRoundNumber, null);
        return $this->createPathNodeRecursive($pathNodeAsString, $qualifyTargetPos, $rootPathNode);
    }

    private function createPathNodeRecursive(string $pathNodeAsString, int $qualifyTargetPos, StructurePathNode $previous): StructurePathNode
    {
//        if( ($qualifyTargetPos + 1) >= strlen($pathNodeAsString) )  {
//            return $previous;
//        }
        $qualifyTarget = Target::from( substr($pathNodeAsString, $qualifyTargetPos, 1) );

        $roundNumberStartPos = $qualifyTargetPos + 1;
        $nextQualifyTargetPos = $this->getPosQualifyTargetCharacter($pathNodeAsString, $qualifyTargetPos + 1);
        if( $nextQualifyTargetPos === false) {
            $qualifyGroupNumber = (int)substr($pathNodeAsString, $roundNumberStartPos);
            return new StructurePathNode($qualifyTarget, $qualifyGroupNumber,$previous );
        }
        $qualifyGroupNumber = (int)substr($pathNodeAsString, $roundNumberStartPos, $nextQualifyTargetPos - $roundNumberStartPos);
        $pathNode = new StructurePathNode($qualifyTarget, $qualifyGroupNumber, $previous );
        return $this->createPathNodeRecursive($pathNodeAsString, $nextQualifyTargetPos, $pathNode);
    }

    /**
     * @param string $pathNodeAsString
     * @return int|false
     */
    private function getPosQualifyTargetCharacter(string $pathNodeAsString, int $fromIndex): int|false {

        $posFirstWinnersCharacter = strpos($pathNodeAsString, Target::Winners->value, $fromIndex);
        $posFirstLosersCharacter = strpos($pathNodeAsString, Target::Losers->value, $fromIndex);
        if( $posFirstWinnersCharacter === false && $posFirstLosersCharacter === false ) {
            return false;
        }
        if( $posFirstWinnersCharacter === false ) {
            return $posFirstLosersCharacter;
        }
        if( $posFirstLosersCharacter === false ) {
            return $posFirstWinnersCharacter;
        }
        return min($posFirstLosersCharacter, $posFirstWinnersCharacter);
    }

}
