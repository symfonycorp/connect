<?php

/*
 * This file is part of the SensioLabs Connect package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SensioLabs\Connect\Api\Entity;

/**
 * Club
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class Club extends AbstractEntity
{
    const TYPE_COMPANY    = 1;
    const TYPE_USER_GROUP = 2;
    const TYPE_FUN        = 3;
    const TYPE_TEAM       = 4;

    public static $types = array(
        self::TYPE_COMPANY    => 'Company',
        self::TYPE_USER_GROUP => 'Local user group',
        self::TYPE_FUN        => 'Just for fun',
        self::TYPE_TEAM       => 'Team of developers',
    );

    protected function configure()
    {
        $this->addProperty('name')
             ->addProperty('uuid')
             ->addProperty('slug')
             ->addProperty('type')
             ->addProperty('email')
             ->addProperty('description')
             ->addProperty('city')
             ->addProperty('country')
             ->addProperty('url')
             ->addProperty('feedUrl')
             ->addProperty('members', array())
             ->addProperty('image')
             ->addProperty('cumulatedBadges')
             ->addProperty('badges')
        ;
    }

    public function getTextualType()
    {
        return self::$types[$this->get('type')];
    }

    public function getTypeFromTextual($type)
    {
        return array_search($type, self::$types);
    }
}
