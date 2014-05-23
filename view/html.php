<?php

namespace Nooku\Component\Emails;

use Nooku\Library;

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
}