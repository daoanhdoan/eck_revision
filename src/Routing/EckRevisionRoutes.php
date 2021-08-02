<?php

namespace Drupal\eck_revision\Routing;

use Drupal\eck\Entity\EckEntityType;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes.
 *
 * @ingroup eck_revision
 */
class EckRevisionRoutes
{

  /**
   * {@inheritdoc}
   */
  public function routes()
  {
    $routeCollection = new RouteCollection();

    /** @var \Drupal\eck\Entity\EckEntityType $entityType */
    foreach (EckEntityType::loadMultiple() as $entityType) {
      $entityTypeId = $entityType->id();
      $version_history_route = new Route("/{$entityTypeId}/{{$entityTypeId}}/revisions");
      $version_history_route->setDefault("_title", 'Revisions')
        ->setDefault('_controller', '\Drupal\eck_revision\Controller\EckRevisionController::revisionOverview')
        ->setRequirement('_entity_access', "{$entityTypeId}.view")
        ->setOption('parameters', ["{$entityTypeId}" => ["type" => "entity:{$entityTypeId}"]]);
      $routeCollection->add("entity.{$entityTypeId}.version_history", $version_history_route);

      $options = ['parameters' => ["{$entityTypeId}" => ["type" => "entity:{$entityTypeId}"], 'eck_entity_revision' => ['type' => "entity_revision:{$entityTypeId}"]]];

      $revision_route = new Route("/{$entityTypeId}/{{$entityTypeId}}/revisions/{eck_entity_revision}");
      $revision_route->setDefault('_controller', '\Drupal\eck_revision\Controller\EckRevisionController::revisionShow')
        ->setDefault("_title_callback", '\Drupal\eck_revision\Controller\EckRevisionController::revisionPageTitle')
        ->setDefault("_title_arguments", ['eck_entity_revision' => ['type' => "entity_revision:{$entityTypeId}"]])
        ->setRequirement('_entity_access', "{$entityTypeId}.view")
        ->setOptions($options);
      $routeCollection->add("entity.{$entityTypeId}.revision", $revision_route);

      $revision_edit_route = new Route("/{$entityTypeId}/{{$entityTypeId}}/revisions/{eck_entity_revision}/edit");
      $revision_edit_route->setDefault('_controller', '\Drupal\eck_revision\Controller\EckRevisionController::editRevision')
        ->setDefault("_title_callback", '\Drupal\eck_revision\Controller\EckRevisionController::revisionPageTitle')
        ->setDefault("_title_arguments", ['eck_entity_revision' => ['type' => "entity_revision:{$entityTypeId}"]])
        ->setRequirement('_entity_access', "{$entityTypeId}.update")
        ->setOptions($options);
      $routeCollection->add("entity.{$entityTypeId}.revision_edit", $revision_edit_route);

      $revision_delete_route = new Route("/{$entityTypeId}/{{$entityTypeId}}/revisions/{eck_entity_revision}/delete");
      $revision_delete_route->setDefault('_form', '\Drupal\eck_revision\Form\Entity\EckRevisionDeleteForm')
        ->setDefault("_title", 'Delete earlier revision')
        ->setRequirement('_entity_access', "{$entityTypeId}.delete")
        ->setOptions($options);
      $routeCollection->add("entity.{$entityTypeId}.revision_delete_confirm", $revision_delete_route);

      $revision_revert_route = new Route("/{$entityTypeId}/{{$entityTypeId}}/revisions/{eck_entity_revision}/revert");
      $revision_revert_route->setDefault('_form', '\Drupal\eck_revision\Form\Entity\EckRevisionRevertForm')
        ->setDefault("_title", 'Revert to earlier revision')
        ->setRequirement('_entity_access', "{$entityTypeId}.update")
        ->setOptions($options);
      $routeCollection->add("entity.{$entityTypeId}.revision_revert_confirm", $revision_revert_route);

      $revision_revert_translate_route = new Route("/{$entityTypeId}/{{$entityTypeId}}/revisions/{eck_entity_revision}/revert/{langcode}");
      $revision_revert_translate_route->setDefault('_form', '\Drupal\eck_revision\Form\Entity\EckRevisionRevertTranslationForm')
        ->setDefault("_title", 'Revert to earlier revision of a translation')
        ->setRequirement('_entity_access', "{$entityTypeId}.update")
        ->setOptions($options);
      $routeCollection->add("entity.{$entityTypeId}.revision_revert_translate_confirm", $revision_revert_translate_route);
    }
    return $routeCollection;
  }
}
