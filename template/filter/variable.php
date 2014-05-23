<?php

namespace Nooku\Component\Emails;

use Nooku\Library;

class TemplateFilterVariable extends Library\TemplateFilterAbstract implements Library\TemplateFilterRenderer
{
    protected $_data;

    /**
     * Parse the text and render it
     *
     * @param string $text  The text to parse
     * @return void
     */
    public function render(&$text)
    {
        //Replace occurances of [XXX] with $this->getTemplate()->getView()->getData()[XXX]
        $text = preg_replace_callback('#\[([A-Z_\-0-9]+)\]#s', array($this, '_replaceData'), $text);
    }



    protected function _getViewData()
    {
        if($this->_data === null)
        {
            $data = $this->getTemplate()->getView()->getData();

            foreach($data AS $key => $value){
                $this->_data[strtoupper($key)] = is_array($value) ? implode(',', $value) : $value;
            }
        }

         return $this->_data;
    }



    protected function _replaceData($match)
    {
        $var = $match[1];
        $data = $this->_getViewData();

        if(isset($data[$var])) return $data[$var];
        else return $var;
    }
}