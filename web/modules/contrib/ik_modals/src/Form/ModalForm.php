<?php

namespace Drupal\ik_modals\Form;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Form controller for Modal edit forms.
 *
 * @ingroup ik_modals
 */
class ModalForm extends ContentEntityForm {
  /**
   * The account interface.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $accountInterface;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeInterface;

  /**
   * The messanger interface.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messengerInterface;

  /**
   * Class constructor.
   */
  public function __construct(EntityRepositoryInterface $entityRepository, EntityTypeBundleInfoInterface $entityTypeBundleInfo, TimeInterface $timeInterface, AccountInterface $accountInterface, MessengerInterface $messengerInterface) {
    parent::__construct($entityRepository, $entityTypeBundleInfo, $timeInterface);

    $this->accountInterface = $accountInterface;
    $this->messengerInterface = $messengerInterface;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\ik_modals\Entity\Modal */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
      $entity->setRevisionUserId($this->accountInterface->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messengerInterface->addMessage($this->t('Created the %label Modal.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messengerInterface->addMessage($this->t('Saved the %label Modal.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.modal.canonical', ['modal' => $entity->id()]);
  }

}