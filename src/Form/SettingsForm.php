<?php

namespace Drupal\commerce_canadapost\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Canada Post settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_canadapost_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_canadapost.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Build the form fields.
    $utilities_service = \Drupal::service('commerce_canadapost.utilities_service');
    $form += $utilities_service->buildApiForm();

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this
      ->config('commerce_canadapost.settings')
      ->set('api.customer_number', $form_state->getValue('customer_number'))
      ->set('api.username', $form_state->getValue('username'))
      ->set('api.password', $form_state->getValue('password'))
      ->set('api.contract_id', $form_state->getValue('contract_id'))
      ->set('api.mode', $form_state->getValue('mode'))
      ->set('api.log', $form_state->getValue('log'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
