<?php
/**
 * Modulo de entrega.
 *
 * @author César Martins
 * @email cesar.martins01@gmail.com
 */
class MelhorLoja_Shipping_IndexController extends Mage_Core_Controller_Front_Action {

	public $bannerApp = false;

	public function preDispatch()
	{
	    parent::preDispatch();

	}

    public function indexAction() {

        //$cep = $this->getRequest()->getParam('id');

        $params = $this->getRequest()->getParam('estimate');
        $country    = 'BR';//(string) $this->getRequest()->getParam('country_id');
        $postcode   = (string) $params["postcode"];
        $qty = intval($this->getRequest()->getParam('qty'));
        if($qty == 0 || $qty == null){
            $qty = 1;
        }

        $currentProductId = $this->getRequest()->getPost('product');
        $quote = Mage::getModel('sales/quote')->setStoreId(Mage::app()->getStore('default')->getId());
        $_product = Mage::getModel('catalog/product')->load($currentProductId);
        $params = $this->getRequest()->getParams();
        $reqOb = new Varien_Object($params);
        $_product->getStockItem()->setUseConfigManageStock(false);
        $_product->getStockItem()->setManageStock(false);
        $quote->addProduct($_product, $reqOb);
        $quote->getShippingAddress()->setCountryId($country)->setPostcode($postcode);
        $quote->getShippingAddress()->collectTotals();
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();

        $groups = $quote->getShippingAddress()->getGroupedAllShippingRates();

        $shippingRates = array();
        $shippingHtml = "<div class='titulo-entregas'><strong><span>Entregas</span></strong><hr></div>";
        $shippingHtml .= "<div class='conteudo-entregas'>";
        $shippingBlock = new Mage_Checkout_Block_Cart_Shipping();
        foreach($groups as $code=>$_rates){
            //$shippingHtml .= "<dt>" . $shippingBlock->getCarrierName($code) . "</dt><dd><ul>";
            $shippingHtml .= "<dd><ul>";
            foreach ($_rates as $_rate) {
                //if($_rate->getPrice() > 0) {
                $shippingHtml .= "<li><label>";
                if($_rate->getMethod() == "melhorLoja_express"){
                    $shippingHtml .= "<spam><b>Entrega Expressa</b></spam><br>";
                }elseif($_rate->getMethod() == "melhorLoja_standard"){
                    $shippingHtml .= "<spam><b>Transportadora Veneza</b></spam><br>";
                }else{
                    $shippingHtml .= "<spam><b>Retirar na loja</b></spam><br>";
                }

                $shippingHtml .= "<spam>" . $_rate->getCarrierTitle() . "</spam><br>";
                $shippingHtml .= $_rate->getMethodTitle() . "<br>";
                //$shippingHtml .= " - ";
                $shippingHtml .= Mage::helper('core')->currency($_rate->getPrice(), true, false);
                $shippingHtml .= "</label><hr></li>";
            }
            $shippingHtml .= "</ul></dd>";
        }
        $shippingHtml .= "<span>*Após a confimação do pagamento.</span>";
        $shippingHtml .= "</div>";
        echo $shippingHtml;
        //$this->getResponse()->setBody($shippingHtml);

        /*
                $methods = Mage::getSingleton('shipping/config')->getActiveCarriers();
                foreach ($methods as $shippingMethodCode => $shippingMethod)
                {
                    echo $shippingTitle = Mage::getStoreConfig('carriers/'.$shippingMethodCode.'/title');
                    echo "<br>";
                }

                        $shipMethods = array();
                        foreach ($methods as $shippigCode=>$shippingModel)
                        {

                            $shippingTitle = Mage::getStoreConfig('carriers/'.$shippigCode.'/title');
                            $shippingPrice = Mage::getStoreConfig('carriers/'.$shippigCode.'/price');
                            $shippingLabel = Mage::getStoreConfig('carriers/'.$shippigCode.'/label');
                            $shipMethods[]=array('Shipping Type' => $shippigCode, 'title'=> $shippingTitle, 'price'=> $shippingPrice, 'label' => $shippingLabel);


                        }
                        echo "<pre>";
                        print_r($shipMethods);*/


        //die("chegou aqui index dd- front" . $cep);
    }

//	public function indexAction() {
//
////		$instagram = $this->getRequest()->getParam('id');
//		Mage::getModel('experience/experience')->insertUserIp();
//
////		$this->bannerApp = true;
////		Mage::getSingleton('core/session')->setData("banner_app", $this->bannerApp);
//
//		$couponValues['type'] = 2;
//		$couponValues['is_special'] = true;
//		$couponValues['wv'] = false;
//		$couponValues['showInstagram'] = true;
//
//		Mage::getModel('customer/session')->setData('coupon', $couponValues);
//		Mage::getModel('customer/session')->unsetData('coupon_disable');
//
//		$this->_redirect('superheat');
//	}

	public function loginAction() {
		$this->loadLayout();

		$tree = array();
		$slug = $this->getRequest()->getParam('id');
		$code = $this->getRequest()->getParam('code');
		$body = $this->getLayout()->getBlock('experience');



		if(!empty($slug) || !empty($code)){

			$tree = Mage::getModel('tree/tree')->getTreeBySlugOrCode($slug, $code);

			if(!empty($tree) && $tree['customer_id']){
			    $customerId = $tree['customer_id'];
			    $customer = Mage::getModel('customer/customer')->load($customerId);

				if(!empty($customer->getEmail())){
			    	$tree['customer_email'] = $customer->getEmail();
			    }
			}

			// Mage::getModel('customer/session')->unsetData('experienceitutorial');
			// Mage::getModel('customer/session')->setData('experienceitutorial', true);

		}

		$body->setTree($tree);

		if ($this->getRequest()->isPost()) {

            $login = $this->getRequest()->getPost('login');
            $customerSession = Mage::getSingleton('customer/session');
            $coreSession = Mage::getSingleton('core/session');

            if(!Mage::getModel('tree/tree')->isParticipantByCustomerId(null, $login['username'])){
	    		Mage::getSingleton('customer/session')->logout();
	    		$coreSession->addError($this->__('You are not a Seaway Experience participant.'));
	    		$this->_redirect('experience/login', array('_secure' => true));
	        	return;
	    	}

			$valores = Mage::getModel('tree/deadline')->getTreeByEmail($login['username']);
			if(!Mage::getModel('tree/deadline')->verifyDateApp($valores)){
				Mage::getSingleton('customer/session')->logout();
				$dataApp = $valores['date_app'];


				$msg = "The link for you to indicate your 5 surfer friends expired on $dataApp.<br/>
If you have any questions, please contact Seaway by instagram @seaway_usa or email sac@seaway.surf.";

				$coreSession->addError($msg);
				$this->_redirect('experience/login', array('_secure' => true));
				return;
			}


            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $customerSession->login($login['username'], $login['password']);
                } catch (Exception $e) {
                	$coreSession->addError($this->__('Invalid login or password.'));
                    // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                }
            } else {
                $coreSession->addError($this->__('Login and password are required.'));
            }

            $this->_redirect('experience/login');
        }

		Mage::getModel('track/track')->trackLog();
	    $this->renderLayout();
	}

	  /*
       * Fun��o que salva a informa��o de que imagem para convidar
       * 1 -  img salva foi seaway quem gerou
       * 2 -  img salva foi ele quem editou e gerou
        */
		public function saveimageinviteAction(){
			try{

				$result = array(
					'status' => false,
					'msg'	 => ''
				);

				if(!$this->getRequest()->isPost()){
					throw new Exception("Request is not valid.", -1);
				}



				// instagram of child
				$instagram 	 = $this->getRequest()->getParam('instagram');
				$saveas 	 = $this->getRequest()->getParam('saveas');

				if( !isset($instagram)|| empty($instagram)){
					throw new Exception("Instagram is empty.", -1);
				}

				Mage::getModel('tree/app')->saveImageInvite($instagram , $saveas );
				$result['status'] = true;
				$result['msg']  = "success";


			}catch (Exception $e){
				$result['msg'] = $e->getMessage();
			}

			header('Content-Type:application/json');
			echo json_encode($result);
			die;

		}


	public function savecopyinviteorindicatelinkAction() {


		if($this->getRequest()->isPost()){
        	
        	$result = array(
	            'status' => false
	        );

            $instagram = $this->getRequest()->getParam('instagram');
            $target = $this->getRequest()->getParam('target');

            if(empty($instagram)){
            	throw new Exception("instagram is empty.", -1);
            }

            if(empty($target)){
            	throw new Exception("target is empty.", -1);
            }

            try{
    			$tree = Mage::getModel('tree/app')->saveCopyInviteOrIndicateLink($instagram, $target);
    			$result['status'] = true;
	    	}catch (Exception $e){
				if($e->getCode() == -3){
					echo $e->getMessage();
				}    		
	    	}

	        header('Content-Type:application/json');
	        echo json_encode($result);
	        die;

        }


	}

}