<?php

namespace Drupal\cmlmigrations_features\Plugin\migrate\source;

use Drupal\cmlmigrations\Utility\MigrationsSourceBase;

/**
 * Source for CSV.
 *
 * @MigrateSource(
 *   id = "cmlmigrations_features_commerce_product_variation"
 * )
 */
class CommerceProductVariation extends MigrationsSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getRows() {
    $rows = [];
    $type = \Drupal::config('cmlmigrations.settings')->get('variation');
    $source = \Drupal::service('cmlapi.parser_offers')->parse();
    if ($source) {
      $k = 0;
      foreach ($source as $key => $row) {
        if ($k++ < 150 || !$this->uipage) {
          $id = $row['Id'];
          $rows[$id] = [
            'type' => $type,
            'uuid' => $id,
            'sku' => $id,
            'product_uuid' => strstr("{$id}#", "#", TRUE),
            'title' => $this->getVal($row, 'Naimenovanie'),
            'unit' => $this->getVal($row, 'BazovayaEdinica'),
            'Kolicestvo' => (int) $this->getVal($row, 'Kolichestvo'),
            'Sklad' => $this->getVal($row, 'Sklad'),
            'price' => [
              'number' => (int) $row['Ceny'][0]['ЦенаЗаЕдиницу'],
              'currency_code' => 'RUB',
            ],
          ];
        }
      }
    }
    $this->debug = TRUE;
    return $rows;
  }

}
