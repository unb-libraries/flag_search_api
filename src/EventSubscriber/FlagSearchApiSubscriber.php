<?php

namespace Drupal\flag_search_api\EventSubscriber;

use Drupal\flag\Entity\Flagging;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Drupal\flag_search_api\FlagSearchApiReindexService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class FlagSearchApiSubscriber.
 */
class FlagSearchApiSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\flag_search_api\FlagSearchApiReindexService
   */
  protected $flagSearchApiReindex;

  /**
   * Constructs a new FlagSearchApiSubscriber object.
   *
   * @param FlagSearchApiReindexService $flag_search_api_reindex_service
   */
  public function __construct(FlagSearchApiReindexService $flag_search_api_reindex_service) {
    $this->flagSearchApiReindex = $flag_search_api_reindex_service;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['flag.entity_flagged'] = ['flagEntityFlagged'];
    $events['flag.entity_unflagged'] = ['flagEntityUnflagged'];

    return $events;
  }

  /**
   * This method is called whenever the flag.entity_flagged event is
   * dispatched.
   *
   * @param FlaggingEvent $event
   */
  public function flagEntityFlagged(FlaggingEvent $event) {
    $this->flagSearchApiReindex->reindexItem($event->getFlagging());
  }
  /**
   * This method is called whenever the flag.entity_unflagged event is
   * dispatched.
   *
   * @param UnflaggingEvent $event
   */
  public function flagEntityUnflagged(UnflaggingEvent $event) {
    $flaggings = $event->getFlaggings();
    /** @var Flagging $flagging */
    foreach($flaggings as $flagging){
      $this->flagSearchApiReindex->reindexItem($flagging);
    }
  }

}
