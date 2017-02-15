<?php
/**
 * Created by PhpStorm.
 * User: Dalibor Stojaković
 * Date: 06.02.17.
 * Time: 11:06
 */
namespace Drupal\flag_search_api\Plugin\search_api\processor;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * @SearchApiProcessor(
 *   id = "flag_indexer",
 *   label = @Translation("Flag indexing"),
 *   description = @Translation("Switching on will enable indexing flags on content"),
 *   stages = {
 *     "add_properties" = 1,
 *     "pre_index_save" = -10,
 *     "preprocess_index" = -30
 *   }
 * )
 */
class FlagIndexer extends ProcessorPluginBase implements PluginFormInterface {
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'flag_index' => array(),
    );
  }
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $default_flags = FALSE;
    $options = [];

    if (isset($this->configuration['flag_index'])) {
      $default_flags = $this->configuration['flag_index'];
    }
    $flag_service = \Drupal::service('flag');
    //this is for deprecated function support
    $flags = (method_exists($flag_service,'getAllFlags')) ? $flag_service->getAllFlags() : $flag_service->getFlags();
    foreach($flags as $flag){
      $options[$flag->get('id')] = $flag->get('label');
    }

    $form['flag_index'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable these flags on this index'),
      '#description' => $this->t('This will index IDs from users that flagged this content'),
      '#options' => $options,
      '#default_value' => $default_flags,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $fields = array_filter($form_state->getValues()['flag_index']);
    if ($fields) {
      $fields = array_keys($fields);
    }
    $form_state->setValue('flag_index', $fields);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Sanitize the storage for the "exclude_fields" setting.
    $this->setConfiguration($form_state->getValues());
  }
  /**
   * {@inheritdoc}
   *
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = array();

    if (!$datasource) {
      // Ensure that our fields are defined.
      $fields = $this->getFieldsDefinition();

      foreach ($fields as $field_id => $field_definition) {
        $properties[$field_id] = new DataDefinition($field_definition);
      }
    }
    return $properties;
  }

  /**
   * Helper function for defining our custom fields.
   */
  protected function getFieldsDefinition() {
    $config = isset($this->configuration['settings']['flag_index']) && !empty($this->configuration['settings']['flag_index']) && empty($this->configuration['flag_index'])? $this->configuration['settings']['flag_index']: $this->configuration['flag_index'];
    $fields = [];
    $flag_service = \Drupal::service('flag');
    //this is for deprecated function support
    $flags = (method_exists($flag_service,'getAllFlags')) ? $flag_service->getAllFlags() : $flag_service->getFlags();
    foreach($config as $flag){
      $label = $flags[$flag]->get('label');
      $fields['flag_'. $flag] = array(
        'label' => $label,
        'description' => $label,
        'type' => 'integer',
        'prefix' => 't',
      );
    }
    return $fields;
  }


  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    $config = $this->configuration['flag_index'];
    $flag_service = \Drupal::service('flag');
    //this is for deprecated function support
    $flags = (method_exists($flag_service,'getAllFlags')) ? $flag_service->getAllFlags() : $flag_service->getFlags();
    foreach ($items as $item) {
      $entity = $item->getOriginalObject()->getValue();
      foreach($config as $flag_id){
        $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL,'flag_' . $flag_id);
        foreach ($fields as $flag_field) {
          $users = $flag_service->getFlaggingUsers($entity,$flags[$flag_id]);
          foreach($users as $user){
            $flag_field->addValue($user->id());
          }
        }
      }
    }
  }
  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    foreach ($this->getFieldsDefinition() as $field_id => $field_definition) {
      $this->ensureField(NULL, $field_id, $field_definition['type']);
    }
  }

}