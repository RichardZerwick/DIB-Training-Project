<?php 

class ProductsController extends Controller {

    function useStock($containerName, $productId, $clientData) {
        
        // Business Logic 
        // var_dump($clientData);
        // die();
        $result = Database::execute("UPDATE products SET product_quantity=product_quantity-1 WHERE id=:id",['id' => $productId], DIB::LOGINDBINDEX);
        Log::w($result);
        if ($result) {
            ClientFunctions::addAction(DibApp::$clientActions, 'reload-container', array('value' => 'products'));
            return $this->success('The Stock quantity has successfully updated','dialog');
        } else { 
            return $this->failure('The Stock quantity could not be updated','dialog');
        }
        
    }
}