<?php

namespace Drupal\eck_revision\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to a pricing revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("eck_revision_link")
 */
class RevisionLink extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\eck_revision\EckRevisionEntityInterface $eck_entity */
    $eck_entity = $this->getEntity($row);
    // Current revision uses the pricing view path.
    return !$eck_entity->isDefaultRevision() ?
      Url::fromRoute("entity.{$eck_entity->getEntityTypeId()}.revision", [$eck_entity->getEntityTypeId() => $eck_entity->id(), 'eck_entity_revision' => $eck_entity->getRevisionId()]) :
      $eck_entity->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    /** @var \Drupal\eck_revision\EckRevisionEntityInterface $eck_entity */
    $eck_entity = $this->getEntity($row);
    if (!$eck_entity->getRevisionId()) {
      return '';
    }
    $text = parent::renderLink($row);
    $this->options['alter']['query'] = $this->getDestinationArray();
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('View');
  }

}
