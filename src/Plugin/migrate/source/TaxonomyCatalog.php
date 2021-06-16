<?php

namespace Drupal\cmlmigrations_features\Plugin\migrate\source;

use Drupal\cmlmigrations\Utility\MigrationsSourceBase;

/**
 * Source for CSV.
 *
 * @MigrateSource(
 *   id = "cmlmigrations_features_tx_catalog"
 * )
 */
class TaxonomyCatalog extends MigrationsSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getRows() {
    $rows = [];
    $source = \Drupal::service('cmlapi.parser_catalog')->parseFlatCatalog();
    if ($source) {
      $k = 0;
      $vocabulary = \Drupal::config('cmlmigrations.settings')->get('vocabulary');
      foreach ($source as $key => $row) {
        if ($k++ < 700 || !$this->uipage) {
          $rows[$key] = [
            'vid' => $vocabulary,
            'uuid' => $row['id'],
            'status' => 1,
            'name' => $row['name'],
            'weight' => $row['term_weight'],
          ];
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
