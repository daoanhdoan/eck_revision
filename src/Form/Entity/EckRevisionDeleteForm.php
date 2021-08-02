<?php

namespace Drupal\eck_revision\Form\Entity;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a pricing revision.
 *
 * @internal
 */
class EckRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The pricing revision.
   *
   * @var \Drupal\eck_revision\EckRevisionEntityInterface
   */
  protected $revision;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new PricingRevisionDeleteForm.
   *   The pricing type storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(Connection $connection, DateFormatterInterface $date_formatter) {
    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eck_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity_type = $this->revision->getEntityTypeId();
    return new Url("entity.{$entity_type}.version_history", [$entity_type => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $eck_entity_revision = NULL) {
    $this->revision = $eck_entity_revision;
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type = $this->revision->getEntityType();
    \Drupal::entityTypeManager()->getStorage($entity_type->id())->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('@type: deleted %title revision %revision.', ['@type' => $this->revision->bundle(), '%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()
      ->addStatus($this->t('Revision from %revision-date of @type %title has been deleted.', [
        '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
        '@type' => $this->revision->getEntityType()->getLabel(),
        '%title' => $this->revision->label(),
      ]));
    $form_state->setRedirect(
      "entity.{$entity_type->id()}.canonical",
      [$entity_type->id() => $this->revision->id()]
    );

    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {' . $entity_type->getRevisionTable() . '} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        "entity.{$entity_type->id()}.version_history",
        [$entity_type->id() => $this->revision->id()]
      );
    }
  }

}
