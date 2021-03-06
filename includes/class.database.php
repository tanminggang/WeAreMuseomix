<?php
/*****************************************************************************************
** © 2013 POULAIN Nicolas – nico.public@ouvaton.org - http://tounoki.org **
** **
** Ce fichier est une partie du logiciel libre WeAreMuseomix, licencié **
** sous licence "CeCILL version 2". **
** La licence est décrite plus précisément dans le fichier : LICENSE.txt **
** **
** ATTENTION, CETTE LICENCE EST GRATUITE ET LE LOGICIEL EST **
** DISTRIBUÉ SANS GARANTIE D'AUCUNE SORTE **
** ** ** ** **
** This file is a part of the free software project We Are Museomix,
** licensed under the "CeCILL version 2". **
**The license is discribed more precisely in LICENSES.txt **
** **
**NOTICE : THIS LICENSE IS FREE OF CHARGE AND THE SOFTWARE IS DISTRIBUTED WITHOUT ANY **
** WARRANTIES OF ANY KIND **
*****************************************************************************************/

/**
 * abstract class, manage data and records
 * @abstract
 **/
abstract class database {
	/**
	 * ID
	 * @var int
	 */
	protected $ID ; // ID of record
	/**
	 * date of creation for record
	 * @var date
	 */
	protected $date_created ;
	/**
	 * date of last modification for record
	 * @var date
	 */
	protected $date_modified ;
	/**
	 * specific data of each record, $data[name_of_column] = value
	 * @var array
	 */
	protected $data ;
	/**
	 * list of columns of each table
	 * @var array
	 */
	static protected $LIST_DATA = array() ;
	/**
	 * name of considered table - STATIC
	 * @var string
	 */
	static protected $table = "" ;
	/**
	 * array of prepared query for PDO
	 * @var array
	 */
	private $query ; // array of query

	/**
	 * constructor - the constructor prepare the common querys too
	 * @param int
	 * @return bool
	 */
	function __construct($ID=NULL) {
		$this->ID = $ID ;
		foreach( STATIC::$LIST_DATA as $value) {
			if ( 1==1 ) $this->data[$value] = NULL ;
		}
		// prepare query for loadDataFromID
		$this->query['loadDataFromID'] = UPDO::getInstance()->prepare("SELECT * FROM ".STATIC::$table." WHERE ID = :ID") ;
		// prepare query for update
		foreach ( STATIC::$LIST_DATA as $value ) {
			$temp[] = "$value = :$value" ;
		}
		// ajouter date_modified
		$list = implode(',',$temp) ;
		$sql = "UPDATE ".STATIC::$table."
			SET $list WHERE ID = :ID" ;
		$this->query['update'] = UPDO::getInstance()->prepare($sql) ;
		return true ;
	}

	/**
	 * ressource could be a SQL ressource or a POST or GET array
	 * @param array
	 * @return bool
	 */
	public function setData($ressource) {
		if ( empty($ressource) ) return false ;
		foreach( $ressource as $key => $value ) {
			if ( !empty($key) && in_array($key,STATIC::$LIST_DATA) ) {
				$this->data[$key] = trim($value) ;
			}
		}
		return true ;
	}
	/**
	 * return the ID of the record, if it is already save
	 * @return int
	 */
	function getID() {
		return $this->ID ;
	}
	/**
	 * return a specific value of a column - the return value type depend of the choosed column
	 * @param string
	 * @return mixed
	 */
	function getData($field) {
		if ( empty($field) ) return false ;
		return $this->data[$field] ;
	}
	/**
	 * return a all value of a record
	 * @return array
	 */
	function getAllData() {
		return $this->data ;
	}
	/**
	 * load the data stored in database if ID is given
	 * @return bool
	 */
	function loadDataFromID() {
		if ( empty($this->ID) ) return false ;
		// "SELECT * FROM ".STATIC::$table." WHERE ID = :ID" ; // prepared in constructor
		$this->query['loadDataFromID']->execute(array(':ID'=>$this->ID)) ;
		while( $ligne = $this->query['loadDataFromID']->fetch(PDO::FETCH_ASSOC) ) {
			/*
			foreach ($ligne as $key => $value ) {
				if ( in_array($key,STATIC::$LIST_DATA) ) {
					$this->data[$key] = $value ;
				}
			}*/
			$this->setData($ligne) ;
		}
		return true ;
	}

	/**
	 * print directly all the data - often use for debugging or preparing script
	 * @return bool, ever true
	 */
	function printData($level=0,$before=NULL,$after=NULL) {
		echo $before ;
		echo "ID : ".$this->ID."<br/>" ;
		foreach( $this->data as $key => $value ) {
			echo "<strong>".$key." :</strong> ".$value."<br/>" ;
		}
		echo "$after\n" ;
		return true ;
	}

	/**
	 * save the datas a new record
	 * @return bool, true if success
	 */
	function add() {
		if ( !empty($this->data) && count($this->data) > 0 ) {
			//print_r($this->data) ;
			// STATIC::$LIST_DATA
			$list = implode(",",array_keys($this->data)) ;
			$list_prep = ':'.implode(",:",array_keys($this->data)) ;

			// ajout date_created automatic
			$sql = "INSERT
				INTO ".STATIC::$table."(".implode(',',STATIC::$LIST_DATA).")
				VALUES (".$list_prep.")" ;
			//echo $sql ;
			$query = UPDO::getInstance()->prepare($sql) ;
			if ( $query->execute($this->data) ) {
				$this->ID = UPDO::getInstance()->lastInsertId() ;
				return true ;
			}
			else return false ;
		}
		else return false ;
	}

	/**
	 * update a record if it already exists
	 * @return bool true if success
	 */
	function update() {
		if ( !empty($this->data) && count($this->data) > 0 ) {
			// query is prepared in constructor
			$this->query['update']->bindValue(':ID', $this->ID) ;
			foreach ( $this->data as $key => $val ) {
				if ( !empty($key) && in_array($key,STATIC::$LIST_DATA) )
					$this->query['update']->bindValue(':'.$key,$val) ;
			}
			if ( $this->query['update']->execute() ) {
				return true ;
			}
			else return false ;
		}
		else return false ;
	}

	function save() {
		if ( empty($this->ID) ) return $this->add() ;
		else return $this->update() ;
	}

	/**
	 * delete record in database
	 * @return bool, true if success
	 */
	function delete() {
		if ( empty( $this->ID ) ) return false ;
		// prepare the querys
		$sql[0] = "DELETE FROM ".STATIC::$table." WHERE ID = {$this->getID()}" ;
		// execute the querys
		$count[0] = UPDO::getInstance()->exec($sql[0]) ;
		if ( $count[0] > 0 )
			return true ;
		else return false ;
	}

	/**
	 * surcharge of serialize a record for private data
	 * @return string
	 */
	function serialize() {
		$arr['ID'] = $this->getID() ;
		$arr['data'] = $this->getAllData() ;
		return serialize($arr) ;
	}
	/**
	 * surcharge of unserialize for record
	 * @param string the unserialised data
	 * @return bool
	 */
	function unserialize($string) {
		$buffer = unserialize($string) ;
		$this->ID = ( empty($buffer['ID']) ) ? NULL : $buffer['ID'] ;
		$this->setData($buffer['data']) ;
		return true ;
	}

	/**
	 * destructor
	 */
	function __destruct() {
		$this->ID = 		NULL ;
		$this->date_created = 	NULL ;
		$this->date_modified = 	NULL ;
		$this->data = 		NULL ;
	}

}

/**
 * class for manage
 **/
class participation extends database {
	static protected $LIST_DATA = array('event_id','user_id') ;
	static protected $table = TABLE_PARTICIPATIONS ;
	
	function printData($level=0,$before=NULL,$after=NULL) {
		if ( $this->getData('event_id') == 1 )
			echo "<img style='margin-right:5px;' src=\"content/uploads/museo2011.png\" >" ;
		if ( $this->getData('event_id') == 2 )
			echo "<img style='margin-right:5px;' src=\"content/uploads/museo2012.png\" >" ;
		if ( $this->getData('event_id') == 3 )
			echo "<img style='margin-right:5px;' src=\"content/uploads/museo2013.png\" >" ;
		return true ;
	}	
	
}

/**
 * class for manage
 **/
class event extends database {
	static protected $LIST_DATA = array('event_name','event_year','event_localisation','event_comment') ;
	static protected $table = TABLE_PARTICIPATIONS ;
}
function getAllEvents() {
	$list = NULL ;
	$sql = "SELECT * FROM ".TABLE_EVENTS." ORDER BY ID ASC" ;
	$query = UPDO::getInstance()->prepare($sql) ;
	$query->execute() ;
	$i = 0 ;
	while( $ligne = $query->fetch(PDO::FETCH_ASSOC) ) {
		$list[$i] = new event($ligne['ID']) ;
		$list[$i]->setData($ligne) ;
		$i++ ;
	}
	return $list ;
}

?>