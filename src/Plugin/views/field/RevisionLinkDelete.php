<?php

namespace Drupal\eck_revision\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to present link to delete a pricing revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("eck_revision_link_delete")
 */
class RevisionLinkDelete extends RevisionLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\eck_revision\EckRevisionEntityInterface $revision */
    $revision = $this->getEntity($row);
    return Url::fromRoute("entity.{$revision->getEntityTypeId()}.revision_delete_confirm", [$revision->getEntityTypeId() => $revision->id(), 'eck_entity_revision' => $revision->getRevisionId()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Delete');
  }

}
