<?php

namespace geotime\Test\Helper;

use Doctrine\ORM\EntityRepository;

abstract class EntityTestHelper extends MariaDbTestHelper {

    /**
     * @return EntityRepository
     */
    public abstract function getRepository();

}