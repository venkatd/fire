<?php

class form
{

    static function input($name, $value = '', $attributes = array())
    {
        $attributes['type'] = 'text';
        $attributes['name'] = $name;
        $attributes['value'] = $value;
        return html_element('input', $attributes);
    }

    static function hidden($name, $value, $attributes = array())
    {
        $attributes['type'] = 'hidden';
        $attributes['name'] = $name;
        $attributes['value'] = $value;
        return html_element('input', $attributes);
    }

    static function dropdown($name, $options, $selected = null, $attributes = array())
    {
        $html = array();

        $attributes['name'] = $name;
        $html[] = html_element_open('select', $attributes);

        if (!$selected)
            $selected = key($options);

        foreach ($options as $value => $title) {
            $option_attributes = array('value' => $value);
            if ($value == $selected)
                $option_attributes['selected'] = 'selected';
            $html[] = html_element('option', $option_attributes, $title);
        }

        $html[] = html_element_close('select');

        return implode("\n", $html);
    }

}

