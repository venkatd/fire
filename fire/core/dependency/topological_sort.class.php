<?php

/**
 * Sorts a series of dependency pairs in linear order
 *
 * usage:
 * $t = new TopologicalSort($dependency_pairs);
 * $load_order = $t->tsort();
 *
 * where dependency_pairs is in the form:
 * $name => (depends on) $value
 *
 */
class TopologicalSort
{

    private $dependencies = array();
    private $nodes = array();

    /**
     * Dependency pairs are a list of arrays in the form
     * $name => $val where $key must come before $val in load order.
     *
     */
    function TopologicalSort(array $dependencies)
    {
        $this->dependencies = $dependencies;
        $this->nodes = $this->build_node_tree($dependencies);
    }

    function dependencies_of($item)
    {
        $sorted_dependencies = $this->sorted_dependencies();

        if (!$sorted_dependencies) //circular dependency
            return false;

        $visited = $this->visit_dependencies($item);

        foreach ($sorted_dependencies as $k => $dependency) {
            if (!isset($visited[$dependency]))
                unset($sorted_dependencies[$k]);
        }

        return $sorted_dependencies;
    }

    private $sorted_dependencies = null;
    function sorted_dependencies()
    {
        if ($this->sorted_dependencies === null) // false means circular dependency
            $this->sorted_dependencies = $this->compute_topological_sort();

        return $this->sorted_dependencies;
    }

    /**
     * Perform Topological Sort
     *
     * @param array $nodes optional array of node objects may be passed.
     * Default is  $this->nodes created in constructor.
     * @return sorted array
     */
    function compute_topological_sort()
    {
        $nodes = $this->nodes;

        // get nodes without parents
        $root_nodes = array_values($this->get_root_nodes($nodes));

        // begin algorithm
        $sorted = array();
        while (count($nodes) > 0) {
            // check for circular reference
            if (count($root_nodes) == 0) return false;

            // remove this node from root_nodes
            // and add it to the output
            $n = array_pop($root_nodes);
            $sorted[] = $n->name;

            // for each of its  children
            // queue the new node finally remove the original
            for ($i = (count($n->children) - 1); $i >= 0; $i--) {
                $childnode = $n->children[$i];
                // remove the link from this node to its
                // children ($nodes[$n->name]->children[$i]) AND
                // remove the link from each child to this
                // parent ($nodes[$childnode]->parents[?]) THEN
                // remove this child from this node
                unset($nodes[$n->name]->children[$i]);
                $parent_position = array_search($n->name, $nodes[$childnode]->parents);
                unset($nodes[$childnode]->parents[$parent_position]);
                // check if this child has other parents
                // if not, add it to the root nodes list
                if (!count($nodes[$childnode]->parents)) array_push($root_nodes, $nodes[$childnode]);
            }

            // nodes.Remove(n);
            unset($nodes[$n->name]);
        }

        return $sorted;
    }

    private function visit_dependencies($item)
    {
        $unvisited = is_string($item) ? array($item) : $item;
        $visited = array();

        while (count($unvisited) > 0) {
            $cur = array_pop($unvisited);

            if (isset($visited[$cur])) //already visisted
                continue;

            $cur_dependencies = isset($this->dependencies[$cur]) ? $this->dependencies[$cur] : array();
            $unvisited = array_merge($unvisited, $cur_dependencies);

            $visited[$cur] = true;
        }

        return $visited;
    }

    private function build_node_tree(array $dependencies)
    {
        $nodes = array();
        $dependency_pairs = $this->build_dependency_pairs($dependencies);
        // turn pairs into double-linked node tree
        foreach ($dependency_pairs as $key => $dpair) {
            list($module, $dependency) = each($dpair);
            if (!isset($nodes[$module]))
                $nodes[$module] = new TSNode($module);
            if (!isset($nodes[$dependency]))
                $nodes[$dependency] = new TSNode($dependency);
            if (!in_array($dependency, $nodes[$module]->children))
                $nodes[$module]->children[] = $dependency;
            if (!in_array($module, $nodes[$dependency]->parents))
                $nodes[$dependency]->parents[] = $module;
        }
        return $nodes;
    }

    /**
     * Returns a list of node objects that do not have parents
     *
     * @param array $nodes array of node objects
     * @return array of node objects
     */
    private function get_root_nodes($nodes)
    {
        $output = array();
        foreach ($nodes as $name => $node)
            if (!count($node->parents)) $output[$name] = $node;
        return $output;
    }

    /**
     * Parses an array of dependencies into an array of dependency pairs
     *
     * The array of dependencies would be in the form:
     * $dependency_list = array(
     *  "name" => array("dependency1","dependency2","dependency3"),
     *  "name2" => array("dependencyA","dependencyB","dependencyC"),
     *  ...etc
     * );
     *
     * @param array $dlist Array of dependency pairs for use as parameter in tsort method
     * @return array
     */
    private function build_dependency_pairs($dlist = array())
    {
        $output = array();
        foreach ($dlist as $name => $dependencies)
            foreach ($dependencies as $d) array_push($output, array($d => $name));
        return $output;
    }

}

/**
 * Node class for Topological Sort Class
 *
 */
class TSNode
{
    public $name;
    public $children = array();
    public $parents = array();

    function TSNode($name = "")
    {
        $this->name = $name;
    }
}
