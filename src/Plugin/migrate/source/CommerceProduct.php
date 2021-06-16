<?php

namespace Drupal\cmlmigrations_features\Plugin\migrate\source;

use Drupal\cmlmigrations\Utility\MigrationsSourceBase;
use Drupal\cmlmigrations\Utility\FindVariation;
use Drupal\cmlmigrations\Utility\FindImage;
use Drupal\taxonomy\Entity\Term;
use Drupal\cmlapi\Service\Scheme;

/**
 * Source for CSV.
 *
 * @MigrateSource(
 *   id = "cmlmigrations_features_commerce_product"
 * )
 */
class CommerceProduct extends MigrationsSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getRows() {
    $k = 0;
    $rows = [];
    $source = FALSE;
    $images = FALSE;
    $variations = FALSE;
	$data = null;
	$params = [];
    $type = \Drupal::config('cmlmigrations.settings')->get('product');
    $source = \Drupal::service('cmlapi.parser_product')->parse();
	$cml_service = \Drupal::service('cmlapi.cml');
	$data = \Drupal::service('cmlapi.scheme')->init($cml_service->actual()->id());
	if (is_null($data) == false){
		$params = $data['feature'];
	}
    // var_dump($params);
	
    if ($source && isset($source['data']) && !empty($source['data'])) {
      $store = $this->getStore();
      if (count($source['data']) > 400) {
        // Если вариаций много - грузим сразу все.
        $variations = FindVariation::getBy1cUuid();
        $images = FindImage::getBy1cImage();
      }
      foreach ($source['data'] as $id => $row) {
        if ($k++ < 100 || !$this->uipage) {
          $product = $row['product'];
          $offers = $row['offers'];
          $status = isset($product['status']) && $product['status'] == 'Удален' ? 0 : 1;
          $params_field = $this->hasParams($rows, $params, $product, $id, $product['ZnacheniyaSvoystv']);
		  $params_check = $this->checkParams($params_field);
		  $params_arr_id = $this->get_array_id_param($params_check[0]);
		  $brand = $params_check[1];
		  $functional = $params_check[2];
		  //var_dump($product['ZnacheniyaRekvizitov']);
		  $props = $this->getProps($rows, $product, $id, $product['ZnacheniyaRekvizitov']);
		  //var_dump($props);
		  // var_dump($params_arr_id);
        
          $rows[$id] = [
            'uuid' => $id,
            'type' => $type,
            'stores' => $store,
            'status' => $status,
            'title' => trim($product['Naimenovanie']),
            'catalog' => $product['Gruppy'][0],
            'body' => [
              'value' => $product['Opisanie'],
              'format' => 'full_html',
            ],
			'brand' => $brand,
			'functional' => $functional,
			'params' => $params_arr_id,
            'sku' => $product['Artikul'],
			'options' => $props[0],
			'unit' => $props[1],
          ];
          $this->hasVariations($rows, $variations, $id);
          $this->hasImage($rows, $images, $product, $id);
        }
      }
    }
    $this->debug = TRUE;
    return $rows;
  }

  /**
   * HasVariations.
   */
  private function hasVariations(&$rows, $variations, $id) {
    $result = FALSE;
    if (!$variations) {
      // Ищем вариации текущего товара.
      $variations = FindVariation::getBy1cUuid($id, FALSE);
    }
    if (isset($variations[$id])) {
      $result = $variations[$id];
      $rows[$id]['variations'] = $result;
    }
    return $result;
  }

  /**
   * HasImage.
   */
  private function hasImage(&$rows, $images, $product, $id, $field = 'Kartinka') {
    $result = FALSE;
    if (isset($product[$field])) {
      $image = $product[$field];
      if (is_array($image)) {
        $image = reset($image);
      }

      if (!$images) {
        // Fing image.
        $images = FindImage::getBy1cImage($image, FALSE);
      }
      if (isset($images[$image])) {
        $result = $images[$image];
        $rows[$id]['field_image'] = [
          'target_id' => $result,
        ];
        $rows[$id]['field_gallery'] = $images;
      }
    }
    return $result;
  }
  
  /**
   * GetProps
   */
   // $result[0] - options
   // $result[1] - units
  private function getProps(&$rows, $product, $id, $props) {
    $result = [];
	$options = [];
	$unit = '';
	if (is_array($props) && !empty($props)) {
		foreach ($props as $pid_key => $pid_value){
			if (($pid_key == 'ЭтоНовинка') && ($pid_value == 'true')){
				$new_id = $this->getTidByName('Новинка','product_options');
				if (isset($new_id)){
					array_push($options,$new_id);
				}
			}
			if ($pid_key == 'ЕдиницаИзмерения') {
				$unit = $pid_value;
			}
		}
	}
	
	if (isset($options) && !empty($options)){
		array_push($result,$options);
	}else {
		array_push($result, NULL);
	}
	
	if (!empty($unit)){
		array_push($result,$unit);
	}else {
		array_push($result, '');
	}
	
    return $result;
  }
  

  /**
   * HasParams
   */
  private function hasParams(&$rows, $params, $product, $id, $field) {
    $result = [];
	if (is_array($params) && !empty($params)){
		foreach ($params as $pid_key => $pid_value) {
		  if (is_array($pid_value) && is_array($field) && !empty($field)) {
			foreach ($field as $fid_key => $fid_value){
				if ($fid_key == $pid_key){
					$new_key = '';
					$new_value = '';
					foreach ($pid_value as $ppid_key => $ppid_value){
						if ($ppid_key == 'name'){
							$new_key = $ppid_value;
						}
						if (($ppid_key == 'props') && is_array($ppid_value)){
							foreach ($ppid_value as $pppid_key => $pppid_value){
								if ($pppid_key == $fid_value){
									$new_value = $pppid_value;
								}
							}
						}
					}
					if (($new_key != '') && ($new_value != '')){
						$result += [ $new_key => $new_value ];
						
					}
					
				}
			}
		  }
	    }
	} 
    return $result;
  }
  
  // Remove brand and functional from params
  // result[0] => array of other params if not params return empty string
  // result[1] => brand (id)
  // result[2] => functional (id)
  private function checkParams($params_field) {
	$brand = [];
	$functional = [];
	$params_temp = $params_field;
	$result = [];
	if (is_array($params_temp) && !empty($params_temp)){
		foreach ($params_temp as $ptid_key => $ptid_value) {
			if (($ptid_key == 'Производитель') || ($ptid_key == 'Бренд') || ($ptid_key == 'Производитель')){
				array_push($brand,$ptid_value);
				unset($params_temp[$ptid_key]);
			}
			if ($ptid_key == 'Функционал'){
				array_push($functional,$ptid_value);
				unset($params_temp[$ptid_key]);
			}			
		}
	}
	
	if (!empty($params_temp)){
		array_push($result,$params_temp);
	}else {
		array_push($result, NULL);
	}
	
	if (!empty($brand)){
		$brand_id = $this->getTidByName($brand[0],'brand');
		array_push($result,$brand_id);
	}else {
		array_push($result, NULL);
	}
	
	if (!empty($functional)){
		$functional_id = $this->getTidByName($functional[0],'functional');
		array_push($result,$functional_id);
	}else {
		array_push($result, NULL);
	}
	
	return $result;
  }
  
    /**
   * Utility: find term by name and vid.
   * @param null $name
   *  Term name
   * @param null $vid
   *  Term vid
   * @return int
   *  Term id or 0 if none.
   */
  private function getTidByName($name = NULL, $vid = NULL, $parent = []) {
    $properties = [];
    if (!empty($name)) {
      $properties['name'] = $name;
    }
    if (!empty($vid)) {
      $properties['vid'] = $vid;
    }
	
	if (!empty($parent)) {
      $properties['parent'] = $parent;
    }
    $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadByProperties($properties);
    $term = reset($terms);
	
	$term_id = NULL;
	
	if (empty($term)){
		$term_id = $this->term_create($name, $vid, $parent);
	} else {
		$term_id = $term->id();
	}

    return $term_id;
  }
  
  private function term_create($term, $vocabulary, array $parent = []) {

	// Create the taxonomy term.
	$new_term = Term::create([
	  'name' => $term,
	  'vid' => $vocabulary,
	  'parent' => $parent,
	]);

	// Save the taxonomy term.
	$new_term->save();

	// Return the taxonomy term id.
	return $new_term->id();
  }
  
  private function get_id_param($param_name, $param_value) {
	$parents = [];
	$parent_id = $this->getTidByName($param_name, 'params');
	
	if (!empty($parent_id)){
		array_push($parents,$parent_id);
	}
	
	$param_id = $this->getTidByName($param_value, 'params', $parents);

	// Return the taxonomy term id.
	return (!empty($param_id)) ? $param_id : NULL;
  }
  
  private function get_array_id_param($params){
	$result = [];
	
	if (is_array($params) && !empty($params)){
		foreach ($params as $pid_key => $pid_value) {
			$param_id = $this->get_id_param($pid_key, $pid_value);
			if (!empty($param_id)){
				array_push($result,$param_id);
			}
		}
	}
	
	return $result;
  }
  
  /**
   * GetStore.
   */
  private function getStore() {
    $sid = FALSE;
    $storage = \Drupal::service('entity_type.manager')->getStorage('commerce_store');
    if ($store = $storage->loadDefault()) {
      $sid = $store->id();
    }
    else {
      $ids = $storage->getQuery()
        ->range(0, 1)
        ->execute();
      if (!empty($ids)) {
        $sid = array_shift($ids);
        $store = $storage->load($sid);
        $storage->markAsDefault($store);
      }
    }
    return $sid;
  }

}
