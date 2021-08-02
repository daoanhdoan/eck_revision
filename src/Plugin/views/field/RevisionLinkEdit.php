<?php

namespace Drupal\eck_revision\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\eck_revision\EckRevisionEntityInterface;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to revert a revision to a revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("eck_revision_link_edit")
 */
class RevisionLinkEdit extends RevisionLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var EckRevisionEntityInterface $revision */
    $revision = $this->getEntity($row);
    return Url::fromRoute("entity.{$revision->getEntityTypeId()}.revisions_edit", [$revision->getEntityTypeId() => $revision->id(), 'eck_entity_revision' => $revision->getRevisionId()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Edit');
  }

}
