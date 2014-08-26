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
 * @todo Override setApi to apply it to children User
 */
class Project extends AbstractEntity
{
    const TYPE_WEBSITE = 0; // This is deprecated. You should not use it
    const TYPE_LIBRARY = 1; // This is deprecated. You should not use it
    const TYPE_SYMFONY_BUNDLE = 2; // This is deprecated. You should not use it
    const TYPE_CLI_APPLICATION = 1; // This is deprecated. You should not use it
    const TYPE_SYMFONY_PLUGIN = 4; // This is deprecated. You should not use it
    const TYPE_SYMFONY_DISTRIBUTION = 10; // This is deprecated. You should not use it

    const TYPE_PHP_WEBSITE          = 0;
    const TYPE_PHP_LIBRARY          = 1;
    const TYPE_SYMFONY2_BUNDLE      = 2;
    const TYPE_SYMFONY1_PLUGIN      = 4;
    const TYPE_OTHER                = 6;
    const TYPE_DRUPAL_MODULE        = 7;
    const TYPE_LARAVAL_WEB_PROJECT  = 8;
    const TYPE_SILEX_WEB_PROJECT    = 9;
    const TYPE_SYMFONY2_WEB_PROJECT = 10;
    const TYPE_SYMFONY1_WEB_PROJECT = 11;

    public static $types = array(
        self::TYPE_SYMFONY2_WEB_PROJECT => 'Symfony2 Web Project',
        self::TYPE_SYMFONY1_WEB_PROJECT => 'symfony1 Web Project',
        self::TYPE_SILEX_WEB_PROJECT    => 'Silex Web Project',
        self::TYPE_LARAVAL_WEB_PROJECT  => 'Laravel Web Project',
        self::TYPE_SYMFONY2_BUNDLE      => 'Symfony2 Bundle',
        self::TYPE_SYMFONY1_PLUGIN      => 'symfony1 Plugin',
        self::TYPE_DRUPAL_MODULE        => 'Drupal Module',
        self::TYPE_PHP_WEBSITE          => 'PHP Web Project',
        self::TYPE_PHP_LIBRARY          => 'PHP Library',
        self::TYPE_OTHER                => 'Other',
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
             ->addProperty('slug')
             ->addProperty('uuid')
             ->addProperty('isPrivate')
             ->addProperty('description')
             ->addProperty('image')
             ->addProperty('type')
             ->addProperty('url')
             ->addProperty('repositoryUrl')
             ->addProperty('isInternalGitRepositoryCreated')
             ->addProperty('createRepository') // Boolean to order a creation of private git repository
             ->addProperty('pictureFile')
        ;
    }
}
