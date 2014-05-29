<?php
/**
 * Author: Oli Griffiths <github.com/oligriffiths>
 * Date: 29/05/2014
 * Time: 17:37
 */

\Nooku\Library\ClassLoader::getInstance()->getLocator('component')->registerNamespaces(
    array(
        'Oligriffiths\Component\Emails' => JPATH_VENDOR.'/oligriffiths/component/emails'
    )
);