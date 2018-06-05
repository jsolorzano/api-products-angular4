<?php

// Incluimos el archivo que carga las clases del framework
require_once 'vendor/autoload.php';

// Instanciamos un nuevo objeto Slim
$app = new \Slim\Slim();

$db = new mysqli('localhost', 'root', '123456', 'curso_angular4');  // Creamos una conexión a nuestra base de datos

/* Configuración de cabeceras para permitir el acceso CORS a la API sin problemas.
 * Esto permitirá hacer peticiones ajax desde el front-end sin que haya bloqueos por parte del servidor.
 */
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST, OPTIONS, PUT, DELETE");
$method = $_SERVER['REQUEST_METHOD'];
if($method == "OPTIONS") {
    die();
}

// Con el método get creamos una url por get
$app->get("/", function() use($app, $db){  // Con use() permitimos que la función de callback reciba como parámetro variables externas
	echo "Hola mundo desde Slim PHP.";
	var_dump($db);  // Mostramos los datos de la conexión a la base de datos
});

// Con el método get creamos una url por get
$app->get("/pruebas", function() use($app){  // Con use() permitimos que la función de callback reciba como parámetro variables externas
	echo "Probando el framework para crear una api rest.";
});

// Método para listar todos los productos
$app->get("/productos", function() use($app, $db){
	
	$sql = "SELECT * FROM productos ORDER BY id DESC;";
	
	$query = $db->query($sql);
	
	//~ var_dump($query->fetch_assoc());
	
	$productos = array();
	
	while($producto = $query->fetch_assoc()){
		$productos[] = $producto;
	}
	
	$result = array(
		'status' => 'success',
		'code' => 200,
		'data' => $productos
	);
	
	echo json_encode($result);
	
});

// Método para devolver un producto específico
$app->get("/producto/:id", function($id) use($app, $db){
	
	$sql = "SELECT * FROM productos WHERE id = ".$id;
	
	$query = $db->query($sql);
	
	//~ var_dump($query->fetch_assoc());
	
	$result = array(
		'status' => 'error',
		'code' => 404,
		'data' => 'Producto no disponible'
	);
	
	if($query->num_rows == 1){
		$producto = $query->fetch_assoc();
		
		$result = array(
			'status' => 'success',
			'code' => 200,
			'data' => $producto
		);
	}
	
	echo json_encode($result);
	
});

// Método para eliminar un producto
$app->get("/producto/delete/:id", function($id) use($app, $db){
	
	$sql = "DELETE FROM productos WHERE id = ".$id;
	
	$query = $db->query($sql);
	
	if($query){
		$result = array(
			'status' => 'success',
			'code' => 200,
			'message' => 'El producto se ha eliminado correctamente'
		);
	}else{
		$result = array(
			'status' => 'error',
			'code' => 404,
			'message' => 'El producto NO se ha eliminado'
		);
	}
	
	echo json_encode($result);
	
});

// Método para actualizar un producto específico
$app->post("/producto/update/:id", function($id) use($app, $db){
	
	$json = $app->request->post('json');
	
	$data = json_decode($json, true);
	
	$sql = "UPDATE productos SET ".
			"nombre = '{$data["nombre"]}', ".
			"descripcion = '{$data["descripcion"]}', ";
	
	if(isset($data['imagen'])){
		$sql .= "imagen = '{$data["imagen"]}', ";
	}
				
	$sql .= "precio = '{$data["precio"]}' ";
	$sql .= "WHERE id = {$id}";
			
	$query = $db->query($sql);
	
	if($query){
		$result = array(
			'status' => 'success',
			'code' => 200,
			'message' => 'El producto fue actualizado correctamente'
		);
	}else{
		$result = array(
			'status' => 'error',
			'code' => 404,
			'message' => 'El producto NO se ha actualizado'
		);
	}
	
	echo json_encode($result);
	
});

// Método para subir una imagen al servidor
$app->post("/upload-file", function() use($app, $db){
	
	$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'El archivo NO ha podido subirse'
	);
	
	//~ print_r($_FILES);
	
	if(isset($_FILES['uploads'])){
		
		// Instanciamos la clase de la librería de subida de archivos
		$piramideUploader = new PiramideUploader();
		
		/* Ejecutamos el método upload() y le pasamos el prefijo que tendrá la imagen cargada, el nombre del campo que viene por el request,
		 * el nombre del directorio donde se guardará la imagen y un arreglo de tipos de archivo permitidos.
		 * */
		$upload = $piramideUploader->upload('image', "uploads", "uploads", array('image/jpeg', 'image/png', 'image/gif'));
		
		// Obtenemos los datos generales del archivo subido con el método getInfoFile()
		$file = $piramideUploader->getInfoFile();
		
		// Obtenemos el nombre completo del archivo
		$file_name = $file['complete_name'];
		
		if(isset($upload) && $upload['uploaded'] == false){
			$result = array(
				'status' => 'error',
				'code' => 404,
				'message' => 'El archivo NO ha podido subirse'
			);
		}else{
			$result = array(
				'status' => 'success',
				'code' => 200,
				'message' => 'El archivo se ha subido',
				'filename' => $file_name
			);
		}
		
	}
	
	echo json_encode($result);
	
});

// Método de guardado de un producto con post
$app->post("/productos", function() use($app, $db){
	
	// Con request->post() capturamos los datos vía post
	$json = $app->request->post('json');
	// Transformamos los datos recibidos de json a array()
	$data = json_decode($json, true);
	
	// Validamos los distintos campos
	if(!isset($data['nombre'])){
		$data['nombre']	= null;
	}
	
	if(!isset($data['descripcion'])){
		$data['description'] = null;
	}
	
	if(!isset($data['precio'])){
		$data['precio']	= null;
	}
	
	if(!isset($data['imagen'])){
		$data['imagen']	= null;
	}
	
	// Generamos una consulta sql con el nombre y el precio
	$query = "INSERT INTO productos VALUES(NULL,'".$data['nombre']."','".$data['descripcion']."','".$data['precio']."','".$data['imagen']."');";
	
	// Ejecutamos la consulta sql con el método query()
	$insert = $db->query($query);
	
	$result = array(
		'status' => 'error',
		'code' => 404,
		'message' => 'Producto NO se ha creado'
	);
	
	if($insert){
		$result = array(
			'status' => 'success',
			'code' => 200,
			'message' => 'Producto creado correctamente'
		);
	}
	
	echo json_encode($result);
	
});

// Con el método run() del objeto hacemos que todos los métodos que se han llamado de Slim funcionen
$app->run();

?>
