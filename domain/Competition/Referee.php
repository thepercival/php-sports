<?php

declare(strict_types=1);

namespace Sports\Competition;

use Sports\Competition;
use Sports\Person;
use Sports\Priority\Prioritizable;
use SportsHelpers\Identifiable;

class Referee extends Identifiable implements Prioritizable
{
    private string $initials;
    protected int $priority;
    private string|null $name = null;
    private string|null $emailaddress = null;
    private string|null $info = null;

    public const MIN_LENGTH_INITIALS = 1;
    public const MAX_LENGTH_INITIALS = 3;
    public const MIN_LENGTH_NAME = 1;
    public const MAX_LENGTH_NAME = 30;
    public const MIN_LENGTH_EMAIL = 6;
    public const MAX_LENGTH_EMAIL = 100;
    public const MAX_LENGTH_INFO = 200;
    public const DEFAULT_PRIORITY = 1;

    public function __construct(private Competition $competition, string $initials, int $priority = null)
    {
        $this->setCompetition($competition);
        $this->setInitials($initials);
        if ($priority === null || $priority < self::DEFAULT_PRIORITY) {
            $priority = $competition->getReferees()->count();
        }
        $this->setPriority($priority);
    }

    private function setCompetition(Competition $competition): void
    {
        $this->competition = $competition;
        $this->competition->getReferees()->add($this);
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    final public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getInitials(): string
    {
        return $this->initials;
    }

    final public function setInitials(string $initials): void
    {
        if (strlen($initials) < self::MIN_LENGTH_INITIALS or strlen($initials) > self::MAX_LENGTH_INITIALS) {
            throw new \InvalidArgumentException(
                "de initialen moet minimaal " . self::MIN_LENGTH_INITIALS . " karakter bevatten en mag maximaal " . self::MAX_LENGTH_INITIALS . " karakters bevatten",
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name = null): void
    {
        if ($name !== null && (strlen($name) < self::MIN_LENGTH_NAME or strlen($name) > self::MAX_LENGTH_NAME)) {
            throw new \InvalidArgumentException(
                "de naam moet minimaal " . self::MIN_LENGTH_NAME . " karakters bevatten en mag maximaal " . self::MAX_LENGTH_NAME . " karakters bevatten",
                E_ERROR
            );
        }
        if ($name !== null && preg_match('/^[a-z0-9 .\-]+$/i', $name) === 0) {
            throw new \InvalidArgumentException(
                "de naam (" . $name . ") mag alleen cijfers, streeptes, slashes en spaties bevatten",
                E_ERROR
            );
        }
        $this->name = $name;
    }

    public function getEmailaddress(): string|null
    {
        return $this->emailaddress;
    }

    public function setEmailaddress(string|null $emailaddress): void
    {
        if ($emailaddress !== null && strlen($emailaddress) > 0) {
            if (strlen($emailaddress) < self::MIN_LENGTH_EMAIL or strlen($emailaddress) > self::MAX_LENGTH_EMAIL) {
                throw new \InvalidArgumentException(
                    "het emailadres moet minimaal " . self::MIN_LENGTH_EMAIL . " karakters bevatten en mag maximaal " . self::MAX_LENGTH_EMAIL . " karakters bevatten",
                    E_ERROR
                );
            }

            if (filter_var($emailaddress, FILTER_VALIDATE_EMAIL) === false) {
                throw new \InvalidArgumentException("het emailadres " . $emailaddress . " is niet valide", E_ERROR);
            }
        }
        $this->emailaddress = $emailaddress;
    }

    public function getInfo(): string|null
    {
        return $this->info;
    }

    public function setInfo(string|null $info): void
    {
        if ($info !== null && strlen($info) > self::MAX_LENGTH_INFO) {
            $info = substr($info, 0, self::MAX_LENGTH_INFO);
        }
        $this->info = $info;
    }

    public function getCompetition(): Competition
    {
        return $this->competition;
    }
}
