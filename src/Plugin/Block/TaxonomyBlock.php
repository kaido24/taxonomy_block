<?php

namespace Drupal\taxonomy_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Provides a 'TaxonomyBlock' block.
 *
 * @Block(
 *  id = "taxonomy_block",
 *  admin_label = @Translation("Taxonomy block"),
 * )
 */
class TaxonomyBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $vocabularies = Vocabulary::loadMultiple();
    $options = array_map(function($voc){
      return $voc->label();
    }, $vocabularies);
    $form['vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Vocabulary'),
      '#description' => $this->t('Taxonomy selection'),
      '#options' => $options,
      '#default_value' => $this->configuration['vocabulary'],
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['vocabulary'] = $form_state->getValue('vocabulary');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $vid = $this->configuration['vocabulary'];
    $tt = \Drupal::service('taxonomy_block.taxonomy_term_tree');
    $tree = $tt->load($vid);

    $route_match = \Drupal::service('current_route_match');
    $active_term = $route_match->getParameter('taxonomy_term');
    $active_build = 0;
    $parents = array();

    if($active_term != NULL){
      $active_build = $active_term->id();
      $parent = $this->getParents($active_term->id());
      while($parent[0] != 0) {
        $parents[$parent[0]] = $parent[0];
        $parent = $this->getParents($parent[0]);
      }
      //ksm($parents);
    }

    $build = [];
    $build['tb'] = [
      '#theme' => 'taxonomy_block',
      '#terms' => $tree,
      '#active' => $active_build,
      '#parents' => $parents
    ];
    return $build;
  }

  /**
   *
   * @param $element_id
   * @return $parent_id
   */
  public function getParents($element_id) {
    $query = \Drupal::database()->select('taxonomy_term__parent', 't');
    $query->addField('t','parent_target_id');
    $query->condition('entity_id', $element_id, "=");
    $parent_id = $query->execute()->fetchCol();
    return $parent_id;
  }


  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['taxonomy_term_list']);
  }
}
