<?php

class PHPClassParser
{

    function get_file_classes($filepath)
    {
        return $this->get_classes(file_get_contents($filepath));
    }

    function get_classes($php_code)
    {
        $classes = array();
        $tokens = $this->get_tokens($php_code);

        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if ($this->is_class($tokens, $i)) {
                $class = $this->get_class($tokens, $i);
                $classes[$class['name']] = $class;
            }
        }

        return $classes;
    }

    private function get_tokens($php_code)
    {
        $tokens = array();
        foreach (token_get_all($php_code) as $token) {

            if (is_array($token))
                $token['name'] = token_name($token[0]);

            // white space makes parsing more annoying, so drop all white space tokens
            if ($token[0] != T_WHITESPACE)
                $tokens[] = $token;
        }
        return $tokens;
    }

    private function is_class($tokens, $i)
    {
        return $tokens[$i - 1][0] == T_CLASS
               && $tokens[$i][0] == T_STRING;
    }

    private function get_class($tokens, &$i)
    {
        $count = count($tokens);

        $class = array(
            'name' => $tokens[$i][1],
            'abstract' => $tokens[$i - 2][0] == T_ABSTRACT,
        );

        // skip ahead until we hit the { (start of the class)
        while (!$this->is_open_bracket($tokens, $i)) {
            if ($tokens[$i][0] == T_EXTENDS) {
                $class['parent'] = $tokens[$i + 1][1];
            }
            $i++;
        }

        $unclosed_brackets = 0;
        do {
            if ($this->is_open_bracket($tokens, $i)) {
                $unclosed_brackets++;
            }
            elseif ($this->is_closed_bracket($tokens, $i)) {
                $unclosed_brackets--;
            }
            elseif ($this->is_method($tokens, $i)) {
                $method = $this->get_method($tokens, $i);
                $class['methods'][$method['name']] = $method;
            }
//            elseif ($this->is_var($tokens, $i)) {
//                $var = $this->get_var($tokens, $i);
//                $class['vars'][$var['name']] = $var;
//            }

            $i++;

            // Keep going until we hit the end of the class.
            // This is when all brackets have been closed.
        } while ($unclosed_brackets > 0 && $i < $count);

        return $class;
    }

    private function is_open_bracket($tokens, $i)
    {
        return $tokens[$i] == '{'
               || is_array($tokens[$i]) && $tokens[$i][1] == '{';
    }

    private function is_closed_bracket($tokens, $i)
    {
        return $tokens[$i] == '}';
    }

    private function is_var($tokens, $i)
    {
        return $tokens[$i][0] == T_VARIABLE;
    }

    private function get_var($tokens, &$i)
    {
        $var = array();
        $var['name'] = $tokens[$i][1];

        $value_str = '';

        while ($tokens[$i] != '=')
            $i++;

        $i++;

        while ($tokens[$i] != ';') {
            $value_str .= is_string($tokens[$i]) ? $tokens[$i] : $tokens[$i][1];
            $i++;
        }

        $i++;

        $value = null;
        eval('$value = ' . $value_str . ';');

        $var['value'] = $value;

        return $var;
    }

    private function is_method($tokens, $i)
    {
        return is_array($tokens[$i])
               && $tokens[$i - 1][0] == T_FUNCTION
               && $tokens[$i][0] == T_STRING;
    }

    private function get_method($tokens, &$i)
    {
        $method = array(
            'name' => $tokens[$i][1],
        );
        $method += $this->get_method_modifiers($tokens, $i);
        $method['arguments'] = $this->get_method_arguments($tokens, $i);

        $method['min_arguments'] = 0;
        foreach ($method['arguments'] as $argument) {
            if ($argument['optional'] == false)
                $method['min_arguments']++;
        }

        return $method;
    }

    private function get_method_modifiers($tokens, $i)
    {
        $modifiers = array(T_PUBLIC, T_PROTECTED, T_PRIVATE, T_ABSTRACT, T_FINAL, T_STATIC);
        $modifiers = array_fill_keys($modifiers, false);

        // $i points to T_STRING which is function name. $i - 4 points to first possible modifier.
        $i -= 2;
        while (isset($modifiers[$tokens[$i][0]]) && $i > 0) {
            $modifiers[$tokens[$i][0]] = true;
            // move in increments of two because T_WHITESPACE is in between every modifier
            $i -= 1;
        }

        return array(
            'public' => !$modifiers[T_PRIVATE] && !$modifiers[T_PROTECTED], // public is implied when there are no private modifiers
            'protected' => $modifiers[T_PROTECTED],
            'private' => $modifiers[T_PRIVATE],
            'abstract' => $modifiers[T_ABSTRACT],
            'final' => $modifiers[T_FINAL],
            'static' => $modifiers[T_STATIC],
        );
    }

    private function get_method_arguments($tokens, $i)
    {
        $arguments = array();

        while ($tokens[$i] != '(') $i++; // Go to first (

        $unclosed_parens = 0;
        $argument_position = 0;

        do {
            if ($tokens[$i] == '(') {
                $unclosed_parens++;
            }
            elseif ($tokens[$i] == ')') {
                $unclosed_parens--;
            }
            elseif ($this->is_argument($tokens, $i)) {
                $argument = $this->get_argument($tokens, $i);
                $argument['position'] = $argument_position++;
                
                $arguments[$argument['name']] = $argument;
            }

            $i++;
        } while ($unclosed_parens > 0);

        return $arguments;
    }

    private function is_argument($tokens, $i)
    {
        return $tokens[$i][0] == T_VARIABLE;
    }

    private function get_argument($tokens, $i)
    {
        $arg = array(
            'name' => substr($tokens[$i][1], 1), // remove $ sign
        );

        // determine argument type
        if ($tokens[$i - 1] != '(') {
            if ($tokens[$i - 1][0] == T_ARRAY) {
                $arg['type'] = 'array';
            }
            elseif ($tokens[$i - 1][0] == T_STRING) {
                $arg['type'] = $tokens[$i - 1][1];
            }
        }

        if ($this->has_default_argument($tokens, $i)) {
            $arg['default'] = $this->get_default_argument($tokens, $i);
            $arg['optional'] = true;
        }
        else {
            $arg['optional'] = false;
        }

        return $arg;
    }

    private function has_default_argument($tokens, $i)
    {
        return $tokens[$i + 1] == '=';
    }

    private function get_default_argument($tokens, $i)
    {
        $unclosed_parens = 0;
        // advanced to after the = sign, when the default arg begins
        $i += 2;
        $default = '';
        do {
            if ($tokens[$i] == '(') {
                $unclosed_parens++;
            }
            elseif ($tokens[$i] == ')') {
                $unclosed_parens--;
            }

            $default .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
            $i++;

            // We must keep track of number of parenthesis because of arrays as defaults
            // We break the loop when we hit a , or a ) since that means we are at the next
            // argument or at end the end of arguments.
        } while (!($unclosed_parens == 0 && ($tokens[$i] == ',' || $tokens[$i] == ')')));

        return $default;
    }

}
