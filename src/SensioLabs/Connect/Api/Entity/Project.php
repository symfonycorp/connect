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
 * Project
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 * @todo Override setApi to apply it to children Contributor/User
 */
class Project extends AbstractEntity
{
    const TYPE_WEBSITE = 0;
    const TYPE_LIBRARY = 1;
    const TYPE_SYMFONY_BUNDLE = 2;

    public static $types = array(
        self::TYPE_WEBSITE        => 'Website',
        self::TYPE_LIBRARY        => 'Library',
        self::TYPE_SYMFONY_BUNDLE => 'Symfony Bundle',
    );

    public function getTextualType()
    {
        return self::$types[$this->get('type')];
    }

    public function getTypeFromTextual($type)
    {
        return array_search($type, self::$types);
    }

    protected function configure()
    {
        $this->addProperty('name')
             ->addProperty('uuid')
             ->addProperty('slug')
             ->addProperty('isPrivate')
             ->addProperty('description')
             ->addProperty('image')
             ->addProperty('type')
             ->addProperty('url')
             ->addProperty('repositoryUrl')
             ->addProperty('pictureFile')
             ->addProperty('contributors')
        ;
    }
}

