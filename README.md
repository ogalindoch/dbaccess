# DbAccess
## Parte del servidor REST para EUROGLAS
Acceso a la BD, para uso de otros módulos del servidor REST de EUROGLAS   

No es un modulo propiamente dicho, pues no implementa la interfaz `restModuleInterface`, y por lo tanto, no expone ninguna URL.
Pero, ofrece a los otros modulos una forma sencilla de interactuar con la base de datos.

### Archivos

    eurorest
    ├───src
    │   └───dbaccess.php
    ├───.gitignore
    ├───composer.json
    ├───index.php
    ├───iniciaServidorDePruebas.php
    ├───servidor.ini
    ├───LICENSE
    └───README.md

### Directorio `src`

Contiene el archivo que implementa las herramientas del modulo.

```C#
class authkey extends \euroglas\eurorest\auth
```

| Archivo  | Descripcion   |
|---|---|
| .gitIgnore | blah |
| composer.json| Manejo de requerimientos |
| index.php | Implementacion del servidor de pruebas
| servidor.ini | Configuracion del servidor | 
| iniciaServidorDePruebas.bat | Script para arrancar el servidor usando el servidor interno de PHP |
| LICENSE | Licencia de uso de este paquete |
| README .md | éste archivo |

## Configuración

### servidor.ini

| Llave  | Explicación   |
|---|---|
| ServerName="" | Nombre del servidor | 
| ModoDebug = 1 | Habilita el modo de desarrollo | 
| [Modulos] <br> authkey=1 | Habilita el modulo authkey queda registrado como proveedor de Auth
| [dbaccess]<br>config = dbconfig.ini | Configura el archivo con los detalles de conexión a la BD

### dbconfig.ini
Cada conexión se define en su propio grupo, y el archivo puede contener tantos grupos como sea necesario, para conectarse a diferentes BDs. Otra razon, es por ejemplo, definir una para operaciones de Solo Lectura y otro, para operaciones de escritura, asi se hace más explicito (y seguro) los permisos que se estan usando en cada caso.

```ini
[ExampleDB]
driver = mysql
server = "127.0.0.1"
schema = "myschema"
username = "DBUSER"
password = "inseguro"
port = 3306
```
<dl>
  <dt>[ExampleDB]</dt>
  <dd>Nombre de la conexión, es como la vamos a identificar en el código.</dd>

  <dt>driver</dt>
  <dd>Driver para usar, normalmente <em>mysql</em> o <em>mysqli</em> </dd>

  <dt>schema</dt>
  <dd>Nombre de la BD dentro del servidor</dd>

  <dt>username + password</dt>
  <dd>Credenciales para la conexión. Asegurate que tenga los privilegios necesarios.</dd>

  <dt>port</dt>
  <dd>Puerto de red a usar para la conexión. Normalmente <code>3306</code> para <em>MySql</em>.</dd>

</dl>

## Ejemplos

### Conectar a la BD

```PHP
// Inicializa la clase, indicando el archivo de configuracion a cargar
$db = new new \euroglas\dbaccess\dbaccess("dbconfig.ini");

// Abre la conexion a la base de datos 
if( $this->dbRing->connect('ExampleDB') === false ) // Ojo, TRIPLE signo de igual
{
    // No se pudo abrir la conexión, imprime el problema
    print($this->dbRing->getLastError());
}
```

### Query, usando query preparado
Los queries preparados hacen que sea más rapido el servidor, cuando es probable que se tenga que repetir, pues se ahorra el parseado y analisis del texto, dejando solo el reemplazo de los valores a la hora de ejecutar cada llamada.

```PHP
// Verifica si el query ya esta guardado, 
// el nombre es arbitrario, solo para diferenciarlo de otros queries
if( ! $db->queryPrepared('UnQuery') )
{
    // Prepara un query y le asigna un nombre
    $db->prepare("SELECT * FROM MiTabla WHERE Llave = :UnaLlave", 'UnQuery');
}

// Ejecuta el query, pasando el parametro
$sth = $db->execute('UnQuery',array(':UnaLlave'=>"123"));

// Obten los resultados, como un arreglo asociativo
$registros = $sth->fetchAll(\PDO::FETCH_ASSOC);
```

## Para más ejemplos, consulta los modulos que listan **euroglas/dbaccess** como requisito.