<?php 

/* database - singleton class that communicates directly with the database and provide methods for modules to access data
 * 			  without diretly dealing with database
 */
class database{
	public $error;
  	private $connection;         
	private $rows;
	private static $instance;
	
	const DB_SERVER = "localhost";
	const DB_USER = "root";
	const DB_PASS = "mysql";
	const DB_NAME = "WebDB";
	

	/* Class constructor */
    private function __construct(){
		$this->connection = new mysqli(self::DB_SERVER, self::DB_USER, self::DB_PASS, self::DB_NAME);
		if($this->connection->connect_error > 0) $this->error = 'Error connecting with database['.$this->connection->connect_error.']'; 
	}
		
	/* Class destructor */
	public function __destruct(){
		$this->connection->close();
	}
	
	public static function & getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
 
   /**
    * query - Performs the given query on the database and
    * returns the result, which may be false, true or a
    * resource identifier.
    */
 	public function query($query, $multiple=false){
	   if($multiple) $result = $this->connection->multi_query($query); // run multiple statements
  	   else $result = $this->connection->query($query);
	   if (!$result) {
	   		$this->error = 'Error:'.$this->connection->connect_error;
			return false;
		}
		else return true;
 	}
	
	/**
	 * query-params - performs query but with supplied params to prevent SQL injection
	 *
	 */
	public function query_params($query, $paramsArray){
		$param_type = '';
		for($i=0;$i<count($paramsArray);$i++) $param_type .= 's'; // string params only
		$params = array();
		$params[] = & $param_type;
		for($i=0;$i<count($paramsArray);$i++) $params[] = & $paramsArray[$i];
		
		$stmt = $this->connection->prepare($query);
		if($stmt === false){
			trigger_error('Wrong SQL:'.$query.' Error: '.$this->connection->error);
		}
		
		call_user_func_array(array($stmt,'bind_param'),$params);
		$stmt->execute();
		$result = $stmt->get_result();
	   if (!$result) {
	   		$this->error = 'Error:'.$this->connection->connect_error;
			return false;
		}
		else return $result;
	}
	
	
	/* execute insert query, returns false or inserted id */	
	public function query_insert($query){
	   $result = $this->connection->query($query);
	   if (!$result) {
	   		$this->error = 'Error:'.$this->connection->error();
			return false;
		}
		else return $this->connection->insert_id;
 	}
	
	/* when id is 0, execute insertsql and return inserted id, 
	 * otherwise execute updatesql and return id
	 * when either sql fails return 0
	 */
	public function query_insert_update($insertSql, $updateSql, $id){
		$return = 0;
		if($id > 0){ // this is update
			$result = $this->connection->query($updateSql);
			if($result) $return = $id;
		}
		else{ // this is insert
			$result = $this->connection->query($insertSql);
			if($result) $return = $this->connection->insert_id;
		}
		return $return;	
	}
		
	// returns result of scalar query such as "SELECT COUNT"
	public function query_scalar($query){
		$result = $this->connection->query($query); 
		if(!$result) return false;
		$rows = $result->fetch_row();
		return $rows[0];
	}
	
	/***
	 * @param $query: sql statement
	 * @param $class: name of the Module class
	 * @param $callbackfunction: function to read row of data into an object
	 * @return a single object
	 */	
	public function fetch_object($query, $class, $callbackfunction) {
		$object = null;
		$result = $this->connection->query($query);
		if($result)
		{
			$row = $result->fetch_array();
			if(!empty($row)) $object = call_user_func(array($class, $callbackfunction), $row);
			$result->free();
		}
		return $object;
	}	
	
	/***
	 * @param $query: sql statement
	 * @param $class: name of the Module class
	 * @param $callbackfunction: function to read row of data into an object
	 * @return a single object
	 */	
	public function fetch_object_params($query, $arrParams, $class, $callbackfunction) {
		$object = null;
		$result = $this->query_params($query,$arrParams);
		if($result)
		{
			$row = $result->fetch_array();
			if(!empty($row)) $object = call_user_func(array($class, $callbackfunction), $row);
			$result->free();
		}
		return $object;
	}	
	
	/***
	 * Takes a query and a callback function name and returns a list of class objects
	 * @param object $query: query to get data
	 * @param object $class: the name of the class containing the call back function
	 * @param object $callbackfunction: call back function to create the object. The parameter of the function must be $row.
	 * @return a list of objects created by the callback function.
	 */
	public function fetch_object_list($query, $class, $callbackfunction) {
		$list = array();
		$result = $this->connection->query($query);
		if(!$result || $result->num_rows < 1)
		{
			$list = null;
		}
		else
		{
			while($row = $result->fetch_array())
			{
				$list[] = call_user_func(array($class, $callbackfunction), $row);
			}		
		}
		if($result) $result->free();
		return $list;
	}

	
	/* takes a query and a column name and returns an array of records with that column*/	
	public function fetch_list($query, $column) {
		$list = array();
		$result = $this->connection->query($query);
		if(!$result || $result->num_rows < 1)
		{
			$list = null;
		}
		else
		{
			while($row = $result->fetch_array())
			{
				$list[] = $row[$column];
			}
			$result->free();
		}
		return $list;			
	}

	/* takes a query and key column, value column names and returns an assosiative array of records with that key-value*/		
	public function fetch_asso_list($query, $key_column, $value_column) {
		$list = array();
		$result = $this->connection->query($query);
		if(!$result || $result->num_rows < 1)
		{
			$list = null;
		}
		else
		{
			while($row = $result->fetch_array())
			{
				$key = $row[$key_column];
				$value = $row[$value_column];
				$list[$key] = $value;
			}
			$result->free();
		}
		return $list;			
	}
		
	// get an array of arrays in (key, value) format
	public function fetch_kv_array($query, $key_column, $value_column) {
		$array = array();
		$result = $this->connection->query($query);
		if(!$result || $result->num_rows < 1)
		{
			$list = null;
		}
		else
		{
			while($row = $result->fetch_array())
			{
				$array[] = array($row[$key_column], $row[$value_column]);
			}
			$result->free();
		}
		return $array;			
	}
	
	// returns true if the query returns any row
	public function row_exists($query){
		$result = $this->connection->query($query);
		if(!$result || $result->num_rows < 1)
			return false;
		else return true;
	}
	
	// returns the number of rows returned by the given query
	public function num_rows($query){
		$result = $this->connection->query($query);
		$num = $result->num_rows;
		$result->free();
		return $num;
	}
	
	/* getExelFile - takes a query and returns data dump to generate Excel file output */
	public function getExcelFile($query){
		
		$result = $this->connection->query($query);
		$body = '';
		$count = $result->field_count;
		$header = $data = '';
		
		$fields = $result->fetch_fields();
		foreach($fields as $field) $header .= $field->name."\t";
		
		while($row = $result->fetch_row()){
		  $line = '';
		  foreach($row as $value){
			if(!isset($value) || $value == ""){
			  $value = " \t";
			}else{
		// important to escape any quotes to preserve them in the data.
			  $value = str_replace('"', '""', $value);
		// needed to encapsulate data in quotes because some data might be multi line.
		// the good news is that numbers remain numbers in Excel even though quoted.
			  $value = '"' . $value . '"' . "\t";
			}
			$line .= $value;
		  }
		  $data .= trim($line)."\n";
		}
		// this line is needed because returns embedded in the data have "\r"
		// and this looks like a "box character" in Excel
		  $data = str_replace("\r", "", $data);
		
		
		// Nice to let someone know that the search came up empty.
		// Otherwise only the column name headers will be output to Excel.
		if ($data == "") {
		  $data = "\nno matching records found\n";
		}
		$body .= $header."\n".$data."\n\n\n";	
		
		// Clean up
		$result->free();
		
		return $body;
	}
	
	/* prepares the string for SQL Server database by replacing apostrophes with double quotes
	 */
	public static function prepareString($string)
	{
		return str_replace("'", "''", trim($string));
	}
	
	/* prepares the string for SQL Server database by replacing apostrophes with double quotes
	 */
	public static function prepareInput($data)
	{
		if (empty($data) ) return $data;
		if ( is_numeric($data) ) return $data;

		$non_displayables = array(
					'/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
					'/%1[0-9a-f]/',             // url encoded 16-31
					'/[\x00-\x08]/',            // 00-08
					'/\x0b/',                   // 11
					'/\x0c/',                   // 12
					'/[\x0e-\x1f]/'             // 14-31
				);
		foreach ( $non_displayables as $regex )
			$data = preg_replace( $regex, '', $data );
		$data = str_replace("'", "''", $data );
		return $data;	
	}
	
	/* iterates through members of an object and prepare the values for SQL injection */
	public static function prepareObject($object)
	{
		foreach($object as $key=>$value)
			$object->$key = self::prepareInput($value);
		return $object;
	}

	/* prepare the keywords for full text search */
	public static function prepareKeywords($keywords)
	{
		$keywordsArray = explode(' ', $keywords);
		$noiseArray = array("0","1","2","3","4","5","6","7","8","9","$","a","about","after","all","also","an","and","another","any","are","as","at","b","be","because","been","before","being","between","both","but","by","c","came","can","come","could","d","did","do","does","e","each","else","f","for","from","g","get","got","h","had","has","have","he","her","here","him","himself","his","how","i","if","in","into","is","it","its","j","just","k","l","like","m","make","many","me","might","more","most","much","must","my","n","never","now","o","of","on","only","or","other","our","out","over","p","q","r","re","s","said","same","see","should","since","so","some","still","such","t","take","Test","than","that","the","their","them","then","there","these","they","this","those","through","to","too","u","under","up","use","v","very","w","want","was","way","we","well","were","what","when","where","which","while","who","will","with","would","x","y","you","your","z");
		$keywords = '';
		$numKeywords = count($keywordsArray);
		$i = 0;
		foreach($keywordsArray as $word)
		{
			$i++;
			if(!in_array($word, $noiseArray)) // strip words that are in the noise words array
			{
				$keywords .= '"'.$word.'"';
				if($i < $numKeywords) $keywords .= ' AND ';	
			}
		}
		
		return $keywords;
	}
	
}

?>
