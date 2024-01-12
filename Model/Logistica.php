<?php
/**
 * Modulo de entrega.
 *
 * @author César Martins
 * @email cesar.martins01@gmail.com
 */
class MelhorLoja_Shipping_Model_Logistica extends Mage_Core_Model_Abstract
{

    public function __construct()
    {
       $this->resource = Mage::getSingleton('core/resource')->getConnection('core_write');
    }

    public function pegarNomeDocas($arrayItens)
    {
        foreach ($arrayItens as $key => $values){

            $sql = "select code, title  
                    from warehouse where warehouse_id = " . $key;

            $fetchAll = $this->resource->fetchAll($sql);
            $retorno[$key]["stock_id"] = $key;
            $retorno[$key]["qty"] = $values->getQty();
            $retorno[$key]["code"] = $fetchAll[0]["code"];
            $retorno[$key]["title"] = $fetchAll[0]["title"];

        }

        return $retorno;
    }

    public function pegarFreteVeneza($postcode)
    {
        $cep = str_replace('-','', $postcode);
        $sql = "select * from ML_frete_veneza where '" . $cep . "' BETWEEN cep_ini AND cep_fim";
        $fetchAll = $this->resource->fetchAll($sql);
        return $fetchAll;
    }

    public function pegarFreteDelivery($postcode)
    {
        $cep = str_replace('-','', $postcode);
        $sql = "select * from ML_frete_delivery where '" . $cep . "' BETWEEN cep_ini AND cep_fim";
        $fetchAll = $this->resource->fetchAll($sql);
        return $fetchAll;
    }






    public function inserirProdutoFavoritos($userProdutos, $lista){

        $userID = key($userProdutos);
        $produtoId = $userProdutos[$userID];

        $sqlListaFavoritos = "select * from cis_lista_favoritos 
                                where lista_favoritos_user_id = :user 
                                AND lista_favoritos_id = :lista";
        $fetchAll = $this->resource->fetchAll($sqlListaFavoritos , array('user' => $userID, 'lista' => $lista));

        $metodo = "inserir";
        $ativo = 1;
        foreach ($fetchAll as $item){

            $sql = "select * from cis_lista_favoritos_produtos 
                        where lista_favoritos_id = " . $item["lista_favoritos_id"] . " 
                        AND product_id = " . $produtoId;
            $encontrado = $this->resource->fetchAll($sql);

            if(count($encontrado) >= 1){
                $metodo = "delete";
                $ativo = 0;
                $sql = "DELETE FROM cis_lista_favoritos_produtos WHERE (`idcis_lista_favoritos_produtos` =" . $encontrado[0]["idcis_lista_favoritos_produtos"] . ");";
                $this->resource->query($sql);
            }else{
                $sql = "INSERT cis_lista_favoritos_produtos (lista_favoritos_id, product_id, data_insert)";
                $sql .= "VALUES(:lista_favoritos_id,:product_id, now())";

                $data = array(
                    "lista_favoritos_id" => $item["lista_favoritos_id"],
                    "product_id" => $produtoId
                );
                $this->resource->query($sql, $data);

            }


        }
        $retorno = array("metodo" => $metodo, "ativo" => $ativo);
        return $retorno;
    }


}