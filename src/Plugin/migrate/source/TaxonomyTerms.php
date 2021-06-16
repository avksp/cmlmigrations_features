<?php

namespace Drupal\cmlmigrations_features\Plugin\migrate\source;

use Drupal\cmlmigrations\Utility\MigrationsSourceBase;

/**
 * Source for CSV.
 *
 * @MigrateSource(
 *   id = "cmlmigrations_features_tx_terms"
 * )
 */
class TaxonomyTerms extends MigrationsSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getRows($rows = []) {
    $k = 0;
    $source = \Drupal::service('cmlmigrations.scheme')->terms();
    if ($source) {
      foreach ($source as $key => $row) {
        if ($k++ < 700 || !$this->uipage) {
          $rows[$key] = [
            'uuid' => $key,
            'vid' => $row['vid'],
            'name' => $row['name'],
            'status' => 1,
            'weight' => 0,
          ];
          if (isset($row['term_weight']) && $row['term_weight']) {
            $rows[$key]['term_weight'] = $row['term_weight'];
          }
          if (isset($row['parent']) && $row['parent']) {
            $rows[$key]['parent'] = $row['parent'];
          }
        }
      }
    }
    $this->debug = TRUE;
    return $rows;
  }

}
