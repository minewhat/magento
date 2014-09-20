<?php

/**
 * @category    MineWhat
 * @package     MineWhat_Insights
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MineWhat_Insights_ApiController extends Mage_Core_Controller_Front_Action {

    const CONFIG_API_KEY = 'minewhat_insights/settings/api_key';

    public function _authorise() {

        $API_KEY = Mage::getStoreConfig(self::CONFIG_API_KEY);

        // Check for api access
        if(!$API_KEY && strlen($API_KEY) === 0) {
            // Api access disabled
            $this->getResponse()
                    ->setBody(json_encode(array('status' => 'error', 'message' => 'API access disabled')))
                    ->setHttpResponseCode(403)
                    ->setHeader('Content-type', 'application/json', true);
            return false;
        }

        $authHeader = $this->getRequest()->getHeader('authorization');

        if (!$authHeader) {
            Mage::log('Unable to extract authorization header from request', null, 'minewhat.log');
            // Internal server error
            $this->getResponse()
                    ->setBody(json_encode(array('status' => 'error', 'message' => 'Internal server error')))
                    ->setHttpResponseCode(500)
                    ->setHeader('Content-type', 'application/json', true);
            return false;
        }

        if(trim($authHeader) !== trim($API_KEY)) {
            // Api access denied
            $this->getResponse()
                    ->setBody(json_encode(array('status' => 'error', 'message' => 'Api access denied')))
                    ->setHttpResponseCode(401)
                    ->setHeader('Content-type', 'application/json', true);
            return false;
        }

        return true;
        
    }

    public function ordersAction() {

        try {

            if(!$this->_authorise()) {
                return $this;
            }

            $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));


            if(isset($sections[3])) {
                // Looking for a specific order
                $orderId = $sections[3];
                
                $order = Mage::getModel('sales/order')->load($orderId, 'increment_id');
                
                $items = array();
                
                $orderItems = $order->getItemsCollection()->load();
                
                foreach($orderItems as $key => $orderItem) {
                    $items[] = array(
                            'name'  =>  $orderItem->getName(),
                            'pid'   =>  $orderItem->getProductId(),
                            'sku'   =>  $orderItem->getSku(),
                            'qty'   =>  $orderItem->getQtyOrdered(),
                            'price' =>  $orderItem->getPrice()                        
                        );
                }

                $this->getResponse()
                    ->setBody(json_encode(array('order_id' => $orderId, 'items' => $items, 'ip' => $order->getRemoteIp())))
                    ->setHttpResponseCode(200)
                    ->setHeader('Content-type', 'application/json', true);            
            } else {
                // Looking for a list of orders
                $currentTime = time();
                $fromDate = $this->getRequest()->getParam('fromDate', date('Y-m-d', ($currentTime - 86400)));
                $toDate = $this->getRequest()->getParam('toDate', date('Y-m-d', $currentTime));

                $orders = array();

                $ordersCollection = Mage::getModel('sales/order')->getCollection()
                    //->addFieldToFilter('status', 'complete')
                    ->addAttributeToSelect('customer_email')
                    ->addAttributeToSelect('created_at')
                    ->addAttributeToSelect('increment_id')
                    ->addAttributeToSelect('status')
                    ->addAttributeToFilter('created_at', array('from' => $fromDate, 'to' => $toDate))
                ;

                foreach ($ordersCollection as $order) {
                    $orders[] = array(
                        'order_id'      =>  $order->getIncrementId(),
                        'status'        =>  $order->getStatus(),
                        'email'         =>  $order->getCustomerEmail(),
                        'created_at'    =>  $order->getCreatedAt()
                        );
                }

                $this->getResponse()
                    ->setBody(json_encode(array('orders' => $orders, 'fromDate' => $fromDate, 'toDate' => $toDate)))
                    ->setHttpResponseCode(200)
                    ->setHeader('Content-type', 'application/json', true);

            }

        } catch(Exception $e) {
            $this->getResponse()
                ->setBody(json_encode(array('status' => 'error', 'message' => 'Internal server error')))
                ->setHttpResponseCode(500)
                ->setHeader('Content-type', 'application/json', true);
        }

        return this;

    }

    public function productsAction() {

        try {

            if(!$this->_authorise()) {
                return $this;
            }

            $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));
            $products = array();

            $attributes = array(
               'name',
               'sku',
               'image',                
               'manufacturer',
               'price',
               'final_price',
               'special_price',
               'short_description'
            );

            $extras = $this->getRequest()->getParam('extras');
            $allAttrs = $this->getRequest()->getParam('allAttrs', 'false') === 'true';
            if($extras && strlen($extras)) {
                $extras = explode(',', $extras);
                for($i = 0;$i < sizeof($extras);$i++) {
                    $extras[$i] = trim($extras[$i]);
                    $attributes[] = $extras[$i];
                }
            }

            if(isset($sections[3])) {
                // Looking for a specific product
                $productId = $sections[3];

                $product = Mage::getModel('catalog/product')->load($productId);

                $product = $this->getFormatedProduct($product, $extras, $allAttrs);
                if($product !== null) {
                    $products[] = $product;
                }

            } else {
                // Looking for a list of products
                $limit = $this->getRequest()->getParam('limit', 100);
                $offset = $this->getRequest()->getParam('offset', 1);

                $productsCollection = Mage::getModel('catalog/product')->getCollection();
                $productsCollection
                ->addAttributeToSelect($attributes)
                ->getSelect()->limit($limit, $offset)   //we can specify how many products we want to show on this page
                ;

                foreach($productsCollection as $product) {
                    $product = $this->getFormatedProduct($product, $extras, $allAttrs);
                    if($product !== null) {
                        $products[] = $product;
                    }                 
                }

            }

            $currency = Mage::app()->getStore()->getCurrentCurrencyCode();

            $this->getResponse()
                ->setBody(json_encode(array('products' => $products, 'currency' => $currency)))
                ->setHttpResponseCode(200)
                ->setHeader('Content-type', 'application/json', true);
        

        } catch(Exception $e) {
            $this->getResponse()
                ->setBody(json_encode(array('status' => 'error', 'message' => 'Internal server error')))
                ->setHttpResponseCode(500)
                ->setHeader('Content-type', 'application/json', true);
        }
        
        return $this;

    }

    private function getFormatedProduct($product, $extras, $allAttrs) {

        $formatedProduct = null;

        try {
            $formatedProduct = array(
                'id'            =>  $product->getId(),
                'sku'           =>  $product->getSku(),
                'name'          =>  $product->getName(),
                'cat'           =>  array(),
                'manufacturer'  =>  $product->getAttributeText('manufacturer'),
                'price'         =>  $product->getPrice(),
                'final_price'   =>  $product->getFinalPrice(),
                'special_price' =>  $product->getSpecialPrice(),                
                'image'         =>  $product->getImageUrl(),
                'url'           =>  $product->getProductUrl(),
                'info'          =>  $product->getShortDescription(),
                'status'        =>  $product->getStatus()
            );
            if(!$formatedProduct['manufacturer'] || strlen($formatedProduct['manufacturer']) === 0) {
                $product = Mage::getModel('catalog/product')->load($product->getId());
                $formatedProduct['manufacturer'] = $product->getAttributeText('manufacturer');
            }

            if($allAttrs) {
                $attributes = $product->getAttributes();
                foreach($attributes as $key => $value) {
                    $formatedProduct['extras'][$key] = $product->getAttributeText($key);
                }
            } else {
                foreach($extras as $key) {
                    $formatedProduct['extras'][$key] = $product->getAttributeText($key);
                }
            }

            $categories = $product->getCategoryCollection()->addAttributeToSelect('name');
            foreach($categories as $category) {
                $formatedProduct['cat'][] = $category->getName();
            }     
        } catch(Exception $e) {}

        return $formatedProduct;

    }
   
}
