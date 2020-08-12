<?php

namespace Sports\Priority;

class Service
{

    /**
     * @var array | Prioritizable[]
     */
    protected $prioritizables;

    public function __construct(array $prioritizables)
    {
        $this->prioritizables = $prioritizables;
    }

    /**
     * @return array | Prioritizable[]
     */
    public function validate(): array
    {
        $changed = [];

        uasort(
            $this->prioritizables,
            function (Prioritizable $prioritizableA, Prioritizable $prioritizableB): int {
                return $prioritizableA->getPriority() - $prioritizableB->getPriority();
            }
        );
        $priority = 1;
        /** @var Prioritizable $prioritizableIt */
        foreach ($this->prioritizables as $prioritizableIt) {
            if ($prioritizableIt->getPriority() !== $priority) {
                $prioritizableIt->setPriority($priority);
                $changed[] = $prioritizableIt;
            }
            $priority++;
        }
        return $changed;
    }

    /**
     * @param Prioritizable $prioritizable
     * @return array | Prioritizable[]
     */
    public function upgrade(Prioritizable $prioritizable): array
    {
        $changed = $this->validate();

        $upgradePrioritizable = $this->findByPriority($prioritizable->getPriority());
        $downgradePrioritizable = $this->findByPriority($prioritizable->getPriority() - 1);;
        if ($upgradePrioritizable === $prioritizable and $downgradePrioritizable !== null) {
            $upgradePrioritizable->setPriority($upgradePrioritizable->getPriority() - 1);
            if (array_search($upgradePrioritizable, $changed, true) === false) {
                $changed[] = $upgradePrioritizable;
            }
            $downgradePrioritizable->setPriority($downgradePrioritizable->getPriority() + 1);
            if (array_search($downgradePrioritizable, $changed, true) === false) {
                $changed[] = $downgradePrioritizable;
            }
        }

        return $changed;
    }

    protected function findByPriority(int $priority): ?Prioritizable
    {
        $foundPrioritizables = array_filter(
            $this->prioritizables,
            function (Prioritizable $prioritizable) use ($priority) : bool {
                return $prioritizable->getPriority() === $priority;
            }
        );
        if (count($foundPrioritizables) === 1) {
            return array_pop($foundPrioritizables);
        }
        return null;
    }
}
