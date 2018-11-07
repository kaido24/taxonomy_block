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

    $build = [];
    $build['tb'] = [
      '#theme' => 'taxonomy_block',
      '#terms' => $tree,
    ];
    return $build;
  }
  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['taxonomy_term_list']);
  }
}
