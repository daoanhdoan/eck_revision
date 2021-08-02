<?php

namespace Drupal\eck_revision\Form\Entity;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eck\Form\Entity\EckEntityForm;
use Drupal\eck_revision\Entity\EckRevisionEntity;

/**
 * Form controller for the ECK entity forms.
 *
 * @ingroup eck
 */
class EckRevisionEntityForm extends EckEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var EckRevisionEntity $entity */
    parent::save($form, $form_state);
    $form_state->setRedirect('entity.' . $this->entity->getEntityTypeId() . '.canonical', [$this->entity->getEntityTypeId() => $this->entity->id()]);
  }

}
