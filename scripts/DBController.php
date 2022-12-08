<?php

class DBController
{
 
    private $host = "localhost";
    private $user = "root";
    private $password = "ninetynine99";
    private $database = "cliffs";
    private $conn;
 
    function __construct()
    {
        $this->conn = $this->connectDB();
    }
 
    function connectDB()
    {
        $conn = mysqli_connect($this->host, $this->user, $this->password, $this->database);
        return $conn;
    }
 
    function runQuery($query)
    {
        $result = mysqli_query($this->conn, $query);
        while ($row = mysqli_fetch_array($result)) {
            $resultset[] = $row;
        }
        if (! empty($resultset))
            return $resultset;
    }
	
	function runQueryBasic($query) {
		$result = mysqli_query($this->conn, $query);
		$resultArray = mysqli_fetch_array($result);
		return $resultArray;
	}
	
	function runQueryNoReturn($query) {
		mysqli_query($this->conn, $query);
	}
		
		
    function insertQuery($query)
    {
        mysqli_query($this->conn, $query);
        $insert_id = mysqli_insert_id($this->conn);
        return $insert_id;
    }
	
	function contQuery($query) {
		$result = mysqli_query($this->conn, $query);
		return mysqli_num_rows($result);
	}
 
    function getIds($query)
    {
        $result = mysqli_query($this->conn, $query);
        while ($row = mysqli_fetch_array($result)) {
            $resultset[] = $row[0];
        }
        if (! empty($resultset))
            return $resultset;
    }
}
?>