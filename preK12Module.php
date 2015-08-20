<?php
include_once($_SERVER["DOCUMENT_ROOT"]."/lib/mysqlidb.php"); // reference to the database class	

/* activity object */
class preK12Activity
{
	public $id, $title, $type, $description, $college, $exSponsor, $ageGroup, $website, $startDate, $endDate, $county,
	$enrollment, $enrollmentNotes, $address, $fee, $research, $details, $display, $contactName, $contactPhone, $contactEmail, 
	$submitterName, $submitterPhone, $submitterEmail, $dateSubmitted, $status, $location;
	//	$location, $dates
	public function __construct()
	{
		$this->id = 0;
		$this->status = 'New';
	}
}

class preK12Module
{
	public static $collegeList, $typeList;
	
	public static function getPreK12ActivityData($row)
	{
		$a = new preK12Activity();
		foreach($a as $key=>$value) if(isset($row[$key])) $a->$key = trim($row[$key]);
		return $a;
	}
	
	public static function getPreK12Activity($aid)
	{
		$db = database::getInstance();
		//$query = "SELECT * FROM PreK12Activities WHERE id=$aid";
		//return $db->fetch_object($query,'preK12Module','getPreK12ActivityData');
		$query = "SELECT * FROM PreK12Activities WHERE id=?"; 
		return $db->fetch_object_params($query,array($aid),'preK12Module','getPreK12ActivityData');
	}
		
	public static function getPreK12Activities($tag = NULL)
	{
		$db = database::getInstance();
		$query = "SELECT * FROM PreK12Activities WHERE Status = 'Approved' ORDER BY StartDate DESC";
		if(!empty($tag)) $query = "SELECT * FROM PreK12Activities WHERE id IN 
		(SELECT activityId FROM preK12ActivityTags WHERE tag='$tag') AND Status ='Approved' ORDER BY StartDate DESC";
		return $db->fetch_object_list($query,'preK12Module','getPreK12ActivityData');
	}
	
	public static function searchPreK12Activities($keywords)
	{
		$db = database::getInstance();
		$query = "SELECT * FROM PreK12Activities WHERE Status ='Approved ";
		if(!empty($keywords))
		{
			$keywords = preg_replace('/\s+/', '" AND "', $keywords);
			$query .= "'AND CONTAINS((address,county,description,details,location,title,type), N' \"$keywords\" ')"; 
		} 
		$query .= " OR (Status='Approved' AND id IN (SELECT activityId FROM preK12ActivityTags WHERE tag='$keywords')) ORDER BY StartDate DESC";
		return $db->fetch_object_list($query,'preK12Module','getPreK12ActivityData');
	}	

	public static function countPreK12Activities($status)
	{
		$db = database::getInstance();
		$query = "SELECT COUNT(id) FROM PreK12Activities WHERE status='$status'";
		return $db->query_scalar($query);
	}
	
	public static function listPreK12Activities($count, $status=NULL)
	{
		$db = database::getInstance();
		$query = "SELECT id, title FROM PreK12Activities";
		if(!empty($status)) $query .= " WHERE status='$status'";
		$query .= " ORDER BY dateSubmitted DESC LIMIT $count";
		return $db->fetch_asso_list($query,'id','title');
	}
	
	public static function listUpcomingPreK12Activities($count,$type=NULL)
	{
		$db = database::getInstance();
		$query = "SELECT TOP $count id, title FROM PreK12Activities WHERE status='Approved'
		AND startDate >= NOW()";
		if(!empty($type)) $query .= " AND type='$type'";
		$query .= " ORDER BY startDate";
		return $db->fetch_asso_list($query,'id','title');
	}

	public static function listPreK12ActivitiesByTag($tag)
	{
		$db = database::getInstance();
		$query = "SELECT id, title FROM PreK12Activities WHERE id IN 
		(SELECT activityId FROM preK12ActivityTags WHERE tag='$tag') AND Status='Approved'
		ORDER BY dateSubmitted DESC";
		return $db->fetch_asso_list($query,'id','title');
	}
	
	public static function savePreK12Activity($a)
	{
		$db = database::getInstance();
		$a = database::prepareObject($a);
		$endDate = $a->endDate;
		if(!empty($endDate)) $endDate = trim($endDate);
		if(empty($endDate)) $endDate = 'NULL';
		else $endDate = "'$endDate'";
		$insertSql = "INSERT INTO PreK12Activities(title, type, description, college, exSponsor, ageGroup, website,
		 startDate, endDate, enrollment, enrollmentNotes, address, fee, research, county, details, display, contactName,  
		 contactPhone, contactEmail, submitterName, submitterPhone, submitterEmail, status, location) 
		 VALUES('$a->title','$a->type','$a->description','$a->college','$a->exSponsor','$a->ageGroup','$a->website',
		'$a->startDate',$endDate,'$a->enrollment','$a->enrollmentNotes','$a->address','$a->fee','$a->research',
		'$a->county','$a->details','$a->display','$a->contactName','$a->contactPhone','$a->contactEmail',
		'$a->submitterName','$a->submitterPhone','$a->submitterEmail','$a->status','$a->location')"; 
		$updateSql = "UPDATE PreK12Activities SET title='$a->title',type='$a->type',description='$a->description',
		college='$a->college',exSponsor='$a->exSponsor',ageGroup='$a->ageGroup',website='$a->website',
		startDate='$a->startDate',endDate=$endDate,address='$a->address',county='$a->county',
		fee='$a->fee',research='$a->research',contactName='$a->contactName',contactPhone='$a->contactPhone',
		contactEmail='$a->contactEmail',status='$a->status',enrollment='$a->enrollment',enrollmentNotes='$a->enrollmentNotes',
		details='$a->details',display='$a->display',submitterName='$a->submitterName',submitterPhone='$a->submitterPhone',
		submitterEmail='$a->submitterEmail',location='$a->location' 
		WHERE id=$a->id";
		return $db->query_insert_update($insertSql,$updateSql,$a->id);
	}
	
	public static function updateActivityStatus($aid,$status)
	{
		$db = database::getInstance();
		$query = "UPDATE PreK12Activities SET status='$status' WHERE id=$aid";
		return $db->query($query);
	}
	
	public static function addTag($aid, $tag)
	{
		$db = database::getInstance();
		$tag = database::prepareString($tag);
		$query = "INSERT IGNORE INTO preK12ActivityTags(activityId, tag) VALUES($aid,'$tag')";
		return $db->query($query);
	}
	
	public static function deleteTag($aid, $tag=NULL)
	{
		$db = database::getInstance();
		$tag = database::prepareString($tag);
		$query = "DELETE FROM preK12ActivityTags WHERE activityId=$aid";
		if(!empty($tag)) $query .= " AND tag='$tag'";
		return $db->query($query);
	}
	
	public static function listTags($aid)
	{
		$db = database::getInstance();
		$query = "SELECT tag FROM preK12ActivityTags WHERE activityId=$aid";
		return $db->fetch_list($query, 'tag');
	}
	
	public static function listAllTags()
	{
		$db = database::getInstance();
		$query = "SELECT COUNT(*) AS count, t.tag FROM preK12ActivityTags t 
		INNER JOIN PreK12Activities a ON t.activityId = a.id 
		WHERE a.status = 'Approved' 
		GROUP BY tag ORDER BY tag";
		return $db->fetch_asso_list($query,'tag','count');
	}	
}

preK12Module::$collegeList = array(
	"AI"=>"Academic Innovation",
	"Engineering"=>"Benjamin Statler College of Engineering and Mineral Resources",
	"Business"=>"College of Business and Economics",
	"CreativeArts"=>"College of Creative Arts",
	"Education"=>"College of Education and Human Services",
	"Law"=>"College of Law",
	"Sports"=>"College of Physical Activity and Sport Sciences",
	"Agriculture"=>"Davis College of Agriculture, Natural Resources, and Design",
	"Arts&Sciences"=>"Eberly College of Arts and Sciences",
	"Extension"=>"Extension Service",
	"Honors"=>"Honors College",
	"Media"=>"Reed College of Media",
	"Dentistry"=>"School of Dentistry",
	"Medicine"=>"School of Medicine",
	"Nursing"=>"School of Nursing",
	"Pharmacy"=>"School of Pharmacy",
	"StudentLife"=>"Student Life",
	"Potomac"=>"Potomac State College",
	"PublicHealth"=>"School of Public Health",
	"UniversityCollege"=>"University College",
	"WVUIT"=>"WVU Institute of Technology"
	);
	
preK12Module::$typeList = array("Class","Academic Camp","Academic Enrichment Day","Sport/Fitness Camp",
						"Outreach/Service Project","Contest/Competition","Extension Service & 4-H","Other");

?>