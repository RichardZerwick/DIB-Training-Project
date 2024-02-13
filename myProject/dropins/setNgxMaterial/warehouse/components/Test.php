<?php

class Test  {

    //
    public function afterReadMany($containerName, &$attributes, $trigger, $filterParams) {
        // Run through products and calculate the value of the product
        
        foreach ($attributes as &$attribute) {
            $attribute['totalValue'] = $attribute['product_quantity'] * $attribute['price'];
        }
        
		return TRUE;

    }

}