<?php

class DynaDB {

	function __construct(){
        $this->db = new SQLite3("random.sqlite");
        //if(!$this->db) {
        //    echo $this->db->lastErrorMsg();
        //} else {
        //    echo "Opened database successfully\n";
        //} 
    }

    public $datam = array();

    //CONNECTION
    private $conex;
    //SELECT
    public $action, $table, $where, $orderby;
    //UPDATE
    public $update, $set, $to, $for;
    //INSERT
    public $insert, $into, $values;
    //Remove
    public $remove;
    //DEBUG + LOG
    public $finalsqlquery, $ID, $RecordHistory; 

    //THE SQLITE
    public $db;


  	public function Select($leselect = null){
        $this->ResetField(); // F10 to skip the reseting part in debugger
        if(!isset($leselect)){
            $this->action = "SELECT * FROM ";
        }
        else{
            $this->action = "SELECT $leselect FROM ";
        }
        return $this;
    }

    public function Distinct($field){
        $this->ResetField(); // F10 to skip the reseting part in debugger
        $this->action = "SELECT DISTINCT " . $field . " FROM ";
        return $this;
    }

    public function Table($thetable){
        $this->table = $thetable;
        return $this;
    }
    public function From($thetable){
        $this->Table($thetable);
        return $this;
    }
  
    public function OrderBy($theorder){
        $this->orderby = $theorder;
        return $this;
    }

    public function QueryOne(){
        $query =  $this->Query();
        return $query[0];
    }

    function Query(){
        $this->datam = array();
        $lesql = $this->action;
        $lesql .= $this->table;
        if($this->where != null){
            $lesql .= " WHERE " . $this->where;
        }
        if($this->orderby != null){
            $lesql .= (" ORDER BY ". $this->orderby);
        }
        $this->finalsqlquery = $lesql;
        $ret = $this->db->query($lesql);

        while($row = $ret->fetchArray(SQLITE3_ASSOC) ) {
            array_push($this->datam,(object)$row);
        }

        return $this->datam;
    }


    //INSERT
    public function Insert(){
        $this->ResetField(); // F10 to skip the reseting part in debugger
        $this->insert = "INSERT INTO ";
        return $this;
    }

    public function Into($table){
        $this->into = $table;
        return $this;
    }

    public function Values($theRequest){
        $this->values = $theRequest;
        return $this;
    }


    public function Delete(){
        $this->ResetField(); // F10 to skip the reseting part in debugger
        $this->delete = "DELETE FROM ";
        return $this;
    }

    public function Remove(){

        $lesql = $this->delete;
        $lesql .= $this->table;
        $lesql .= " WHERE " . $this->where;


        $lefield = $this->conex->query($lesql);

        $this->RecordHistory .= "<br>" . $_REQUEST['ID'] . " WAS DELETED ";

        //$updated_history = "Modified ". date("Y-m-d H:i:s") ." by ".$_SESSION['Username']."  ";
        $updated_history = "Modified ". date("Y-m-d H:i:s") ." by FOOFON  ";

        $updated_history .= $this->RecordHistory;
        $updated_history = str_replace("'", "\"", $updated_history);

        $log = new stdClass();
        $log->Record_ID = $_REQUEST['ID'];
        $log->Record_Table = $this->table;
        $log->Record_History = $updated_history;

        $this->Insert()->Into("lcars_history")->Values($log)->Create();
    }

    public function Create(){
        $lesql = $this->insert;
        $lesql .= $this->into;

        $thefields = "";
        $thevalues = "";

        foreach ($this->values as $key => $value){
            $value = $this->CleanInput($value);
            if(/*$key != "ID" && */$key != "id" && $key != "record_action"){
                $thefields .= $key .", ";
                $thevalues .= "'" .$value."', ";
            }
        }
        
        //Cleaning the ',' at the end
        $thefields = substr($thefields, 0, -2);
        $thevalues = substr($thevalues, 0, -2);

        $lesql .= "($thefields) VALUES ($thevalues)";
        if($this->where != null){
            $lesql .= " WHERE " . $this->where;
        }

        $ret = $this->db->exec($lesql);
        if(!$ret) {
           echo $db->lastErrorMsg();
        } else {
           //echo "Records created successfully\n";
        }
        $this->db->close();
     
        return $ret;
    }


    //UPDATE
    public function Update($table){
        $this->ResetField(); // F10 to skip the reseting part in debugger
        $this->table = $table; //forlog
        $this->update = "UPDATE " . $table;
        return $this;
    }

    public function Set($field){
        $this->set = " SET " . $field;
        return $this;
    }

    public function To($value){
        $value = $this->CleanInput($value);
        $this->to = " = '". $value . "'";
        return $this;
    }

    public function For($values){
        $this->for = $values;
        return $this;
    }


    public function Edit($Req){
        $Changed = new stdClass();

        $Original = unserialize($Req["Original"]);
        unset($Req["Original"]);

        $Modified = $Req;
 
        $this->ID = $Original->ID;
    
        //Check if the new propriety are changed by taking the Modified and comparing with the Original
            //+if the values are not the same this mean they are modified, so we save in changed
        foreach($Modified as $key => $value){
            $value = $this->CleanInput($value);
            if($value != $Original->$key){
                $Changed->$key = $value;
                $this->RecordHistory .= "<br> $key UPDATED ( FROM : ".$Original->$key." TO : $value ) ";
            };
        }
        $this->for = $Changed;

        return $this;
    }




    public function Go(){
        $lesql = $this->update;

        //Multiple
        if($this->for != null){

            $lesql .= " SET ";
            $thevalues = "";
    
            foreach ($this->for as $key => $value){
                $value = $this->CleanInput($value);
                if($key != "ID" && $key != "id" && $key != "record_action"){
                    $thevalues .= "$key='$value', ";
                }
            }
            $thevalues = substr($thevalues, 0, -2);

            $lesql .= "$thevalues";
        }
        //Single
        else{
            $lesql .= $this->set;
            $lesql .= $this->to;
        }

        if($this->where != null){
            $lesql .= " WHERE " . $this->where;
        }
        $lefield = $this->conex->query($lesql);
        $this->log_history();

    }

    //GLOBAL
    public function Where($thewhere){
        $this->where = $thewhere;
        return $this;
    }

    public function ResetField(){
        $this->action = null;
        $this->table = null;
        $this->where = null;
        $this->orderby = null;
        $this->update = null;
        $this->set = null;
        $this->to = null;
        $this->insert = null;
        $this->into = null;
        $this->values = null;
        $this->for = null;
        $this->ID = null;
        $this->RecordHistory = null;
        $this->db = new SQLite3("random.sqlite");
    }

    function insertquery($query){
        $lefield = $this->conex->query($query);
        return $this->datam;
    }


    function log_history(){
        //$updated_history = "Modified ". date("Y-m-d H:i:s") ." by ".$_SESSION['Username']."  ";
        $updated_history = "Modified ". date("Y-m-d H:i:s") ." by FOOFON  ";

        $updated_history .= $this->RecordHistory;
        $updated_history = str_replace("'", "\"", $updated_history);

        $log = new stdClass();
        $log->Record_ID = $this->ID;
        $log->Record_Table = $this->table;
        $log->Record_History = $updated_history;

        $query = $this->Insert()->Into("lcars_history")->Values($log)->Create();
    }
    //AFTER THE WHERE 
    function ActiveOnly(){
        if($this->where != null){
            $this->where .= " AND IsActive ='1'";
        }
        else{
            $this->where .= "IsActive ='1'";
        }
        return $this;
    }

    function PureQuery($sql){
        $this->finalsqlquery = $sql;
        $lefield = $this->conex->query($sql);
        while($row = $lefield->fetch_assoc()){
            array_push($this->datam,(object)$row);
        }
        return $this->datam;
    }

    public function GetSQLStr(){
        return $this->finalsqlquery;
    }

    public function Count(){
        return count($this->Query());
    }
    public function CleanInput($input){
        return preg_replace("/'/","`",$input);
    }

}


global $DynaDB;
$DynaDB = new DynaDB();

//global $DynaDBMoodle;
//$DynaDBMoodle = new DynaDB("192.168.36.31", "sysadmin", "ncc1701O!","moodle");

/*
// SETTINGS FOR PRODUCTION, NOT FOR DEVELOPEMENT,
// BE SURE TO UNCOMMENT ON LIVE MACHINE

$DynaDB = new DynaDB("192.168.36.15", "sysadmin", "ncc1701O!","cfsl");

global $DynaDBMoodle;
$DynaDBMoodle = new DynaDB("192.168.36.31", "sysadmin", "ncc1701O!","moodle");

global $DynaDBMoodleDev;
$DynaDBMoodleDev = new DynaDB("192.168.36.15", "sysadmin", "ncc1701O!","moodle_dev");
*/




/*

//COPY AND PASTE FOR EASY MACHINE 

/UPDATE

//SIMPLE
$DynaDB->Update()->Set()->To()->Where()->Go();

//EDIT
$DynaDB->Update()->Edit(1,2)->Where()->Go();


/SELECT 
$query = $DynaDB->Select()->Table()->Where()->Query();

/INSERT 
$query = $DynaDB->Insert()->Into()->Values()->Create();

/REMOVE
$query = $DynaDB->Delete()->From()->Where()->Remove();


//EXTRA 
Count       = End with ->Count() to get the number of element you get from your query;
QueryOne    = Query only 1 item with SELECT, so you dont need foreach
PureQuery   = Sent a literal string to query mysql ( Experimental and not recommended )
GetSQLStr   = View the string you dynamicaly built with DynaDB ( For Debugging and understanding DynaDB )
ActiveOnly  = Display only the activated on the query, DB field (IsActive)


/  Inheritence priority 
// DynaDB -> DynaFunc -> DynaForm -> DynaFormStatic -> DynaView

*/