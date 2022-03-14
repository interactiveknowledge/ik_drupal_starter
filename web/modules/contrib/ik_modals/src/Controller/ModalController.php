<?php

namespace Drupal\ik_modals\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\ik_modals\Entity\ModalInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ModalController.
 *
 *  Returns responses for Modal routes.
 */
class ModalController extends ControllerBase implements ContainerInjectionInterface {

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
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, EntityRepositoryInterface $entity_repository = NULL) {
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
   * Displays a Modal  revision.
   *
   * @param int $modal_revision
   *   The Modal  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($modal_revision) {
    $modal = $this->static::entityManager()->getStorage('modal')->loadRevision($modal_revision);
    $view_builder = $this->static::entityManager()->getViewBuilder('modal');

    return $view_builder->view($modal);
  }

  /**
   * Page title callback for a Modal  revision.
   *
   * @param int $modal_revision
   *   The Modal  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($modal_revision) {
    $modal = $this->static::entityManager()->getStorage('modal')->loadRevision($modal_revision);
    return $this->t('Revision of %title from %date', ['%title' => $modal->label(), '%date' => $this->dateFormatter->format($modal->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Modal .
   *
   * @param \Drupal\ik_modals\Entity\ModalInterface $modal
   *   A Modal  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(ModalInterface $modal) {
    $account = $this->currentUser();
    $langcode = $modal->language()->getId();
    $langname = $modal->language()->getName();
    $languages = $modal->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $modal_storage = \Drupal::entityTypeManager()->getStorage('modal');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $modal->label()]) : $this->t('Revisions for %title', ['%title' => $modal->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all modal revisions") || $account->hasPermission('administer modal entities')));
    $delete_permission = (($account->hasPermission("delete all modal revisions") || $account->hasPermission('administer modal entities')));

    $rows = [];

    $vids = $modal_storage->revisionIds($modal);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\ik_modals\ModalInterface $revision */
      $revision = $modal_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $modal->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.modal.revision', ['modal' => $modal->id(), 'modal_revision' => $vid]));
        }
        else {
          $link = $modal->toLink($date)->toString();
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute(
                'entity.modal.translation_revert',
                [
                  'modal' => $modal->id(),
                  'modal_revision' => $vid,
                  'langcode' => $langcode,
                ]
              ) :
              Url::fromRoute(
                'entity.modal.revision_revert',
                [
                  'modal' => $modal->id(),
                  'modal_revision' => $vid,
                ]
              ),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.modal.revision_delete', ['modal' => $modal->id(), 'modal_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['modal_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
