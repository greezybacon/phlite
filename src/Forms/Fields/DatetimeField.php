<?php

namespace Phlite\Forms\Fields;

use Phlite\Forms\Field;

class DatetimeField extends Field {
    static $widget = 'DatetimePickerWidget';

    function to_database($value) {
        // Store time in gmt time, unix epoch format
        return (string) $value;
    }

    function to_php($value) {
        if (!$value)
            return $value;
        else
            return (int) $value;
    }

    function parse($value) {
        if (!$value) return null;
        $config = $this->getConfiguration();
        return ($config['gmt']) ? Misc::db2gmtime($value) : $value;
    }

    function toString($value) {
        global $cfg;
        $config = $this->getConfiguration();
        $format = ($config['time'])
            ? $cfg->getDateTimeFormat() : $cfg->getDateFormat();
        if ($config['gmt'])
            // Return time local to user's timezone
            return Format::userdate($format, $value);
        else
            return Format::date($format, $value);
    }

    function export($value) {
        $config = $this->getConfiguration();
        if (!$value)
            return '';
        elseif ($config['gmt'])
            return Format::userdate('Y-m-d H:i:s', $value);
        else
            return Format::date('Y-m-d H:i:s', $value);
    }

    function getConfigurationOptions() {
        return array(
            'time' => new BooleanField(array(
                'id'=>1, 'label'=>'Time', 'required'=>false, 'default'=>false,
                'configuration'=>array(
                    'desc'=>'Show time selection with date picker'))),
            'gmt' => new BooleanField(array(
                'id'=>2, 'label'=>'Timezone Aware', 'required'=>false,
                'configuration'=>array(
                    'desc'=>"Show date/time relative to user's timezone"))),
            'min' => new DatetimeField(array(
                'id'=>3, 'label'=>'Earliest', 'required'=>false,
                'hint'=>'Earliest date selectable')),
            'max' => new DatetimeField(array(
                'id'=>4, 'label'=>'Latest', 'required'=>false,
                'default'=>null)),
            'future' => new BooleanField(array(
                'id'=>5, 'label'=>'Allow Future Dates', 'required'=>false,
                'default'=>true, 'configuration'=>array(
                    'desc'=>'Allow entries into the future'))),
        );
    }

    function validateEntry($value) {
        $config = $this->getConfiguration();
        parent::validateEntry($value);
        if (!$value) return;
        if ($config['min'] and $value < $config['min'])
            $this->_errors[] = 'Selected date is earlier than permitted';
        elseif ($config['max'] and $value > $config['max'])
            $this->_errors[] = 'Selected date is later than permitted';
        // strtotime returns -1 on error for PHP < 5.1.0 and false thereafter
        elseif ($value === -1 or $value === false)
            $this->_errors[] = 'Enter a valid date';
    }
}
