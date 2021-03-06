<?php

namespace Matthias\SymfonyConfigTest\Tests\Partial;

use Matthias\SymfonyConfigTest\Partial\PartialNode;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\PrototypedArrayNode;

class PartialNodeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_strips_children_that_are_not_in_the_given_path_with_one_name()
    {
        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root('root');
        $root
            ->children()
                ->arrayNode('node_1')
                    ->children()
                        ->scalarNode('node_1_scalar_node')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('node_2')
                    ->children()
                        ->scalarNode('node_2_scalar_node');

        $node = $treeBuilder->buildTree();
        /** @var ArrayNode $node */

        PartialNode::excludeEverythingNotInPath($node, array('node_2'));

        $this->nodeOnlyHasChild($node, 'node_2');
    }

    /**
     * @test
     */
    public function it_strips_children_that_are_not_in_the_given_path_with_several_names()
    {
        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root('root');
        $root
            ->children()
                ->arrayNode('node_1')
                    ->children()
                        ->arrayNode('node_1_a')
                            ->children()
                                ->scalarNode('scalar_node')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('node_1_b')
                            ->children()
                                ->scalarNode('scalar_node')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('node_2')
                    ->children()
                        ->scalarNode('scalar_node');

        $node = $treeBuilder->buildTree();
        /** @var ArrayNode $node */

        PartialNode::excludeEverythingNotInPath($node, array('node_1', 'node_1_b'));

        $node1 = $this->nodeOnlyHasChild($node, 'node_1');
        $this->nodeOnlyHasChild($node1, 'node_1_b');
    }

    /**
     * @test
     */
    public function it_strips_children_when_leaf_node_is_not_an_array()
    {
        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root('root');
        $root
            ->children()
                ->arrayNode('node_1')
                    ->children()
                        ->scalarNode('node_1_scalar_node')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('node_2')
                ->end()
                ->scalarNode('node_3');

        $node = $treeBuilder->buildTree();
        /** @var ArrayNode $node */

        PartialNode::excludeEverythingNotInPath($node, array('node_3'));

        $this->nodeOnlyHasChild($node, 'node_3');
    }

    /**
     * @test
     */
    public function it_does_not_crash_on_prototypes()
    {
        $treeBuilder = new TreeBuilder();
        /** @var ArrayNodeDefinition $root */
        $root = $treeBuilder->root('root');
        $root
            ->prototype('array')
                ->children()
                    ->arrayNode('node_1')
                        ->children()
                            ->scalarNode('node_1_scalar_node')->end()
                        ->end()
                    ->end()
                    ->arrayNode('node_2')
                        ->children()
                            ->scalarNode('node_2_scalar_node')
        ;

        /** @var PrototypedArrayNode $node */
        $node = $treeBuilder->buildTree();

        /** @var ArrayNode $prototypeNode */
        $prototypeNode = $node->getPrototype();

        PartialNode::excludeEverythingNotInPath($node, array('*', 'node_1'));

        $this->nodeOnlyHasChild($prototypeNode, 'node_1');
    }

    /**
     * @test
     * @expectedException \Matthias\SymfonyConfigTest\Partial\Exception\UndefinedChildNode
     * @expectedExceptionMessage Undefined child node "non_existing_node" (the part of the path that was successful: "root.sub_node")
     */
    public function it_fails_when_a_requested_child_node_does_not_exist()
    {
        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root('root');
        $root
            ->children()
                ->arrayNode('sub_node')
                    ->children()
                        ->arrayNode('sub_sub_tree');

        $node = $treeBuilder->buildTree();

        PartialNode::excludeEverythingNotInPath($node, array('sub_node', 'non_existing_node'));
    }

    /**
     * @test
     * @expectedException \Matthias\SymfonyConfigTest\Partial\Exception\ChildIsNotAnArrayNode
     * @expectedExceptionMessage Child node "scalar_node" is not an array node (current path: "root.sub_node")
     */
    public function it_fails_when_a_requested_child_node_is_no_array_node_itself_and_path_not_empty()
    {
        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root('root');
        $root
            ->children()
                ->arrayNode('sub_node')
                    ->children()
                        ->scalarNode('scalar_node');

        $node = $treeBuilder->buildTree();

        PartialNode::excludeEverythingNotInPath($node, array('sub_node', 'scalar_node', 'extra_node'));
    }

    private function nodeOnlyHasChild(ArrayNode $node, $nodeName)
    {
        $property = new \ReflectionProperty($node, 'children');
        $property->setAccessible(true);
        $children = $property->getValue($node);

        $this->assertCount(1, $children);
        $firstChild = reset($children);
        $this->assertSame($nodeName, $firstChild->getName());

        return $firstChild;
    }
}
