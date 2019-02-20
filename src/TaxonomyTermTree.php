<?php

namespace Drupal\taxonomy_block;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\Plugin\views\argument\Taxonomy;

/**
 * Loads taxonomy terms in a tree
 */
class TaxonomyTermTree {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;


  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * TaxonomyTermTree constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   */
  public function __construct(EntityTypeManager $entityTypeManager, AccountInterface $account = NULL) {
    $this->entityTypeManager = $entityTypeManager;
    $this->account = $account;
  }

  /**
   * Loads the tree of a vocabulary.
   *
   * @param string $vocabulary
   *   Machine name
   *
   * @return array
   */
  public function load($vocabulary) {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary, 0, NULL, TRUE);
    $tree = [];
    foreach ($terms as $tree_object) {
      /** @var $tree_object Taxonomy */
      if ($tree_object->access($this->account)) {
        $this->buildTree($tree, $tree_object, $vocabulary);
      }
    }

    return $tree;
  }

  /**
   * Populates a tree array given a taxonomy term tree object.
   *
   * @param $tree
   * @param $object
   * @param $vocabulary
   */
  protected function buildTree(&$tree, $object, $vocabulary) {

    if ($object->depth != 0) {
      return;
    }
    $tree[$object->id()] = $object;
    $tree[$object->id()]->children = [];
    $object_children = &$tree[$object->id()]->children;

    $children = $this->entityTypeManager->getStorage('taxonomy_term')->loadChildren($object->id());
    if (!$children) {
      return;
    }

    $child_tree_objects = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary, $object->id(), NULL, TRUE);

    foreach ($children as $child) {
      foreach ($child_tree_objects as $child_tree_object) {
        if ($child_tree_object->id() == $child->id() && $child_tree_object->access($this->account)) {
         $this->buildTree($object_children, $child_tree_object, $vocabulary);
        }
      }
    }

    uasort($object_children, function ($a, $b){
      if($a->get("weight")->getValue()[0] == $b->get("weight")->getValue()[0])
        return 0;
      return ($a->get("weight")->getValue()[0] > $b->get("weight")->getValue()[0]) ? 1 : -1;
    });

  }
}
