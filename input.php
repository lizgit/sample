<?php
	
	// sanitizes posted data. returns default value when posted data is empty
	function getPost($inputName, $defaultValue = NULL, $datatype= NULL) 
	{
		if(!isset($_POST[$inputName])) return $defaultValue;
		if(is_array($_POST[$inputName]))
		{
			$returnArray = array();
			foreach($_POST[$inputName] as $value) $returnArray[] = sanitize($value, $datatype);
			return $returnArray;
		}
		else return sanitize($_POST[$inputName],$datatype);
	}
	
	// sanitizes query string. returns default value when query is empty
	function getQueryString($queryName, $defaultValue = NULL, $datatype = NULL) 
	{
		if(isset($_GET[$queryName])) $query = sanitize($_GET[$queryName],$datatype);
		else $query = $defaultValue;
		return $query;		
	}
	
	// sanitizes data according to its data type
	function sanitize($string, $datatype)
	{
		$clean = NULL;
		if($string) $string = trim($string);
		if($string == '') $clean = '';
		elseif($datatype == 'html') $clean = $string; //raw, do not sanitize to allow html and javascript code
		elseif($datatype == 'number' && !preg_match('/^[0-9\.]*$/',$string)) $clean = 0; // numbers only
		elseif($datatype == 'words') $clean = preg_replace('/[^a-zA-Z0-9_@,\.\-: ]/','',$string); // words only
		elseif($datatype == 'email' && !preg_match('/^[a-zA-Z0-9_@,\.\-]*$/',$string)) $clean = NULL; // email only
		elseif($datatype == 'url') $clean = preg_replace('/[^a-zA-Z0-9\+\-_\?&\.\/:~#=%]/','',$string); //only allows a few special characters in url 
		elseif($datatype == 'date') $clean = preg_replace('/[^a-zA-Z0-9\.\-\/: ]/','',$string); // only dot, dash and colon allowed
		elseif($datatype == 'text')  $clean = htmlspecialchars($string, ENT_QUOTES); // html encoding
		else $clean = htmlspecialchars($string); // when datatype not specified, do html encoding	
		return $clean;	
	}

?>