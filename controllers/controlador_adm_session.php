<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\contrato\controllers;

use config\generales;
use gamboamartin\administrador\models\adm_session;
use gamboamartin\errores\errores;
use gamboamartin\system\links_menu;
use JsonException;
use stdClass;

class controlador_adm_session extends \gamboamartin\controllers\controlador_adm_session {
    public bool $existe_msj = false;
    public string $include_menu = '';
    public string $mensaje_html = '';

    public string $options_folio = '';

    public function alta_rapida(bool $header = true, bool $ws = false)
    {

        $session_em3 = '';
        if(isset($_GET['session_em3'])){
            $session_em3 = $_GET['session_em3'];
        }
        $end_point = (new generales())->api_em3;
        $end_point = $end_point."?method=get_session&session_id=".$session_em3;


        $params = array();
        $params['numero_empresa'] = 1;
        $result = $this->make_post_request($end_point,$params);
        if(isset($result->error)){
            if((int)$result->error === 1){
                $error = (new errores())->error('Error al obtener datos de session',$result);
                print_r($error);
                die('ERROR');
            }
        }


        print_r($_POST);
        $end_point = (new generales())->api_em3;
        $end_point = $end_point."?method=alta_rapida_bd&debug=1&session_id=".$session_em3;

        $params = array();
        $params['numero_empresa'] = 1;
        $params['plaza_id'] = $result[0]->plaza_id;
        $params['producto_id'] = 22;
        $params['ohem_id'] = $result[0]->ohem_id;;
        $params['U_CondPago'] = 'S';
        $params['U_FeNac'] = $_POST['fecha_nacimiento'];
        $params['nombre'] = $_POST['nombre'];
        $params['apellido_p'] = $_POST['apellido_paterno'];
        $params['apellido_m'] = $_POST['apellido_materno'];
        $params['CardName'] = $params['nombre'].' '.$params['apellido_p'].' '.$params['apellido_m'];
        $params['CardFName'] = $params['nombre'].' '.$params['apellido_p'].' '.$params['apellido_m'];

        if((int)$result[0]->plaza_id === 29){
            $params['producto_id'] = 23;
        }

        $rfc = $this->generarRFC($_POST['nombre'],$_POST['apellido_paterno'],$_POST['apellido_materno'],
            $_POST['fecha_nacimiento']);

        $params['rfc'] = $rfc;

        $alta = $this->make_post_request($end_point, $params,true);

        print_r($alta);exit;


        print_r($_POST);
        print_r($_FILES);exit;




        exit;



        return $result;
    }

    private function generarRFC($nombre, $apellidoPaterno, $apellidoMaterno, $fechaNacimiento)
    {
        // --- 1. Preparación de los datos ---

        $nombre = strtoupper($this->eliminarAcentos($nombre));
        $apellidoPaterno = strtoupper($this->eliminarAcentos($apellidoPaterno));
        $apellidoMaterno = strtoupper($this->eliminarAcentos($apellidoMaterno));
        //$fechaNacimiento = str_replace("-", "", $fechaNacimiento); //Quitar diagonales si existen

        if (empty($apellidoMaterno)) {
            $apellidoMaterno = "X";
        }


        // --- 2.  Obtener las partes del RFC ---

        // Primera letra y primera vocal interna del apellido paterno
        $rfc = substr($apellidoPaterno, 0, 1);
        $rfc .= $this->primeraVocalInterna($apellidoPaterno);

        // Primera letra del apellido materno (o X si no hay)
        $rfc .= substr($apellidoMaterno, 0, 1);

        // Primera letra del nombre
        $rfc .= substr($nombre, 0, 1);

        $fecha_array = explode("-", $fechaNacimiento);  //Formato AAAA-MM-DD


        if (strlen($fecha_array[0]) == 4) //Año esta en la primera posicion
            $rfc .= substr($fecha_array[0], 2, 2) . $fecha_array[1] . $fecha_array[2];
        else //Asumir que la fecha esta en formato DD/MM/YYYY
            $rfc .= substr($fecha_array[2], 2, 2) . $fecha_array[1] . $fecha_array[0];


        // --- 3. Manejo de palabras inconvenientes ---

        $palabrasInconvenientes = array("BUEI", "BUEY", "CACA", "CACO", "CAGA", "CAGO", "CAKA", "CAKO", "COGE", "COJA", "COJE", "COJI", "COJO", "CULO",
            "FETO", "GUEY", "JOTO", "KACA", "KACO", "KAGA", "KAGO", "KAKA", "KAKO", "KOGE", "KOJO", "KAKA",
            "KULO", "MAME", "MAMO", "MEAR", "MEON", "MION", "MOCO", "MULA", "PEDA", "PEDO", "PENE", "PITO",
            "QULO", "RATA", "RUIN");
        if (in_array($rfc, $palabrasInconvenientes)) {
            $rfc = substr_replace($rfc, "X", 3, 1); // Reemplaza la última letra
        }

        // --- 4. Cálculo de la Homoclave ---

        $nombreCompleto = $apellidoPaterno . " " . $apellidoMaterno . " " . $nombre;
        $valorNumerico = $this->calcularValorNumerico($nombreCompleto);
        $homoclave = $this->calcularHomoclave($valorNumerico);

        $rfc .= $homoclave;


        // --- 5. Cálculo del Dígito Verificador (SIMULADO) ---
        // NOTA:  Este es un cálculo SIMULADO del dígito verificador.
        // El algoritmo real requiere una tabla proporcionada por el SAT.

        $rfcSinVerificador = $rfc;  //RFC + homoclave (sin digito verificador)
        $suma = 0;
        $factor = "0123456789ABCDEFGHIJKLMN&OPQRSTUVWXYZ* "; // IMPORTANTE: El espacio es parte de los factores
        for ($i = 0; $i < strlen($rfcSinVerificador); $i++) {
            $char = $rfcSinVerificador[$i];
            $pos = strpos($factor, $char);
            if ($pos === false) {
                return "Caracter invalido en RFC para calcular digito";
            }
            $suma += $pos * (14 - $i); // Ponderación (de 13 a 1, en reversa)
        }

        $modulo = $suma % 11;
        $digitoVerificador = ($modulo == 0) ? "0" : (string)(11 - $modulo);
        if ($digitoVerificador == "10") $digitoVerificador = "A";
        $rfc .= $digitoVerificador;

        return $rfc;
    }

// --- Funciones auxiliares ---

    private function primeraVocalInterna($palabra)
    {
        $palabra = substr($palabra, 1); // Quita la primera letra
        $vocales = "AEIOU";
        for ($i = 0; $i < strlen($palabra); $i++) {
            if (strpos($vocales, $palabra[$i]) !== false) {
                return $palabra[$i];
            }
        }
        return "X";
    }

    private function eliminarAcentos($cadena)
    {
        $cadena = str_replace(
            array('Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª'),
            array('A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A'),
            $cadena
        );
        $cadena = str_replace(
            array('É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê'),
            array('E', 'E', 'E', 'E', 'E', 'E', 'E', 'E'),
            $cadena
        );
        $cadena = str_replace(
            array('Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î'),
            array('I', 'I', 'I', 'I', 'I', 'I', 'I', 'I'),
            $cadena
        );
        $cadena = str_replace(
            array('Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô'),
            array('O', 'O', 'O', 'O', 'O', 'O', 'O', 'O'),
            $cadena
        );
        $cadena = str_replace(
            array('Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û'),
            array('U', 'U', 'U', 'U', 'U', 'U', 'U', 'U'),
            $cadena
        );
        return $cadena;
    }

    private function calcularValorNumerico($nombreCompleto)
    {
        $tablaValores = array(
            " " => "00", "0" => "00", "1" => "01", "2" => "02", "3" => "03", "4" => "04",
            "5" => "05", "6" => "06", "7" => "07", "8" => "08", "9" => "09",
            "&" => "10", "A" => "11", "B" => "12", "C" => "13", "D" => "14", "E" => "15",
            "F" => "16", "G" => "17", "H" => "18", "I" => "19", "J" => "21", "K" => "22",
            "L" => "23", "M" => "24", "N" => "25", "O" => "26", "P" => "27", "Q" => "28",
            "R" => "29", "S" => "32", "T" => "33", "U" => "34", "V" => "35", "W" => "36",
            "X" => "37", "Y" => "38", "Z" => "39", "Ñ" => "25"  //La Ñ se considera igual que la N
        );

        $valorNumerico = "";
        $longitud = strlen($nombreCompleto);

        for ($i = 0; $i < $longitud; $i++) {
            $char = $nombreCompleto[$i];

            //Si es la Ñ la sustituimos a mano
            if ($char == "Ñ") {
                $valor = $tablaValores["Ñ"];
            } //Si es algun caracter que no esta, lo marcamos como 0 y continuamos.
            elseif (!isset($tablaValores[$char])) {
                $valor = "00";
            } //Si existe, realizamos la sustitucion normalmente.
            else {
                $valor = $tablaValores[$char];
            }


            $valorNumerico .= $valor;

            // Suma de pares de caracteres
            if ($i < $longitud - 1) {
                $charSig = $nombreCompleto[$i + 1];
                $valorActual = intval($valor);

                if ($charSig == "Ñ")
                    $valorSiguiente = intval($tablaValores["Ñ"]);
                elseif (!isset($tablaValores[$charSig]))
                    $valorSiguiente = 0;
                else
                    $valorSiguiente = intval($tablaValores[$charSig]);

                $sumaParcial = $valorActual + $valorSiguiente;
                $valorNumerico .= $sumaParcial;

            }

        }

        return $valorNumerico;

    }

    private function calcularHomoclave($valorNumerico)
    {
        // 1. Dividir el valor numérico en grupos de dos dígitos
        $grupos = str_split($valorNumerico, 2);

        //2.  Convertir a entero cada grupo y realizar las divisiones y módulo
        $cocientes = [];
        $residuos = [];

        for ($i = 0; $i < count($grupos) - 1; $i++) { //No necesitamos procesar el ultimo par, solo hasta n-1
            $dividendo = intval($grupos[$i] . $grupos[$i + 1]);
            $divisor = intval($grupos[count($grupos) - 1]); //Ultimo par

            $cocientes[] = intdiv($dividendo, $divisor);
            $residuos[] = $dividendo % $divisor;
        }

        // 3.  Sumar los residuos multiplicados por 10
        $sumaResiduos = 0;
        foreach ($residuos as $residuo) {
            $sumaResiduos += $residuo * 10;
        }

        // 4.  Dividir la suma entre 34 y obtener cociente y residuo.
        $cocienteFinal = intdiv($sumaResiduos, 34);
        $residuoFinal = $sumaResiduos % 34;

        //var_dump($cocienteFinal);
        //var_dump($residuoFinal);

        // 5.  Obtener los caracteres de la homoclave de la tabla.

        $tablaHomoclave = "0123456789ABCDEFGHIJKLMNPQRSTUVWXYZ"; //Sin la O
        $caracter1 = $tablaHomoclave[$cocienteFinal];
        $caracter2 = $tablaHomoclave[$residuoFinal];

        //5.1 El tercer caracter

        //Obtener los ultimos 3 digitos del valor numerico
        $ultimos_tres = substr($valorNumerico, -3);
        $cociente_hc3 = intdiv(intval($ultimos_tres), 17);
        $residuo_hc3 = intval($ultimos_tres) % 17;

        $tablaHomoclave3 = "123456789ABCDEFGHIJKLMNPQRSTUVWXYZ"; //Tercer caracter de la homoclave
        $caracter3 = $tablaHomoclave3[$residuo_hc3];


        return $caracter1 . $caracter2 . $caracter3;
    }


    /**
     * Funcion de controlador donde se ejecutaran siempre que haya un acceso denegado
     * @param bool $header Si header es true cualquier error se mostrara en el html y cortara la ejecucion del sistema
     *              En false retornara un array si hay error y un string con formato html
     * @param bool $ws Si ws es true retornara el resultado en formato de json
     * @return array vacio siempre
     */
    public function denegado(bool $header, bool $ws = false): array
    {

        return array();

    }


    public function inicio(bool $aplica_template = false, bool $header = true, bool $ws = false): string|array
    {

        $session_em3 = '';
        if(isset($_GET['session_em3'])){
            $session_em3 = $_GET['session_em3'];
        }
        $end_point = (new generales())->api_em3;
        $end_point = $end_point."?method=get_session&session_id=".$session_em3;


        $params = array();
        $params['numero_empresa'] = 1;
        $result = $this->make_post_request($end_point,$params);
        if(isset($result->error)){
            if((int)$result->error === 1){
                $error = (new errores())->error('Error al obtener datos de session',$result);
                print_r($error);
                die('ERROR');
            }
        }

        //print_r($result);

        $template =  parent::inicio($aplica_template, false); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje:  'Error al generar template',data: $template, header: $header, ws: $ws);
        }


        $this->include_menu = (new generales())->path_base;
        $this->include_menu .= 'templates/inicio.php';

        $end_point = (new generales())->api_em3;
        $end_point = $end_point."?method=get_folios";

        $params = array();
        $params['numero_empresa'] = 1;
        $params['codPlaza'] = $result[0]->plaza_codigo_sap;
        //$params['codPlaza'] = 'P007';
        $params['empleado_codigo'] = $result[0]->ohem_empID;
        //$params['empleado_codigo'] = '25184';
        $folios = $this->make_post_request($end_point, $params);

       // print_r($folios);;exit;

        if(isset($folios->error)){
            if((int)$folios->error === 1){
                $error = (new errores())->error('Error al obtener folios',$folios);
                print_r($error);
                die('ERROR');
            }
        }

        $options_folio = '';
        foreach ($folios as $folio){
            $folio = (object)$folio;
            $options_folio.="<option value='$folio->folio_con_fila_id'>$folio->folio_con_U_Serie $folio->folio_con_fila_U_Folio $folio->folio_con_fila_U_Asistente</option>";

        }

        $this->options_folio = $options_folio;

        $params_link = array('session_em3'=>$session_em3);

        $link_alta_rapida = (new links_menu($this->link,-1))->link_sin_id('alta_rapida',
            $this->link,$this->seccion,$params_link);
        if(errores::$error){
            return $this->retorno_error(mensaje:  'Error al generar $link_alta_rapida',data: $link_alta_rapida, header: $header, ws: $ws);
        }


        $this->link_alta_bd = $link_alta_rapida;




        return $template;
    }


    final public function loguea(bool $header, bool $ws = false, string $accion_header = 'login',
                                 string $seccion_header = 'adm_session'): array|stdClass
    {

        $data_original = $_POST;
        $_POST['user'] = (new generales())->adm_usuario_user_init;
        $_POST['password'] = (new generales())->adm_usuario_password_init;
        $loguea = parent::loguea(header: false); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje:  'Error al loguear',data: $loguea, header: $header, ws: $ws);
        }

        $end_point = (new generales())->api_em3;
        $end_point = $end_point."?method=login";

        $params = array();
        $params['numero_empresa'] = 1;
        $params['username'] = $data_original['user'];
        $params['password'] = $data_original['password'];
        $result = $this->make_post_request($end_point, $params);

        if(isset($result->error)){
            if((int)$result->error === 1){
                $error = (new errores())->error('Error al loguearse en em3',$result);
                print_r($error);
                die('ERROR');
            }
        }

        if($header) {
            header("Location: ./index.php?seccion=adm_session&accion=inicio&session_em3=$result->session_id&mensaje=Bienvenido&tipo_mensaje=exito&session_id=" . (new generales())->session_id);
            exit;
        }

        return $result;
    }


    final function make_post_request(string $url, array $data, bool $debug = false):array|stdClass {
        $ch = curl_init($url); // Initialize cURL session

        // Convert data to JSON (recommended for APIs)
        $jsonData = json_encode($data);

        // Set cURL options
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, // Return response as string
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json', // Indicate JSON data
                'Content-Length: ' . strlen($jsonData) // Important for JSON
            ],
            CURLOPT_POST => true, // Enable POST request
            CURLOPT_POSTFIELDS => $jsonData, // Set POST data
            CURLOPT_SSL_VERIFYPEER => true,  // Verify SSL certificate (important for security)
            CURLOPT_SSL_VERIFYHOST => 2,   // Verify SSL hostname (important for security)
            CURLOPT_TIMEOUT => 30         // Set timeout (in seconds)
        ]);

        // Execute the request
        $response = curl_exec($ch);

        if($debug){
            print_r($response);
        }

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['error' => 1,'mensaje'=>'Error al ejecutar api '.$url.$error]; // Return error information
        }

        // Close cURL session
        curl_close($ch);

        // Decode JSON response (if applicable)
        // true for associative array

        // Return the response data

        return json_decode($response);
    }


    public function login(bool $header = true, bool $ws = false): stdClass|array
    {
        $login = parent::login($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje:  'Error al generar template',data: $login, header: $header, ws: $ws);
        }

        $this->mensaje_html = '';
        if(isset($_GET['mensaje']) && $_GET['mensaje'] !==''){
            $mensaje = trim($_GET['mensaje']);
            if($mensaje !== ''){
                $this->mensaje_html = $mensaje;
                $this->existe_msj = true;
            }
        }

        $this->include_menu .= 'templates/login.php';

        return $login;

    }



}
