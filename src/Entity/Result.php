<?php

/**
 * PHP version 7.4
 * src/Entity/Result.php
 *
 * @category Entities
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Class Result
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name    = "results",
 *     indexes = {
 *          @ORM\Index(name="FK_USER_ID_idx", columns={ "user_id" })
 *     }
 * )
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class Result implements JsonSerializable
{
    public const RESULT_ATTR = 'result';

    /**
     * Result id
     *
     * @ORM\Column(
     *     name     = "id",
     *     type     = "integer",
     *     nullable = false
     * )
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * Result value
     *
     * @ORM\Column(
     *     name     = "result",
     *     type     = "integer",
     *     nullable = false
     *     )
     */
    private int $result;

    /**
     * Result user
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(
     *          name                 = "user_id",
     *          referencedColumnName = "id",
     *          onDelete             = "cascade"
     *     )
     * })
     */
    private User $user;

    /**
     * Result time
     *
     * @ORM\Column(
     *     name     = "time",
     *     type     = "datetime",
     *     nullable = false
     *     )
     */
    private DateTime $time;

    /**
     * Result constructor.
     *
     * @param int $result result
     * @param User|null $user user
     * @param DateTime|null $time time
     */
    public function __construct(
        int $result = 0,
        ?User $user = null,
        ?DateTime $time = null
    ) {
        $this->id     = 0;
        $this->result = $result;
        $this->user   = $user;
        $this->time   = $time;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Implements __toString()
     *
     * @return string
     * @link   http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.tostring
     */
    public function __toString(): string
    {
        return sprintf(
            '%3d - %3d - %22s - %s',
            $this->id,
            $this->result,
            $this->user->getUsername(),
            $this->time->format('Y-m-d H:i:s')
        );
    }

    /**
     * @return int
     */
    public function getResult(): int
    {
        return $this->result;
    }

    /**
     * @param int $result
     */
    public function setResult(int $result): void
    {
        $this->result = $result;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     */
    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return DateTime|null
     */
    public function getTime(): ?DateTime
    {
        return $this->time;
    }

    /**
     * @param DateTime|null $time
     */
    public function setTime(?DateTime $time): void
    {
        $this->time = $time;
    }


    /**
     * Specify data which should be serialized to JSON
     *
     * @link   http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since  5.4.0
     */
    public function jsonSerialize(): array
    {
        return array(
            'id'     => $this->id,
            'result' => $this->result,
            'user'   => $this->user,
            'time'   => $this->time->format('Y-m-d H:i:s')
        );
    }
}
