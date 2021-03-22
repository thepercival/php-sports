<?php

namespace Sports\Association;

use Sports\Association;

class Service
{
    public function __construct()
    {
    }

    public function changeParent(Association $association, Association $parentAssociation = null): Association
    {
        $descendantMap = $this->getDescendantMap($association);
        $descendantMap[$association->getId()] = $association;
        if ($parentAssociation !== null) {
            $ancestors = $this->getAncestors($parentAssociation, [$parentAssociation]);
            foreach ($ancestors as $ancestor) {
                if (array_key_exists($ancestor->getId(), $descendantMap)) {
                    throw new \Exception("er ontstaat een circulaire relatie tussen de bonden", E_ERROR);
                }
            }
        }
        $association->setParent($parentAssociation);
        return $association;
    }

    /**
     * @param Association $association
     * @param array<int|string, Association>|null $descendants
     * @return array<int|string, Association>
     */
    protected function getDescendantMap(Association $association, array|null $descendants = null): array
    {
        if ($descendants === null) {
            $descendants = [];
        }
        foreach ($association->getChildren() as $child) {
            $descendants[$association->getId()] = $association;
            $descendants = array_merge($descendants, $this->getDescendantMap($child, $descendants));
        }
        return $descendants;
    }

    /**
     * @param Association $association
     * @param list<Association>|null $ancestors
     * @return list<Association>
     */
    protected function getAncestors(Association $association, array|null $ancestors = null): array
    {
        if ($ancestors === null) {
            $ancestors = [];
        }
        $parent = $association->getParent();
        if ($parent === null) {
            return $ancestors;
        }
        $ancestors[] = $parent;
        return $this->getAncestors($parent, $ancestors);
    }
}
