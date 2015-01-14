<?php

namespace Oligriffiths\Component\Emails;

use Nooku\Library;

class ViewTxt extends Library\ViewHtml
{
    protected function _initialize(Library\ObjectConfig $config)
    {
        $config->append(array(
            'mimetype'         => 'text/plain',
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