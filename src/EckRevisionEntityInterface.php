<?php

namespace Drupal\eck_revision;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Pricing entity.
 * @ingroup pricing
 */
interface EckRevisionEntityInterface extends RevisionLogInterface, ContentEntityInterface, EntityPublishedInterface, EntityOwnerInterface, EntityChangedInterface {

}
