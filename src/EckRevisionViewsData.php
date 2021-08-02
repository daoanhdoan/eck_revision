<?php

namespace Drupal\eck_revision;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\eck\EckEntityTypeInterface;
use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the pricing entity type.
 */
class EckRevisionViewsData extends EntityViewsData
{
  /**
   * {@inheritdoc}
   */
  public function getViewsData()
  {
    $data = parent::getViewsData();
    $eck_entity_type = \Drupal::entityTypeManager()->getDefinition("eck_entity_type");
    /** @var EckEntityTypeInterface[] $eck_types */
    $eck_types = \Drupal::entityTypeManager()->createHandlerInstance(
      $eck_entity_type->getHandlerClass('storage'),
      $eck_entity_type
    )->loadMultiple();
    foreach ($eck_types as $eck_type) {
      $config = \Drupal::config('eck.eck_entity_type.' . $eck_type->id());
      if (empty($config->get('vid'))) {
        continue;
      }
      /** @var EntityTypeInterface $eck_type */
      $eck_type = \Drupal::entityTypeManager()->getDefinition($eck_type->id());
      $label = $eck_type->getLabel()->__toString();
      $data_table = $eck_type->getDataTable();
      $revision_table = $eck_type->getRevisionTable();
      $revision_data_table = $eck_type->getRevisionDataTable();
      $id_field = $eck_type->getKey('id');
      $revision_field = $eck_type->getKey('revision');

      // Advertise this table as a possible base table.
      $data[$revision_data_table]['table']['base']['help'] = $this->t('Entity revision is a history of changes to entity.');
      $data[$revision_data_table]['table']['base']['defaults']['title'] = 'title';

      // @todo the NID field needs different behavior on revision/non-revision
      //   tables. It would be neat if this could be encoded in the base field
      //   definition.
      $data[$revision_data_table][$id_field]['relationship']['id'] = 'standard';
      $data[$revision_data_table][$id_field]['relationship']['base'] = $data_table;
      $data[$revision_data_table][$id_field]['relationship']['base field'] = $id_field;
      $data[$revision_data_table][$id_field]['relationship']['title'] = $this->t($label);
      $data[$revision_data_table][$id_field]['relationship']['label'] = $this->t('Get the actual entity from a entity revision.');
      $data[$revision_data_table][$id_field]['relationship']['help'] = $data[$revision_data_table][$id_field]['relationship']['label'];
      $data[$revision_data_table][$id_field]['relationship']['extra'][] = [
        'field' => 'langcode',
        'left_field' => 'langcode',
      ];

      $data[$revision_data_table][$revision_field] = [
        'argument' => [
          'id' => 'numeric',
          'numeric' => TRUE,
        ],
        'relationship' => [
          'id' => 'standard',
          'base' => $data_table,
          'base field' => $revision_field,
          'title' => $this->t($label),
          'label' => $this->t('Get the actual entity from a entity revision.'),
          'help' => $this->t('Get the actual %label from a %label revision.', ['%label' => $this->t($label)]),
          'extra' => [
            [
              'field' => 'langcode',
              'left_field' => 'langcode',
            ],
          ],
        ],
      ];

      $data[$revision_table]['table']['join'][$data_table]['left_field'] = $revision_field;
      $data[$revision_table]['table']['join'][$data_table]['field'] = $revision_field;


      $data[$data_table]['status']['filter']['label'] = $this->t('Published status');
      $data[$data_table]['status']['filter']['type'] = 'yes-no';
      // Use status = 1 instead of status <> 0 in WHERE statement.
      $data[$data_table]['status']['filter']['use_equal'] = TRUE;

      $data[$data_table]['status_extra'] = [
        'title' => $this->t('Published status or admin user'),
        'help' => $this->t('Filters out unpublished content if the current user cannot view it.'),
        'filter' => [
          'field' => 'status',
          'id' => $data_table . '_status',
          'label' => $this->t('Published status or admin user'),
        ],
      ];
      $data[$data_table]['uid']['help'] = $this->t('The user authoring the entity. If you need more fields than the uid add the entity: author relationship');
      $data[$data_table]['uid']['filter']['id'] = 'user_name';
      $data[$data_table]['uid']['relationship']['title'] = $this->t('Entity author');
      $data[$data_table]['uid']['relationship']['help'] = $this->t('Relate entity to the user who created it.');
      $data[$data_table]['uid']['relationship']['label'] = $this->t('author');

      $data[$revision_data_table]['uid']['help'] = $this->t('The user who created the revision.');
      $data[$revision_data_table]['uid']['relationship']['label'] = $this->t('Revision user');
      $data[$revision_data_table]['uid']['filter']['id'] = 'user_name';

      $data[$revision_data_table]['status']['filter']['label'] = $this->t('Published');
      $data[$revision_data_table]['status']['filter']['type'] = 'yes-no';
      $data[$revision_data_table]['status']['filter']['use_equal'] = TRUE;

      $data[$revision_table]['link_to_revision'] = [
        'field' => [
          'title' => $this->t('Link to revision'),
          'help' => $this->t('Provide a simple link to the revision.'),
          'id' => 'eck_revision_link',
          'click sortable' => FALSE,
        ],
      ];

      $data[$revision_table]['revert_revision'] = [
        'field' => [
          'title' => $this->t('Link to revert revision'),
          'help' => $this->t('Provide a simple link to revert to the revision.'),
          'id' => 'eck_revision_link_revert',
          'click sortable' => FALSE,
        ],
      ];

      $data[$revision_table]['edit_revision'] = [
        'field' => [
          'title' => $this->t('Link to edit revision'),
          'help' => $this->t('Provide a simple link to edit to the revision.'),
          'id' => 'eck_revision_link_edit',
          'click sortable' => FALSE,
        ],
      ];

      $data[$revision_table]['delete_revision'] = [
        'field' => [
          'title' => $this->t('Link to delete revision'),
          'help' => $this->t('Provide a simple link to delete the content revision.'),
          'id' => 'eck_revision_link_delete',
          'click sortable' => FALSE,
        ],
      ];

    }

    return $data;
  }

}
