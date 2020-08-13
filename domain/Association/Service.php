<?php

namespace Sports\Association;

use Sports\Association;
use Sports\Association\Repository as AssociationRepository;

class Service
{
    public function __construct()
    {
    }

    public function changeParent(Association $association, Association $parentAssociation = null)
    {
        $descendants = $this->getDescendants($association);
        $descendants[$association->getId()] = $association;
        if ($parentAssociation !== null) {
            $ancestors = $this->getAncestors($parentAssociation);
            $ancestors[$parentAssociation->getId()] = $parentAssociation;
            foreach ($ancestors as $ancestor) {
                if (array_key_exists($ancestor->getId(), $descendants)) {
                    throw new \Exception("er ontstaat een circulaire relatie tussen de bonden", E_ERROR);
                }
            }
        }
        $association->setParent($parentAssociation);
        return $association;
    }

    protected function getDescendants(Association $association)
    {
        $descendants = [];
        $this->getDescendantsHelper($association, $descendants);
        return $descendants;
    }

    protected function getDescendantsHelper(Association $association, &$descendants)
    {
        foreach ($association->getChildren() as $child) {
            $descendants[$association->getId()] = $association;
            $this->getDescendantsHelper($child, $descendants);
        }
    }

    protected function getAncestors(Association $association)
    {
        $ancestors = [];
        $this->getAncestorsHelper($association, $ancestors);
        return $ancestors;
    }

    protected function getAncestorsHelper(Association $association, &$ancestors)
    {
        if ($association->getParent() !== null) {
            $ancestors[$association->getParent()->getId()] = $association->getParent();
            $this->getAncestorsHelper($association->getParent(), $descendants);
        }
    }
}
