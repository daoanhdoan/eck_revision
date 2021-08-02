<?php

namespace Drupal\eck_revision\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\eck\Entity\EckEntity;
use Drupal\eck_revision\EckRevisionEntityInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Class EckRevisionEntity
 *
 * @package Drupal\eck_revision\Entity
 */
class EckRevisionEntity extends EckEntity implements EckRevisionEntityInterface {
  use EntityChangedTrait;
  use EntityOwnerTrait;
  use EntityPublishedTrait;
  use RevisionLogEntityTrait;

  /**
   * An array of entity revision metadata keys.
   *
   * @var array
   */
  protected $revision_metadata_keys = [];

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entityType)
  {
    $fields = parent::baseFieldDefinitions($entityType);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the entity.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
          'label' => 'hidden',
          'type' => 'string',
          'weight' => -5,
        ]
      )
      ->setDisplayOptions('form', [
          'type' => 'string_textfield',
          'weight' => -5,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Author field for the entity.
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The username of the entity author.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Created field for the entity.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the entity was created.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Changed field for the entity.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setRevisionable(TRUE)
      ->setDescription(t('The time that the entity was last edited.'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields += static::publishedBaseFieldDefinitions($entityType);
    $fields += static::revisionLogBaseFieldDefinitions($entityType);

    if (isset($fields[$entityType->getKey('owner')])) {
      $fields[$entityType->getKey('owner')]->setDefaultValueCallback(static::class . '::getDefaultEntityOwner');
    }
    return $fields;
  }
}
