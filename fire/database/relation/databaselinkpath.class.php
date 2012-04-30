<?php

class DatabaseLinkPath
{

    /* @var $links DatabaseLink */
    public $links = array();

    function __construct($links = array())
    {
        $this->add_links($links);
    }

    function __clone()
    {
        foreach ($this->links as &$link) {
            $link = clone $link;
        }
    }

    /**
     * @return DatabaseLinkPath
     */
    function reverse()
    {
        $link_path = clone $this;
        $link_path->links = array_reverse($link_path->links);
        /* @var $link DatabaseTableLink */
        foreach ($link_path->links as &$link) {
            $link = $link->reverse();
        }
        return $link_path;
    }

    /**
     * @return DatabaseTableLink
     */
    function add_link_path(DatabaseLinkPath $path)
    {
        $sum = clone $this;
        $sum->add_links($path->links);
        return $sum;
    }

    function add_link(DatabaseLink $link)
    {
        $this->links[] = $link;
    }

    function add_links(array $links)
    {
        foreach ($links as $link) {
            $this->add_link($link);
        }
    }

    function get_link_alias(DatabaseLink $target_link)
    {
        $current_path = array();
        foreach ($this->links as $link) {
            /* @var $link DatabaseTableLink */
            if ($link instanceof DatabaseTableLink) {
                $current_path[] = $link->left_table->name()
                        . ':' . $link->left_column->name()
                        . ':' . $link->right_column->name()
                        . ':' . $link->right_table->name();
            }

            if ($link == $target_link) {
                $table_name = $link instanceof DatabaseTableLink
                                    ? $link->right_table->name()
                                    : $link->left_table->name();
                return $table_name . '_' . substr(md5(implode('->', $current_path)), 0, 4);
            }
        }

        return null;
    }

    function __toString()
    {
        $str = array();
        foreach ($this->links as $link) {
            $str[] = strval($link);
        }
        return implode(', ', $str);
    }

}
