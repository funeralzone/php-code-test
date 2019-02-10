<?php

class GetBookList {

	private $conn;
	private $merchantDetail;
	private $searchType;
	private $author;
	private $title;
	private $genre;
	private $isbn;
	private $published;
	private $format;

	public function __construct()
	{
		//assuming that in production we would be within an MVC with routing and spl_autoloader etc
		include ('pdoConnect.php');
		$pdo = new pdoConnect();
		$this->conn = $pdo->getPdoConnection();
	}

	public function getBookListByMerchant($merchantId,$searchType = "author",$author = "",$title = "",$genre = "",$isbn = "",$published = 0,$format = "json",$limit = 10) {
		$this->setMerchantApiDetails($merchantId);
		$this->searchType = $searchType;
		$this->author = $author;
		$this->title = $title;
		$this->genre = $genre;
		$this->isbn = $isbn;
		$this->published = $published;
		$this->format = $format;
		$apiResult = $this->sendApiRequest($limit);

		if($apiResult->httpCode == 200) {
			return $this->formatApiResponse($apiResult);

		} else {
			return (object)['Error' => "We could not retrieve the book list at this time.  The server reported back with a " . $apiResult->httpCode . " error code."];
		}

	}

	private function getMerchantAPIDetails($merchantId) {
		$q = $this->conn->prepare("	SELECT m.id, m.base_api,me.query_type,me.endpoint
									FROM merchants m
									INNER JOIN merchant_endpoints me ON (me.merchant_id = m.id)
									WHERE m.id = :merchant_id
									AND account_active_from < :now
									AND (account_expires > :now OR account_expires = 0)");
		$timestamp = time();//cannot call time directly in the bind as it requires a variable
		$q->bindParam(':merchant_id',$merchantId,PDO::PARAM_INT);
		$q->bindParam(':now',$timestamp,PDO::PARAM_INT);
		$q->execute();
		return $q->fetchAll();
	}

	private function setMerchantApiDetails($merchantId) {
		$merchantDetails = $this->getMerchantAPIDetails($merchantId);
		if(sizeof($merchantDetails) > 0){
			$this->merchantDetail->id = $merchantDetails[0]->id;
			$this->merchantDetail->base_api = $merchantDetails[0]->base_api;
			//create a easily searchable key=>value pair for setting endpoints etc
			$this->merchantDetail->endpoints = array_combine(
				array_filter(array_map(function($query_type){return $query_type->query_type;},$merchantDetails)),
				array_filter(array_map(function($endpoint){return $endpoint->endpoint;},$merchantDetails))
			);
		}

	}

	private function setEndpoint() {
		if(array_key_exists($this->searchType,$this->merchantDetail->endpoints)) return $this->merchantDetail->endpoints[$this->searchType];
		return "";
	}

	private function setApiQuerystring() {
		$q = "?q=";
		switch($this->searchType) {
			case "title":
				 $q .= $this->title;
				break;
			case "genre":
				$q .= $this->genre;
				break;
			case "isbn":
				$q .= $this->isbn;
				break;
			case "published":
				$q .= $this->published;
				break;
			default:
				$q .= $this->author;
		}

		if(isset($this->author) && $this->author != "" && $this->searchType != "author" ) $q .= "&author=" . $this->author;
		if(isset($this->title) && $this->title!= "" && $this->searchType != "title" ) $q .= "&title=" . $this->title;
		if(isset($this->genre) && $this->genre != "" && $this->searchType != "genre" ) $q .= "&genre=" . $this->genre;
		if(isset($this->isbn) && $this->isbn != "" && $this->searchType != "isbn" ) $q .= "&author=" . $this->isbn;
		if(isset($this->published) && $this->published != "" && $this->searchType != "published" ) $q .= "&published=" . $this->published;
		return $q;

	}

	private function sendApiRequest($limit = 10)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "http://" . $this->merchantDetail->base_api . "/" . $this->setEndpoint() . $this->setApiQuerystring() . "&limit=" . $limit . "&format=" . $this->format);
		$rawResponse = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return (object)["rawResponse" => $rawResponse,"httpCode" => $httpCode];
	}

	private function formatApiResponse($apiResponse) {
		//of course when adding more merchants we would have to specify the paths and object names etc
		//assuming that paths below for merchant "1" would look this way in the documentation
		if($this->merchantDetail->id == 1) {

			if($this->format == 'json'){
				return array_filter(array_map(function($result){
													return (object)[
													'title'    => $result->book->title,
													'author'   => $result->book->author,
													'isbn'     => $result->book->isbn,
													'quantity' => $result->stock->quantity,
													'price'    => $result->stock->price];
											},
									json_decode($apiResponse->rawResponse))
				);
			} else if ($this->format == 'xml') {
				$xml = simplexml_load_string($apiResponse->rawResponse);
				$json = json_encode($xml);

				return array_filter(array_map(function($result){
										return (object)[
										'title'    => $result->book['title'],
										'author'   => $result->book['author'],
										'isbn'     => $result->book['isbn'],
										'quantity' => $result->stock['quantity'],
										'price'    => $result->stock['price']
										];
									},
									json_decode($json))
						);
			}
		}
		return false;
	}
}
