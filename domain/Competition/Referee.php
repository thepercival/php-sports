<?php

namespace Sports\Competition;

use Sports\Competition;
use Sports\Person;
use Sports\Priority\Prioritizable;
use SportsHelpers\Identifiable;

class Referee extends Identifiable implements Prioritizable
{
    const MIN_LENGTH_INITIALS = 1;
    const MAX_LENGTH_INITIALS = 3;
    const MIN_LENGTH_NAME = 1;
    const MAX_LENGTH_NAME = 30;
    const MIN_LENGTH_EMAIL = 6;
    const MAX_LENGTH_EMAIL = 100;
    const MAX_LENGTH_INFO = 200;
    const DEFAULT_PRIORITY = 1;

    /**
     * @var int
     */
    protected $priority;
    /**
     * @var string
     */
    private $initials;
    /**
     * @var string|null
     */
    private $name;
    /**
     * @var string
     */
    private $emailaddress;
    /**
     * @var string
     */
    private $info;
    /**
     * @var Competition
     */
    private $competition;

    public function __construct(Competition $competition, int $priority = null)
    {
        $this->setCompetition($competition);
        if ($priority < self::DEFAULT_PRIORITY) {
            $priority = $competition->getReferees()->count();
        }
        $this->setPriority($priority);
    }

    private function setCompetition(Competition $competition)
    {
        $this->competition = $competition;
        $this->competition->getReferees()->add($this);
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return string
     */
    public function getInitials()
    {
        return $this->initials;
    }

    /**
     * @param string|null $initials
     */
    public function setInitials($initials)
    {
        if ($initials === null) {
            throw new \InvalidArgumentException("de initialen moet gezet zijn", E_ERROR);
        }
        if (strlen($initials) < static::MIN_LENGTH_INITIALS or strlen($initials) > static::MAX_LENGTH_INITIALS) {
            throw new \InvalidArgumentException(
                "de initialen moet minimaal " . static::MIN_LENGTH_INITIALS . " karakter bevatten en mag maximaal " . static::MAX_LENGTH_INITIALS . " karakters bevatten",
                E_ERROR
            );
        }
        if (!ctype_alnum($initials)) {
            throw new \InvalidArgumentException(
                "de initialen (" . $initials . ") mag alleen cijfers en letters bevatten",
                E_ERROR
            );
        }
        $this->initials = $initials;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(string $name = null)
    {
        if ($name !== null && (strlen($name) < static::MIN_LENGTH_NAME or strlen($name) > static::MAX_LENGTH_NAME)) {
            throw new \InvalidArgumentException(
                "de naam moet minimaal " . static::MIN_LENGTH_NAME . " karakters bevatten en mag maximaal " . static::MAX_LENGTH_NAME . " karakters bevatten",
                E_ERROR
            );
        }
        if ($name !== null && !preg_match('/^[a-z0-9 .\-]+$/i', $name)) {
            throw new \InvalidArgumentException(
                "de naam (" . $name . ") mag alleen cijfers, streeptes, slashes en spaties bevatten",
                E_ERROR
            );
        }
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getEmailaddress()
    {
        return $this->emailaddress;
    }

    /**
     * @param string $emailaddress
     */
    public function setEmailaddress($emailaddress)
    {
        if (strlen($emailaddress) > 0) {
            if (strlen($emailaddress) < static::MIN_LENGTH_EMAIL or strlen($emailaddress) > static::MAX_LENGTH_EMAIL) {
                throw new \InvalidArgumentException(
                    "het emailadres moet minimaal " . static::MIN_LENGTH_EMAIL . " karakters bevatten en mag maximaal " . static::MAX_LENGTH_EMAIL . " karakters bevatten",
                    E_ERROR
                );
            }

            if (!filter_var($emailaddress, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("het emailadres " . $emailaddress . " is niet valide", E_ERROR);
            }
        }
        $this->emailaddress = $emailaddress;
    }

    /**
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param string $info
     */
    public function setInfo($info)
    {
        if (strlen($info) > static::MAX_LENGTH_INFO) {
            $info = substr($info, 0, static::MAX_LENGTH_INFO);
        }
        $this->info = $info;
    }

    /**
     * @return Competition
     */
    public function getCompetition()
    {
        return $this->competition;
    }
}
