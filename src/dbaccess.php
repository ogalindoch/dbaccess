<?php
namespace euroglas\dbaccess;

use \PDO;

class dbaccess
{
    /**
     * CONSTRUCTOR
     * 
     * @param mixed $path Archivo de configuración a leer, para obtener los ConnectionString
     */
    function __construct( $path )
    {
        $this->ini = parse_ini_file($path,true);
    }

    public function connect( $connectionName, $schema=null )
    {
        $this->currentConnection = null; // Close any existing connection

        if( array_key_exists ( $connectionName , $this->ini ) )
        {
            // Si no nos pasaron un nombre de BD
            if( empty($schema) )
            {
                // Usamos el default definido en la configuración
                $schema = $this->ini[$connectionName]['schema'];
            }

            try
            {
                // Arma un DSN
                $dns = $this->ini[$connectionName]['driver']
                        . ':host=' . $this->ini[$connectionName]['server']
                        . ';dbname=' . $schema ;

                if (isset($this->ini[$connectionName]['port']))
                {
                    $dns = $dns . ';port=' . $this->ini[$connectionName]['port'];
                }

                // Solicita que los datos sean UTF8, de lo contrario, falla JSON
                $dns .= ';charset=utf8';

                $this->currentConnection = new \PDO( $dns,
                                $this->ini[$connectionName]['username'],
                                $this->ini[$connectionName]['password']);
                $this->currentConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            }
            catch (PDOException $e) {
                $this->lastError = $e->getMessage();
                return false;
            }
        }
        else
        {
            $this->lastError = "Connection name not defined: {$connectionName}";
            return false;
        }
        // return $dns;
        return $this->currentConnection;
    }

    public function queryPrepared($queryName)
    {
        //return $queryName;
        return( array_key_exists($queryName, $this->preparedQueries) );
    }

    public function rawQueryPrepared($queryName)
    {
        return($this->preparedQueries[$queryName]);
    }

    public function prepare($queryString,$queryName)
    {
        $this->preparedQueries[$queryName] = $this->currentConnection->prepare($queryString);
    }

    //
    // Realiza una consulta dada por $sql a la base de datos.
    //
    public function query( $sql )
    {
        try {
            return $this->currentConnection->query( $sql );
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();

            return false;
        }
    }

    //
    // Execute an SQL statement and return the number of affected rows
    //
    public function exec( $sql )
    {
        try {
            return $this->currentConnection->exec( $sql );
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();

            return false;
        }

    }

    public function execute_bind($queryName, $types,  $values=array())
    {
        // Carácter Descripción
        // i    la variable correspondiente es de tipo entero
        // d    la variable correspondiente es de tipo double
        // s    la variable correspondiente es de tipo string
        // b    la variable correspondiente es un blob y se envía en paquetes

        // la lista anterior es para el primer parametro en mysqli_stmt
        // PDOStatement no usa este formato
        // traducir solo i y s, float funciona con s
        // si el tipo no esta tomar s como default
        // Mysql text blob PDO::PARAM_LOB?
        
        $keys = array_keys($values);

        for ($i=0; $i<count($keys); $i++)
        {
            $t = PDO::PARAM_STR;

            if (isset($types[$i]))
            {
                if ($types[$i] == 'i')
                    $t = PDO::PARAM_INT;
                #else if ($types[$i] == 's')
                #   $t = PDO::PARAM_STR;
            }

            $this->preparedQueries[$queryName]->bindValue($keys[$i], $values[$keys[$i]], $t);
        }
        
        try
        {           
            $this->preparedQueries[$queryName]->execute();
            return $this->preparedQueries[$queryName];
        }
        catch(PDOException $e)
        {
            print_r($this->lastError = $e->getMessage());
            die();
            $this->lastError = $e->getMessage();

            return false;

            echo 'Error ejecutando query: ',  $e->getMessage(), "\n";
        }
    }

    public function execute($queryName, $values=array())
    {
        try
        {
            $this->preparedQueries[$queryName]->execute($values);
            //print($this->preparedQueries[$queryName]->queryString);
            return $this->preparedQueries[$queryName];
        }
        catch(PDOException $e)
        {
            print_r($this->lastError = $e->getMessage());
            die();
            $this->lastError = $e->getMessage();

            return false;

            echo 'Error ejecutando query: ',  $e->getMessage(), "\n";
        }
    }
    
    public function rowCount($queryName)
    {
        return( $this->preparedQueries[$queryName]->rowCount() );
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getCurrentConnection()
    {
        return $this->currentConnection;
    }

    private $ini = array();
    private $currentConnection = null;
    private $lastError = null;
    private $preparedQueries = array();

}
