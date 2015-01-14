<?php

namespace Oligriffiths\Component\Emails;

use Nooku\Library;

class TemplateFilterVariable extends Library\TemplateFilterAbstract
{
    protected $_data;


    /**
     * Parse the text and render it
     *
     * @param string $text  The text to parse
     * @return void
     */
    public function filter(&$text)
    {
        //Replace occurrences of {{XXX}} with $this->getTemplate()->getData()[XXX]
        $text = preg_replace_callback('#\{\{([A-Z_\-0-9]+)\}\}#s', array($this, '_replaceData'), $text);

        //We do this twice so that any variables with variables inside them are replaced
        $text = preg_replace_callback('#\{\{([A-Z_\-0-9]+)\}\}#s', array($this, '_replaceData'), $text);
    }


    /**
     * Returns the view data with all keys returned in uppcase and arrays imploded
     * @return null
     */
    protected function _getViewData()
    {
        if($this->_data === null)
        {
            $data = $this->getTemplate()->getData();

            $this->_data = array();

            foreach($data AS $key => $value){
                $this->_data[strtoupper($key)] = is_array($value) ? implode(',', $value) : $value;
            }
        }

         return $this->_data;
    }


    /**
     * Replacement function for the regex callback
     *
     * @param $match
     * @return mixed
     */
    protected function _replaceData($match)
    {
        $var = $match[1];
        $data = $this->_getViewData();

        if(isset($data[$var])) return $data[$var];
        else return $var;
    }
}