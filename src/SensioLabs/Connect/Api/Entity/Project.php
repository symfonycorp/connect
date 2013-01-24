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
    const TYPE_CLI_APPLICATION = 3;
    const TYPE_SYMFONY_PLUGIN = 4;
    const TYPE_SYMFONY_DISTRIBUTION = 5;
    const TYPE_OTHER = 6;

    public static $types = array(
        self::TYPE_WEBSITE => 'Website',
        self::TYPE_LIBRARY => 'Library',
        self::TYPE_SYMFONY_BUNDLE => 'Symfony Bundle',
        self::TYPE_CLI_APPLICATION => 'CLI Application',
        self::TYPE_SYMFONY_PLUGIN => 'symfony 1.x plugin',
        self::TYPE_SYMFONY_DISTRIBUTION => 'Symfony Distribution',
        self::TYPE_OTHER => 'Other',
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
