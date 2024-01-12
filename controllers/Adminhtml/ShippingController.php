<?php
/**
 * Modulo de entrega.
 *
 * @author César Martins
 * @email cesar.martins01@gmail.com
 */
class MelhorLoja_Shipping_Adminhtml_ShippingController extends Mage_Adminhtml_Controller_Action {

	public $bannerApp = false;

	public function preDispatch()
	{
	    parent::preDispatch();

	}

    public function indexAction() {
        die("chegou aqui index - admin");
    }

    public function deliveryAction() {
        die("chegou aqui delivery - admin");
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