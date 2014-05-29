<?php

namespace Oligriffiths\Component\Emails;

use Nooku\Library;
use Nooku\Library\ViewTemplate;

class ViewHtml extends Library\ViewHtml
{
    protected function _initialize(Library\ObjectConfig $config)
    {
        $config->append(array(
            'template_filters' => array('variable')
        ));

        parent::_initialize($config);
    }


    public function getData()
    {
        $data = parent::getData();
        unset($data['state']);

        return $data;
    }


    public function getRoute($route = '', $fqr = null, $escape = null)
    {
        return parent::getRoute($route, true, $escape);
    }
}