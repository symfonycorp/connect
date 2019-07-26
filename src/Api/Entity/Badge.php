<?php

/*
 * This file is part of the SymfonyConnect package.
 *
 * (c) Symfony <support@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SymfonyCorp\Connect\Api\Entity;

/**
 * @method self   setId(int $id)
 * @method int    getId()
 * @method self   setCount(int $count)
 * @method int    getCount()
 * @method self   setName(string $name)
 * @method string getName()
 * @method self   setDescription(string $description)
 * @method string getDescription()
 * @method self   setLevel(int $level)
 * @method int    getLevel()
 * @method self   setImage(string $image)
 * @method string getImage()
 *
 * @author Marc Weistroff <marc.weistroff@sensiolabs.com>
 */
class Badge extends AbstractEntity
{
    public function configure()
    {
        $this->addProperty('id')
             ->addProperty('count')
             ->addProperty('name')
             ->addProperty('description')
             ->addProperty('level')
             ->addProperty('image');
    }
}
