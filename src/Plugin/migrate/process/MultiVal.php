<?php

namespace Drupal\cmlmigrations_features\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin iterates and processes an array.
 *
 * @link https://www.drupal.org/node/2135345 Online handbook documentation for iterator process plugin @endlink
 *
 * @MigrateProcessPlugin(
 *   id = "multi_val",
 *   handle_multiples = TRUE
 * )
 */
class MultiVal extends ProcessPluginBase {

  /**
   * Runs a process pipeline on each destination property per list item.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $return = [];
    if (isset($value[0])) {
      $return = $value[0];
      // Old. $src = $this->configuration['target']; //.
      foreach ($value[0] as $key => $value) {
        $return[$key] = $value;
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
