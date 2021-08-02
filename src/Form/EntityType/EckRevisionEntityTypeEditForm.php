<?php

namespace Drupal\eck_revision\Form\EntityType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eck\Form\EntityType\EckEntityTypeEditForm;

/**
 * Form controller for the ECK Extend entity forms.
 *
 * @ingroup eck_extend
 */
class EckRevisionEntityTypeEditForm extends EckEntityTypeEditForm {
  /**
   * @inheritdoc
   */
  function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $eck_entity_type = $this->entity;
    $config = \Drupal::config('eck.eck_entity_type.' . $eck_entity_type->id());
    $form['base_fields']['vid'] = [
      '#type' => 'checkbox',
      '#title' => t('Revision ID'),
      '#default_value' => $config->get('vid'),
      '#disabled' => TRUE
    ];
    if (!empty($config->get('vid'))) {
      foreach (['created', 'changed', 'uid', 'title'] as $field) {
        $form['base_fields'][$field]['#default_value'] = TRUE;
        $form['base_fields'][$field]['#disabled'] = TRUE;
      }
    }
    return $form;
  }

  /**
   * @inheritdoc
   */
  function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $config = \Drupal::configFactory()->getEditable('eck.eck_entity_type.' . $this->entity->id());
    $config->set('vid', $form_state->getValue('vid') ? TRUE : FALSE);
    $config->save();
    $entity_type_manager = \Drupal::entityTypeManager();
    if ($entity_type_manager->hasHandler($this->entity->id(), 'view_builder')) {
      $entity_type_manager->getViewBuilder($this->entity->id())->resetCache();
    }
  }
}
