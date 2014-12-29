<?php

namespace Phlite\Mail\Parse\Tnef;

use Phlite\Text;

class TnefAttributeStreamReader extends TnefStreamReader {
    var $count = 0;

    const TypeUnspecified = 0x0;
    const TypeNull = 0x0001;
    const TypeInt16 = 0x0002;
    const TypeInt32 = 0x0003;
    const TypeFlt32 = 0x0004;
    const TypeFlt64 = 0x0005;
    const TypeCurency = 0x0006;
    const TypeAppTime = 0x0007;
    const TypeError = 0x000a;
    const TypeBoolean = 0x000b;
    const TypeObject = 0x000d;
    const TypeInt64 = 0x0014;
    const TypeString8 = 0x001e;
    const TypeUnicode = 0x001f;
    const TypeSystime = 0x0040;
    const TypeCLSID = 0x0048;
    const TypeBinary = 0x0102;

    const MAPI_NAMED_TYPE_ID = 0x0000;
    const MAPI_NAMED_TYPE_STRING = 0x0001;
    const MAPI_MV_FLAG = 0x1000;

    function __construct($stream) {
        $this->push($stream);
        /* Number of attributes. */
        $this->count = $this->_geti(32);
    }

    function valid() {
        return $this->count && $this->current;
    }

    function rewind() {
        $this->pos = 4;
    }

    protected function readPhpValue($type) {
        switch ($type) {
        case self::TypeUnspecified:
        case self::TypeNull:
        case self::TypeError:
            return null;

        case self::TypeInt16:
            return $this->_geti(16);

        case self::TypeInt32:
            return $this->_geti(32);

        case self::TypeBoolean:
            return (bool) $this->_geti(32);

        case self::TypeFlt32:
            list($f) = unpack('f', $this->_getx(8));
            return $f;

        case self::TypeFlt64:
            list($d) = unpack('d', $this->_getx(8));
            return $d;

        case self::TypeAppTime:
        case self::TypeCurency:
        case self::TypeInt64:
            return $this->_getx(8);

        case self::TypeSystime:
            $a = unpack('Vl/Vh', $this->_getx(8));
            // return FileTimeToU64(f) / 10000000 - 11644473600
            $ft = ($a['l'] / 10000000.0) + ($a['h'] * 429.4967296);
            return $ft - 11644473600;

        case self::TypeString8:
        case self::TypeUnicode:
        case self::TypeBinary:
        case self::TypeObject:
            $length = $this->_geti(32);

            /* Pad to next 4 byte boundary. */
            $datalen = $length + ((4 - ($length % 4)) % 4);

            // Chomp null terminator
            if ($type == self::TypeString8)
                --$length;
            elseif ($type == self::TypeUnicode)
                $length -= 2;

            /* Read and truncate to length. */
            $text = substr($this->_getx($datalen), 0, $length);
            if ($type == self::TypeUnicode) {
                $text = Text\Codec::decode($text, 'ucs-2le');
            }

            return $text;
        }
    }

    function next() {
        if ($this->count <= 0) {
            return $this->current = false;
        }

        $this->count--;

        $have_mval = false;
        $named_id = $value = null;
        $attr_type = $this->_geti(16);
        $attr_name = $this->_geti(16);
        $data_type = $attr_type & ~self::MAPI_MV_FLAG;

        if (($attr_type & self::MAPI_MV_FLAG) != 0
            // These are a "special case of multi-value attributes with
            // num_values=1
            || in_array($attr_type, array(
                self::TypeUnicode, self::TypeString8, self::TypeBinary,
                self::TypeObject))
        ) {
            $have_mval = true;
        }

        if (($attr_name >= 0x8000) && ($attr_name < 0xFFFE)) {
            $this->_getx(16);
            $named_type = $this->_geti(32);

            switch ($named_type) {
            case self::MAPI_NAMED_TYPE_ID:
                $named_id = $this->_geti(32);
                break;

            case self::MAPI_NAMED_TYPE_STRING:
                $attr_name = 0x9999;
                $named_id = $this->readPhpValue(self::TypeUnicode);
                break;
            }
        }

        if (!$have_mval) {
            $value = $this->readPhpValue($data_type);
        } else {
            $value = array();
            $k = $this->_geti(32);
            for ($i=0; $i < $k; $i++)
                $value[] = $this->readPhpValue($data_type);
        }

        if (is_array($value) && ($attr_type & self::MAPI_MV_FLAG) == 0)
            $value = $value[0];

        $this->current = array(
            'type' => $attr_type,
            'name' => $attr_name,
            'named_id' => $named_id,
            'value' => $value,
        );
    }

    function key() {
        return $this->current['name'];
    }
}
