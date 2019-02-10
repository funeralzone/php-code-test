<?php

/**
 * Created by PhpStorm.
 * User: jamesskywalker
 * Date: 09/02/2019
 * Time: 23:44
 *
 *
 * Merchant specific functions don't belong in the GetBookList
 * we would not expect to be updating, or adding merchants in a class for getting lists of books
 *
 *
 *
 */
class Merchant
{
    public function __construct()
    {
        //assuming that in production we would be within an MVC with routing and spl_autoloader etc
        include ('pdoConnect.php');
        $pdo = new pdoConnect();
        $this->conn = $pdo->getPdoConnection();
    }

    public function addMerchant($merchantName,$merchantBaseApi,$merchantEmail,$merchantTel,$merchantAddress,$merchantPostcode,$merchantMainContact,$accountActiveFrom = 0,$accountExpires = 0) {
        $now = time();
        $q = $this->conn->prepare("INSERT INTO merchants (
										merchant_name,
										base_api,
										email,
										tel,
										address,
										postcode,
										main_contact,
										account_active_from,
										account_expires,
										created,
										last_updated)

										VALUES

										:merchant_name,
										:base_api,
										:email,
										:tel,
										:address,
										:postcode,
										:main_contact,
										:account_active_from,
										:account_expires,
										:now,
										:now");

        $q->bindParam(":merchant_name",$merchantName,PDO::PARAM_STR);
        $q->bindParam(":base_api",$merchantBaseApi,PDO::PARAM_STR);
        $q->bindParam(":email",$merchantEmail,PDO::PARAM_STR);
        $q->bindParam(":tel",$merchantTel,PDO::PARAM_STR);
        $q->bindParam(":address",$merchantAddress,PDO::PARAM_STR);
        $q->bindParam(":postcode",$merchantPostcode,PDO::PARAM_STR);
        $q->bindParam(":main_contact",$merchantMainContact,PDO::PARAM_STR);
        $q->bindParam(":account_active_from",$accountActiveFrom,PDO::PARAM_INT);
        $q->bindParam(":account_expires",$accountExpires,PDO::PARAM_INT);
        $q->bindParam(":now",$now,PDO::PARAM_INT);
        return $q->execute();
    }

    public function updateMerchant($merchantId,$merchantName = "",$merchantBaseApi = "",$merchantEmail = "",$merchantTel = "",$merchantAddress = "",$merchantPostcode = "",$merchantMainContact = "",$accountActiveFrom = 0,$accountExpires = 0) {
        $pdo_bind_array = [];
        $sql_updates = [];
        if($merchantName != ""){$sql_updates[] = "merchant_name = :merchant_name";$pdo_bind_array[':merchant_name'] = $merchantName;}
        if($merchantBaseApi != ""){$sql_updates[] = "base_api = :base_api";$pdo_bind_array[':base_api'] = $merchantBaseApi;}
        if($merchantEmail != ""){$sql_updates[] = "email = :email";$pdo_bind_array[':email'] = $merchantEmail;}
        if($merchantTel != ""){$sql_updates[] = "tel = :tel";$pdo_bind_array[':tel'] = $merchantTel;}
        if($merchantAddress != ""){$sql_updates[] = "address = :address";$pdo_bind_array[':address'] = $merchantAddress;}
        if($merchantPostcode != ""){$sql_updates[] = "postcode = :postcode";$pdo_bind_array[':postcode'] = $merchantPostcode;}
        if($merchantMainContact != ""){$sql_updates[] = "main_contact = :main_contact";$pdo_bind_array[':main_contact'] = $merchantMainContact;}
        if($accountActiveFrom > 0){$sql_updates[] = "account_active_from = :account_active_from";$pdo_bind_array[':account_active_from'] = $accountActiveFrom;}
        if($accountExpires > 0){$sql_updates[] = "account_expires = :account_expires";$pdo_bind_array[':account_expires'] = $accountExpires;}

        if(sizeof($sql_updates) > 0 && sizeof($sql_updates) == sizeof($pdo_bind_array)) {
            $sql = "UPDATE merchants SET " . implode(", ",$sql_updates) . ", updated = :now WHERE id = :merchant_id";
            $pdo_bind_array[':merchant_id'] = $merchantId;
            $pdo_bind_array[':now'] = time();
            $q = $this->conn->prepare($sql);
            return $q->execute($pdo_bind_array);
        }
        return false;
    }

    public function getMerchant($merchantId) {
        $q= $this->conn->prepare("SELECT * FROM merchants WHERE id = :merchant_id");
        $q->execute([':merchant_id' => $merchantId]);
    }

    public function addMerchantEndpoint($merchantId,$queryType,$endpoint) {
        $q = $this->conn->prepare(" INSERT INTO merchant_endpoints (merchant_id, query_type,endpoint,added,updated)
                                    VALUES (:merchant_id,:query_type,:endpoint,:added,:updated)
                                    ");
        $q->bindParam(':merchant_id',$merchantId,PDO::PARAM_INT);
        $q->bindParam(':query_type',$queryType,PDO::PARAM_STR);
        $q->bindParam(':endpoint',$endpoint,PDO::PARAM_STR);
        $q->bindParam(':added',$now,PDO::PARAM_INT);
        $q->bindParam(':updated',$now,PDO::PARAM_INT);
        return $q->execute();
    }
    public function updateMerchantEndpoint($id,$merchantId,$queryType = "",$endpoint = "") {

        $pdo_bind_array = [];
        $sql_updates = [];
        if($queryType != ""){$sql_updates[] = "query_type = :query_type";$pdo_bind_array[':query_type'] = $queryType;}
        if($endpoint != ""){$sql_updates[] = "endpoint = :endpoint";$pdo_bind_array[':endpoint'] = $endpoint;}

        if(sizeof($sql_updates) > 0 && sizeof($sql_updates) == sizeof($pdo_bind_array)) {
            $sql = "UPDATE merchant_endpoints SET " . implode(", ",$sql_updates) . ", updated = :now WHERE id = :id AND merchant_id = :merchant_id";
            $pdo_bind_array[':id'] = $id;
            $pdo_bind_array[':merchant_id'] = $merchantId;
            $pdo_bind_array[':now'] = time();
            $q = $this->conn->prepare($sql);
            return $q->execute($pdo_bind_array);
        }
        return false;
    }


}