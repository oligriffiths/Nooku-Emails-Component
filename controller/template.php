<?php

namespace Oligriffiths\Component\Emails;

use Nooku\Library;

class ControllerTemplate extends Library\ControllerView
{
    /**
     * Initializes the default configuration for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param ObjectConfig $config An optional ObjectConfig object with configuration options.
     * @return void
     */
    protected function _initialize(Library\ObjectConfig $config)
    {
        $config->append(array(
            'formats'   => array('txt'),
        ));

        parent::_initialize($config);
    }
}