<?php

namespace Drupal\flag_search_api\Utility;

use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use \Drupal\Core\Entity\EntityInterface;

class FlagSearchApiHelper {
  /**
   * Reindex Item
   *
   * @param EntityInterface $entity
   */
  public static function reindexItem(EntityInterface $entity){
    $reindex_on_flagging = \Drupal::config('flag_search_api.settings')->get('reindex_on_flagging');
    if($reindex_on_flagging && $entity->getEntityTypeId() == 'flagging'){
      $datasource_id = 'entity:' . $entity->getFlaggableType();
      $content_flagged = $entity->getFlaggable();
      $indexes = ContentEntity::getIndexesForEntity($content_flagged);

      $entity_id = $entity->getFlaggableId();

      $updated_item_ids = $content_flagged->getTranslationLanguages();
      foreach ($updated_item_ids as $langcode => $language) {
        $inserted_item_ids[] = $langcode;
      }
      $combine_id = function ($langcode) use ($entity_id) {
        return $entity_id . ':' . $langcode;
      };
      $updated_item_ids = array_map($combine_id, array_keys($updated_item_ids));
      foreach ($indexes as $index) {
        if ($updated_item_ids) {
          $index->trackItemsUpdated($datasource_id, $updated_item_ids);
        }
      }
    }
  }
}