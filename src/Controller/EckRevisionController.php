<?php

namespace Drupal\eck_revision\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\eck_revision\EckRevisionEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Node routes.
 */
class EckRevisionController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, EntityRepositoryInterface $entity_repository) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('entity.repository')
    );
  }

  /**
   * Displays a pricing revision.
   *
   * @param EckRevisionEntityInterface $eck_entity_revision
   *   The pricing revision ID.
   *
   * @return array
   *   An array suitable for \Drupal\Core\Render\RendererInterface::render().
   */
  public function revisionShow($eck_entity_revision, Request $request) {
    /** @var EckRevisionEntityInterface $eck_entity */
    $eck_entity = $eck_entity_revision;
    $eck_entity = $this->entityTypeManager()->getStorage($eck_entity->getEntityTypeId())->loadRevision($eck_entity->getLoadedRevisionId());
    $eck_entity = $this->entityRepository->getTranslationFromContext($eck_entity);
    $eck_entity_view_controller = new EntityViewController($this->entityTypeManager(), $this->renderer);
    $page = $eck_entity_view_controller->view($eck_entity);
    unset($page[$eck_entity->getEntityTypeId()][$eck_entity->id()]['#cache']);
    return $page;
  }

  /**
   * Page title callback for a pricing revision.
   *
   * @param EckRevisionEntityInterface $eck_entity_revision
   *   The pricing revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($eck_entity_revision) {
    $eck_entity = $eck_entity_revision;
    return $this->t('Revision of %title from %date', ['%title' => $eck_entity->label(), '%date' => $this->dateFormatter->format($eck_entity->getRevisionCreationTime())]);
  }

  /**
   *
   */
  public function getRouteEntity() {
    $route_match = \Drupal::routeMatch();
    // Entity will be found in the route parameters.
    if (($route = $route_match->getRouteObject()) && ($parameters = $route->getOption('parameters'))) {
      // Determine if the current route represents an entity.
      foreach ($parameters as $name => $options) {
        if (isset($options['type']) && strpos($options['type'], 'entity:') === 0) {
          $entity = $route_match->getParameter($name);
          if ($entity instanceof \Drupal\Core\Entity\ContentEntityInterface && $entity->hasLinkTemplate('canonical')) {
            return $entity;
          }

          // Since entity was found, no need to iterate further.
          return NULL;
        }
      }
    }
  }

  /**
   * Generates an overview table of older revisions of a pricing.
   *
   *   A pricing object.
   *
   * @return array
   *   An array as expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function revisionOverview() {
    /** @var EckRevisionEntityInterface $eck_entity */
    $eck_entity = $this->getRouteEntity();
    if (!$eck_entity) {
      return ["#markup" => t('Entity not found.')];
    }
    //$eck_entity = $this->entityTypeManager()->getStorage($eck_entity_type)->load($eck_entity_id);
    $account = $this->currentUser();
    $langcode = $eck_entity->language()->getId();
    $langname = $eck_entity->language()->getName();
    $languages = $eck_entity->getTranslationLanguages();
    $entity_type = $eck_entity->getEntityTypeId();
    $has_translations = (count($languages) > 1);
    $eck_entity_storage = $this->entityTypeManager()->getStorage($entity_type);

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $eck_entity->label()]) : $this->t('Revisions for %title', ['%title' => $eck_entity->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $delete_permission = $account->hasPermission('delete all revisions') || $eck_entity->access('delete');
    $edit_permission = ($account->hasPermission('edit all revisions') || $eck_entity->access('update'));
    $revert_permission = ($account->hasPermission('revert all revisions') || $eck_entity->access('revert'));

    $rows = [];
    $default_revision = $eck_entity->getRevisionId();
    $current_revision_displayed = FALSE;

    foreach ($this->getRevisionIds($eck_entity, $eck_entity_storage) as $vid) {
      /** @var \Drupal\pricing\PricingInterface $revision */
      $revision = $eck_entity_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');

        // We treat also the latest translation-affecting revision as current
        // revision, if it was the default revision, as its values for the
        // current language will be the same of the current default revision in
        // this case.
        $is_current_revision = $vid == $default_revision || (!$current_revision_displayed && $revision->wasDefaultRevision());
        if (!$is_current_revision) {
          $link = Link::fromTextAndUrl($date, new Url("entity.{$entity_type}.revision", ["{$entity_type}" => $eck_entity->id(), 'eck_entity_revision' => $vid]))->toString();
        }
        else {
          $link = $eck_entity->toLink($date)->toString();
          $current_revision_displayed = TRUE;
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => ['#markup' => $revision->revision_log->value, '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        // @todo Simplify once https://www.drupal.org/pricing/2334319 lands.
        $this->renderer->addCacheableDependency($column['data'], $username);
        $row[] = $column;

        if ($is_current_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];

          $rows[] = [
            'data' => $row,
            'class' => ['revision-current'],
          ];
        }
        else {
          $links = [];

          $links['edit'] = [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute("entity.{$entity_type}.revision_edit", ["{$entity_type}" => $eck_entity->id(), 'eck_entity_revision' => $vid]),
          ];
          $links['revert'] = [
            'title' => $this->t('Revert'),
            'url' => Url::fromRoute("entity.{$entity_type}.revision_revert_confirm", ["{$entity_type}" => $eck_entity->id(), 'eck_entity_revision' => $vid]),
          ];

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute("entity.{$entity_type}.revision_delete_confirm", ["{$entity_type}" => $eck_entity->id(), 'eck_entity_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];

          $rows[] = $row;
        }
      }
    }

    $build['eck_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => [
        'library' => ['node/drupal.node.admin'],
      ],
      '#attributes' => ['class' => 'eck-revision-table'],
    ];

    $build['pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Displays a node revision.
   *
   * @param EckRevisionEntityInterface $eck_entity_revision
   *   The node revision ID.
   *
   * @return array
   *   An array suitable for \Drupal\Core\Render\RendererInterface::render().
   */
  public function editRevision($eck_entity_revision) {
    /** @var EckRevisionEntityInterface $eck_entity */
    $eck_entity = $this->getRouteEntity();
    $eck_entity = $this->entityTypeManager()->getStorage($eck_entity->getEntityTypeId())->loadRevision($eck_entity_revision->getLoadedRevisionId());
    $eck_entity = $this->entityRepository->getTranslationFromContext($eck_entity);
    $eck_entity->setNewRevision(FALSE);
    $eck_entity->setRevisionLogMessage('Programmatically change at...' . \Drupal::getContainer()->get('date.formatter')->format(time(), 'short'));
    $eck_entity->setChangedTime(time());
    $form = \Drupal::getContainer()->get('entity.form_builder')->getForm($eck_entity);
    $form['revision'] = ['#type' => 'hidden', '#value' => FALSE];
    return $form;
  }

  /**
   * Gets a list of pricing revision IDs for a specific pricing.
   *
   * @param \Drupal\pricing\PricingInterface $eck_entity
   *   The pricing entity.
   * @param \Drupal\Core\Entity\EntityStorageInterface $eck_entity_storage
   *   The pricing storage handler.
   *
   * @return int[]
   *   Node revision IDs (in descending order).
   */
  protected function getRevisionIds(EckRevisionEntityInterface $eck_entity, EntityStorageInterface $eck_entity_storage) {
    $result = $eck_entity_storage->getQuery()
      ->allRevisions()
      ->condition($eck_entity->getEntityType()->getKey('id'), $eck_entity->id())
      ->sort($eck_entity->getEntityType()->getKey('revision'), 'DESC')
      ->pager(50)
      ->execute();
    return array_keys($result);
  }

}
