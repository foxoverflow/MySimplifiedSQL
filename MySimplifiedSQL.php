<?php
/*
GitHub: https://github.com/foxoverflow/MySimplifiedSQL

Copyright (c) 2015 foxoverflow

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/
class MySimplifiedSQL
{
    public $mysqli;
    public $num_rows = 0;
    public $rowArray = array();
    public $testMode = 0;

    function __construct($db_user, $db_password, $db_host, $db_name, $testModeParameter = 0)
    {
        if($testModeParameter == 0)
        {
            $this->mysqli = @mysqli_connect($db_host, $db_user, $db_password, $db_name);
            if(!$this->mysqli) 
            {
                throw new Exception("MySimplifiedSQL: Error connecting to MySQL database, MySQLi connect error: " . mysqli_connect_error());
            }
            if (!$this->mysqli->set_charset("utf8")) 
            {
                throw new Exception("MySimplifiedSQL: Error setting charset to UTF-8, do not continue as sanitization will not work properly.");
            }
        }
        else 
        {
            $this->testMode = 1;
            echo "MySimplifiedSQL: Test mode is active, no queries will be executed.<br>";
        }
    }

    function __destruct()
    {
        if($this->testMode == 0) mysqli_close($this->mysqli);
    }

    public function testMode($testModeParameter)
    {
        $this->testMode = $testModeParameter;
    }

    public function sanitize($variable)
    {
        if($this->testMode == 0)
        {
            return mysqli_real_escape_string($this->mysqli, $variable);
        }
        else return $variable;
    }

    public function query($query, $selectQuery = 0)
    {
        if($selectQuery == 0 && strpos($query, "SELECT") !== false) $selectQuery = 1; 
        if($this->testMode == 0)
        {
            $queryResult = mysqli_query($this->mysqli, $query);
            if(!$queryResult)
            {
                throw new Exception("MySimplifiedSQL: Error executing your query, it was: {$query} - MySQL Error: " . mysqli_error($this->mysqli));
            }
            if($selectQuery == 1) 
            {
                $this->rowArray = array();
                while($row = $queryResult->fetch_assoc())
                {
                    $this->rowArray[] = $row;
                }
            }
            @$this->num_rows = $queryResult->num_rows;
        }
        else echo htmlentities($query) . "<br>";
    }

    public function insert($table, array $elements)
    {
        $queryStatement = "INSERT INTO {$table} (";
        $totalFieldCount = count($elements);
        $fieldCount = 0;
        foreach ($elements as $field => $value) 
        {
            $fieldCount++;
            $queryStatement .= $field;
            if($fieldCount < $totalFieldCount) $queryStatement .= ", "; else $queryStatement .= ") VALUES (";
        }
        $fieldCount = 0;
        foreach ($elements as $field => $value) 
        {
            $fieldCount++;
            $value = $this->sanitize($value);
            $queryStatement .= "'" . $value . "'";
            if($fieldCount < $totalFieldCount) $queryStatement .= ", "; else $queryStatement .= ")";
        }
        $this->query($queryStatement);
    }
    
    public function select($table, array $where, $condition = "AND", $extra = "") // In $extra you can use "ORDER BY" for example
    {
        if($condition != "AND" && $condition != "OR") throw new Exception("MySimplifiedSQL: Select condition is not \"AND\" nor \"OR\"");
        $queryStatement = "SELECT * FROM {$table} WHERE ";
        $totalWhereCount = count($where);
        $whereCount = 0;
        foreach($where as $whereField => $whereValue)
        {
            $whereCount++;
            $whereValue = $this->sanitize($whereValue);
            $queryStatement .= $whereField . "='" . $whereValue . "'";
            if($whereCount < $totalWhereCount) $queryStatement .= " " . $condition . " ";
        }
        $this->query($queryStatement . " {$extra}", 1);
    }

    public function delete($table, array $where, $condition = "AND")
    {
        if($condition != "AND" && $condition != "OR") throw new Exception("MySimplifiedSQL: Select condition is not \"AND\" nor \"OR\"");
        $queryStatement = "DELETE FROM {$table} WHERE ";
        $totalWhereCount = count($where);
        $whereCount = 0;
        foreach($where as $whereField => $whereValue)
        {
            $whereCount++;
            $whereValue = $this->sanitize($whereValue);
            $queryStatement .= $whereField . "='" . $whereValue . "'";
            if($whereCount < $totalWhereCount) $queryStatement .= " " . $condition . " ";
        }
        $this->query($queryStatement, 1);
    }

}

?>