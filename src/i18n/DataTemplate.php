<?php

namespace Phlite\I18n;

class DataTemplate {
    // Base folder for default data and templates
    var $base = I18N_DIR;

    var $filepath;
    var $data;

    /**
     * Searches for the files matching the template in the order of the
     * received languages. Once matched, the language is captured so that
     * template itself does not have to keep track of the language for which
     * it is defined.
     */
    function DataTemplate($path, $langs=array('en_US')) {
        foreach ($langs as $l) {
            if (file_exists("{$this->base}/$l/$path")) {
                $this->lang = $l;
                $this->filepath = Misc::realpath("{$this->base}/$l/$path");
                break;
            }
            elseif (class_exists('Phar')
                    && Phar::isValidPharFilename("{$this->base}/$l.phar")
                    && file_exists("phar://{$this->base}/$l.phar/$path")) {
                $this->lang = $l;
                $this->filepath = "phar://{$this->base}/$l.phar/$path";
                break;
            }
        }
    }

    function getData() {
        if (!isset($this->data) && $this->filepath)
            $this->data = YamlDataParser::load($this->filepath);
            // TODO: If there was a parsing error, attempt to try the next
            //       language in the list of requested languages
        return $this->data;
    }

    function getRawData() {
        if (!isset($this->data) && $this->filepath)
            return file_get_contents($this->filepath);
            // TODO: If there was a parsing error, attempt to try the next
            //       language in the list of requested languages
        return false;
    }

    function getLang() {
        return $this->lang;
    }
}
