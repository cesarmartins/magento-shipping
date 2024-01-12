<?php
/**
 * Modulo de entrega.
 *
 * @author César Martins
 * @email cesar.martins01@gmail.com
 */
class MelhorLoja_Shipping_Model_Carrier
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    /**
     * Carrier's code, as defined in parent class
     *
     * @var string
     */
    protected $_code = 'melhorLoja_shipping';

    /**
     * Returns available shipping rates for MelhorLoja Shipping carrier
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        /** @var Mage_Shipping_Model_Rate_Result $result */
        $result = Mage::getModel('shipping/rate_result');

        /** @var MelhorLoja_Shipping_Helper_Data $expressMaxProducts */
        $expressMaxWeight = Mage::helper('melhorLoja_shipping')->getExpressMaxWeight();

        $preco = Mage::getStoreConfig('carriers/melhorLoja_shipping/preco_entrega_valor');
        $apartirDe = Mage::getStoreConfig('carriers/melhorLoja_shipping/preco_entrega_apartir_de');

        $expressAvailable = true;
        foreach ($request->getAllItems() as $item) {

            //volume_comprimento

            $volume_comprimento = $item->getData('product')->getData('volume_comprimento');
            $volume_altura = $item->getData('product')->getData('volume_altura');
            $volume_largura = $item->getData('product')->getData('volume_largura');

            $volumeTotal = $volume_comprimento * $volume_altura * $volume_largura;

            //valor de
            //$valorCorte = 10000000;
            $valorCorte = 63000;
            $valorProduto += $item->getPrice();

            $docas = Mage::getModel('melhorLoja_shipping/logistica')->pegarNomeDocas($item->getStockItems());

            //retirada em loja
            $docasFrete = Mage::getModel('melhorLoja_shipping/logistica')->pegarFreteDelivery($request->getDestPostcode());
            $acheiDocas = false;
            if ($expressAvailable) {

               if(!empty($docasFrete)){

                   foreach ($docas as $chaves => $valoresDocas){

                       $docasId = $docasFrete[0]["filial_retira"];
                       //$achei = key($docas);
                       if($docasId == $chaves){
                           if($volumeTotal > $valorCorte){
                               $acheiDocas = false;
                           }else{
                               $acheiDocas = true;
                               //entrega expressa
                               $result->append($this->_getExpressRate($request, $docas[$chaves], $docasFrete));
                           }
                       }
                   }
               }
//               if(!$acheiDocas){
//                   $result->append($this->_getExpressRate($request, $docas[4], $docasFrete));
//               }

                //frente normal
                $docasFreteVeneza = Mage::getModel('melhorLoja_shipping/logistica')->pegarFreteVeneza($request->getDestPostcode());
                $result->append($this->_getStandardRate($docasFreteVeneza));
                $result->append($this->_getRetiradaRate($docasFreteVeneza));
            }
        }



        //if($valorProduto >= $apartirDe){
         //   $preco = '0.0';
        //}


        //$faixaCep = Mage::getStoreConfig('carriers/melhorLoja_shipping/faixa_cep_inicio');
        //$variosCep = explode(";",$faixaCep);

        //$postcode = str_replace('-','',$request->getDestPostcode());

        //$mostrar = false;
       /* foreach ($variosCep as $cep){
            if(!empty($cep)){
                $dest = $this->trataCepValor($cep);
                if (($postcode >= $dest["cep"][0] && $postcode <= $dest["cep"][1])) {
                    //$mostrar = true;
                    if ($expressAvailable) {
                        $result->append($this->_getExpressRate($request, $dest["valor"]));
                    }
                }
            }
        }*/


        //$result->append($this->_getStandardRate());

        return $result;
    }

    public function trataCepValor($cepValor){
        $dest = explode(",",$cepValor);
        $valor = explode("=",$dest[1]);
        $returnArray["cep"][0] = $dest[0];
        $returnArray["cep"][1] = $valor[0];
        $returnArray["valor"] = $valor[1] . "," . $dest[2];
        return $returnArray;
    }

    /**
     * Returns Allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return array(
            'standard'    =>  'Standard delivery',
            'express'     =>  'Express delivery',
            'retirada'     =>  'Retirada delivery',
        );
    }

    /**
     * Get Standard rate object
     *
     * @return Mage_Shipping_Model_Rate_Result_Method
     */
    protected function _getStandardRate($docasFreteVeneza)
    {
        /** @var Mage_Shipping_Model_Rate_Result_Method $rate */
        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($docasFreteVeneza[0]["cidade_de_entrega"]);
        $rate->setMethod('melhorLoja_standard');
        $rate->setMethodTitle($docasFreteVeneza[0]["prazo"]);
        $price = trim(str_replace('R$','', $docasFreteVeneza[0]["valor_frete"]));
        $rate->setPrice($price);
        $rate->setCost(0);

        return $rate;
    }
    protected function _getRetiradaRate($docasFreteVeneza)
    {
        /** @var Mage_Shipping_Model_Rate_Result_Method $rate */
        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($docasFreteVeneza[0]["cidade_de_entrega"]);
        $rate->setMethod('melhorLoja_retirada');
        $rate->setMethodTitle("4 horas");
        $rate->setPrice(0);
        $rate->setCost(0);

        return $rate;
    }

    /**
     * Get Express rate object
     *
     * @return Mage_Shipping_Model_Rate_Result_Method
     */
    protected function _getExpressRate($request, $docas, $docasFrete)
    {
        /** @var Mage_Shipping_Model_Rate_Result_Method $rate */
        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($docas["title"]);
        $rate->setMethod('melhorLoja_express');
        $rate->setMethodTitle("Aproximanente 1 dia (Util)");
        //$rate->setPrice($dados->valor);
        $price = trim(str_replace('R$','', $docasFrete[0]["valor"]));
        $rate->setPrice($price);
        $rate->setCost(0);

        return $rate;
    }

}