<?php
/**	
 * 
 * @author eco.nxn
 */
date_default_timezone_set("Asia/Jakarta");
error_reporting(0);
class curl {
	private $ch, $result, $error;

	/**	
	 * HTTP request
	 * 
	 * @param string $method HTTP request method
	 * @param string $url API request URL
	 * @param array $param API request data
	 */
	public function curl_sp ($method, $url, $header, $param) {
		curl:
        $this->ch = curl_init();
        switch ($method){
            case "GET":
                curl_setopt($this->ch, CURLOPT_POST, false);
                break;
            case "POST":               
                curl_setopt($this->ch, CURLOPT_POST, true);
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $param);
                break;
            case "PATCH":               
                curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "PATCH");
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $param);
                break;
        }
        
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:68.0) Gecko/20100101 Firefox/68.0');
        curl_setopt($this->ch, CURLOPT_HEADER, false);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
        // curl_setopt($this->ch, CURLOPT_VERBOSE, 1);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 120);

        $this->result = curl_exec($this->ch);
        $this->error = curl_error($this->ch);
        if(!$this->result){
            if($this->error) {
                echo "[!] cURL Error: ".$this->error."\n";
                sleep(1);
                goto curl;
            } else {
                echo "[!] cURL Error: No Result\n\n";
                die();
            }
        }
        curl_close($this->ch);
        return $this->result;
    }
    
}


class grab extends curl {

	/**	
	 * Shopee product
	 * 
	 * @param string $shop_username Shop username
	 * @param string $min_price minimum product price
	 */
	public function shopee ($shop_username, $min_price) {

		$url_shop_ids  = 'https://shopee.co.id/api/v2/shop/get?username='.$shop_username; //get shop_id by username

		$getShop = $this->curl_sp ('GET', $url_shop_ids, $header=NULL, $param=NULL); 
		if ($getShop) {
		$json_shop = json_decode($getShop);

		$shop_id = $json_shop->data->shopid;
		if(!isset($shop_id)) {
			echo '['.$json_shop->error.'] '.$json_shop->error_msg." | Toko tidak ditemukan atau terjadi kesalahan lain!\n";
			die();
		}
		echo "\nShop ID :".$shop_id."\n\n";

			$url_shop_collections = 'https://shopee.co.id/api/v1/shop_collections/?filter_empty=1&limit=20&offset=0&shopid='.$shop_id;

			$get_collections = $this->curl_sp ('GET', $url_shop_collections, $header=NULL, $param=NULL);

			if($get_collections) {
				$json_collections = json_decode($get_collections);

				$no_col = 1;
				foreach ($json_collections as $data_collections) {
					$etalase_name  = $data_collections->name;
					$collection_id = $data_collections->shop_collection_id;

					$x=1;
					while ($x<=2) {
						if($x==1) {
							$url_get_items = 'https://shopee.co.id/api/v2/search_items/?by=pop&limit=100&match_id='.$shop_id.'&newest=0&order=desc&page_type=shop&original_categoryid='.$collection_id.'&version=2'; //TERSEDIA                  
							$item_status = "TERSEDIA"; 
						} else {
							$url_get_items = 'https://shopee.co.id/api/v2/search_items/?by=pop&limit=100&match_id='.$shop_id.'&newest=0&only_soldout=1&order=desc&page_type=shop&original_categoryid='.$collection_id.'&version=2'; //KOSONG                  
							$item_status = "KOSONG"; 
						}                       
                
						$get_items = $this->curl_sp ('GET', $url_get_items, $header=NULL, $param=NULL);
						if ($get_items) {

							$json_get_items = json_decode($get_items);
								$data_items = $json_get_items->items;

								if(!empty($data_items)) {
									if($no_col==1) {

										if (file_exists("Shopee_".$shop_username."[".date('d-m-Y')."].CSV")) {

											echo "Data lama akan dihapus, Lanjut? [Y/N] :";
											$next = trim(fgets(STDIN));
											if (strtolower($next)!='y') {
											echo "\n";
											die();
											} else {
												unlink("Shopee_".$shop_username."[".date('d-m-Y')."].CSV");
												echo "\n";
											}
										}
								
									}

									echo "\nETALASE : ".$etalase_name." | Total :".count($data_items)." | Status :".$item_status."\n";

									$no = 1;
									foreach ($data_items as $item) {
										$itemid = $item->itemid;

										$url_detailitem = 'https://shopee.co.id/api/v2/item/get?itemid='.$itemid.'&shopid='.$shop_id; //detail produk

										$get_detailitem = $this->curl_sp ('GET', $url_detailitem, $header=NULL, $param=NULL);
										if ($get_detailitem) {
											$json_detailitem = json_decode($get_detailitem);

											$id_item     = $json_detailitem->item->itemid;
											$img_item    = $json_detailitem->item->image;
											$brand       = $json_detailitem->item->brand;
											$description = $json_detailitem->item->description;
											$price       = ($json_detailitem->item->price)/100000;
											$sold        = $json_detailitem->item->sold;
											$create_time = $json_detailitem->item->ctime;
											$hist_sold   = $json_detailitem->item->historical_sold;
											$discount    = $json_detailitem->item->discount;
											$price_before= ($json_detailitem->item->price_before_discount)/100000;
											$name_item   = $json_detailitem->item->name;
											$variant     = $json_detailitem->item->models;
											$product_url = str_replace(' ', '',$name_item)."-i.".$shop_id.".".$id_item;

											$images = $json_detailitem->item->images;

											$categories = $json_detailitem->item->categories;
											$cat = 0;
											$category_name  = '';
											foreach ($categories as $category) {
												$cat_id   = $category->catid;
                                                
                                                if($cat == 0) {
                                                    $category_name = $category->display_name;
                                                } else {
                                                    $category_name = $category_name."/".$category->display_name;
                                                }
                                                	
                                                $cat++;                                      
                                            }
											
											$display_category = $cat_id;

											if ($price>=$min_price && empty($variant)) {
												echo "[".$no."-".$no_col."] id_item: ".$id_item." | item_name: ".$name_item."\n";
												$data_save  = "shopid;itemid;name;catid;image;price;mounth_sold;historical_sold;brand;description\r\n";
												$data_save2 = $shop_id.';'.$id_item.';'.$name_item.';'.$display_category.';https://cf.shopee.co.id/file/'.$images[0].';'.$price.';'.$sold.';'.$hist_sold.';'.$brand.';"'.str_replace('"', '', $description)."\"\r\n";

												$fh = fopen("Shopee_".$shop_username."[".date('d-m-Y')."].CSV", "a");
												
												if($no_col==1) {
													fwrite($fh, $data_save);
												}
												fwrite($fh, $data_save2);
												fclose($fh);

												unset($category_id);
												unset($category_name);
												$no++;
												$no_col++;
											} elseif (!empty($variant)){
                                                echo "[!] id_item: ".$id_item." | Skipp... reasons: variant product\n";
                                            } elseif ($price < $min_price){
                                                echo "[!] id_item: ".$id_item." | Skipp... reasons: < min price\n";
                                            }

										} else { echo "No Page Found! [Get Detail Item]\n\n";}
									}
								} else {echo "Data Item Tidak Ditemukan!\n\n";}
						} else { echo "No Page Found! [Get Items]\n\n";}

						$x++;
					}
                }
                
                if(file_exists("Shopee_".$shop_username."[".date('d-m-Y')."].CSV")) {
                    echo "\nData tersimpan di Shopee_".$shop_username."[".date('d-m-Y')."].CSV\n\n";
                }
			}
		} else { echo "No Page Found! [Get Shop_id By Username]\n\n";}
	}
}

$grab = new grab();

echo "\nShopee Products Crawling/Grabbing non-variant only...\nCoded by @eco.nxn\n";
shop_username:
echo "\nShop Username :";
$shop_username = trim(fgets(STDIN));
if (strtolower($shop_username)=='z') {
    echo "\n";
    die();
}
min_price:
echo "Harga Minimal [IDR] :";
$min_price = trim(fgets(STDIN));
if (strtolower($min_price)=='z') {
    echo "\n";
    die();
} elseif(strtolower($min_price)=='b'){
    goto shop_username;
} elseif(!is_numeric($min_price)) {
    goto min_price;
}

$grab->shopee ($shop_username, $min_price);

?>