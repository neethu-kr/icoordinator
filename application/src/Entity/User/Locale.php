<?php

namespace iCoordinator\Entity\User;

use iCoordinator\Entity\AbstractEntity;

/**
 * @Entity
 * @Table(name="user_locales", options={"collate"="utf8_general_ci"})
 * @HasLifecycleCallbacks
 */

class Locale extends AbstractEntity
{
    const ENTITY_NAME = 'entity:User\Locale';

    const RESOURCE_ID = 'locale';

    const FIRST_WEEK_DAY_SUNDAY = 0;
    const FIRST_WEEK_DAY_MONDAY = 1;

    /**
     * @OneToOne(targetEntity="\iCoordinator\Entity\User", inversedBy="locale")
     * @JoinColumn(name="user_id", referencedColumnName="id", unique=true, nullable=false)
     **/
    protected $user;

    /**
     * @var string
     * @Column(type="string", length=2)
     */
    protected $lang = 'en';

    /**
     * @var string
     * @Column(type="string", length=50)
     */
    protected $date_format = 'dd/mm/yyyy';

    /**
     * @var string
     * @Column(type="string", length=50)
     */
    protected $time_format = 'HH:MM';

    /**
     * @var int
     * @Column(type="integer")
     */
    protected $first_week_day = self::FIRST_WEEK_DAY_MONDAY;


    public static function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    public function getResourceId()
    {
        return self::RESOURCE_ID;
    }

    public function jsonSerialize()
    {
        return array(
            'entity_type' => self::RESOURCE_ID,
            'id' => $this->getId(),
            'lang' => $this->getLang(),
            'date_format' => $this->getDateFormat(),
            'time_format' => $this->getTimeFormat(),
            'first_week_day' => $this->getFirstWeekDay()
        );
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @param $lang
     * @return $this
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
        return $this;
    }
    /**
     * @return string
     */
    public function getTimeFormat()
    {
        return $this->time_format;
    }

    /**
     * @param $time_format
     * @return $this
     */
    public function setTimeFormat($time_format)
    {
        $this->time_format = $time_format;
        return $this;
    }
    /**
     * @return string
     */
    public function getDateFormat()
    {
        return $this->date_format;
    }

    /**
     * @param mixed $date_format
     * @return $this
     */
    public function setDateFormat($date_format)
    {
        $this->date_format = $date_format;
        return $this;
    }
    /**
     * @return boolean
     */
    public function getFirstWeekDay()
    {
        return $this->first_week_day;
    }

    /**
     * @param $first_week_day
     * @return $this
     */
    public function setFirstWeekDay($first_week_day)
    {
        $this->first_week_day = $first_week_day;
        return $this;
    }
}
