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
use finfo;
use gamboamartin\administrador\models\adm_session;
use gamboamartin\contrato\models\cont_prospecto;
use gamboamartin\contrato\src\rfc;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\documento\models\doc_tipo_documento;
use gamboamartin\errores\errores;
use gamboamartin\system\links_menu;
use JsonException;
use stdClass;

class controlador_adm_session extends \gamboamartin\controllers\controlador_adm_session {
    public bool $existe_msj = false;
    public string $include_menu = '';
    public string $mensaje_html = '';

    public string $options_folio = '';

    public string $token_html = '';
    public string $mensaje_cc = '';

    public function alta_rapida(bool $header = true, bool $ws = false)
    {

        if(!isset($_POST['latitud'])){
            $error = (new errores())->error('Error por favor habilite la Geolocalizacion',$_POST);
            print_r($error);
            die('ERROR');
        }
        if(!isset($_POST['longitud'])){
            $error = (new errores())->error('Error por favor habilite la Geolocalizacion',$_POST);
            print_r($error);
            die('ERROR');
        }

        if($_POST['latitud'] === ''){
            $error = (new errores())->error('Error por favor habilite la Geolocalizacion',$_POST);
            print_r($error);
            die('ERROR');
        }

        if($_POST['longitud'] === ''){
            $error = (new errores())->error('Error por favor habilite la Geolocalizacion',$_POST);
            print_r($error);
            die('ERROR');
        }

        $session_em3 = '';
        if(isset($_GET['session_em3'])){
            $session_em3 = $_GET['session_em3'];
        }
        $end_point = (new generales())->api_em3;
        $end_point = $end_point."?method=get_session&session_id=".$session_em3;


        $params = array();
        $params['numero_empresa'] = 1;
        $session = $this->make_post_request($end_point,$params);
        if(isset($session->error)){
            if((int)$session->error === 1){
                $error = (new errores())->error('Error al obtener datos de session',$session);
                print_r($error);
                die('ERROR');
            }
        }


        $end_point = (new generales())->api_em3;
        $end_point = $end_point."?method=alta_rapida_bd&session_id=".$session_em3;

        $params = array();
        $params['numero_empresa'] = 1;
        $params['plaza_id'] = $session[0]->plaza_id;
        $params['producto_id'] = 22;
        $params['ohem_id'] = $session[0]->ohem_id;;
        $params['empleado_id'] = $session[0]->empleado_id;;
        $params['U_CondPago'] = 'S';
        $params['U_FeNac'] = $_POST['fecha_nacimiento'];
        $params['nombre'] = $_POST['nombre'];
        $params['apellido_p'] = $_POST['apellido_paterno'];
        $params['apellido_m'] = $_POST['apellido_materno'];
        $params['CardName'] = $params['nombre'].' '.$params['apellido_p'].' '.$params['apellido_m'];
        $params['CardFName'] = $params['nombre'].' '.$params['apellido_p'].' '.$params['apellido_m'];
        $params['SlpCode'] = $session[0]->ohem_salesPerson;
        $params['IndustryC'] = -1;
        $params['Cellular'] = $_POST['celular'];
        $params['CmpPrivate'] = 1;
        $params['GroupNum'] = -1;
        $params['periodicidad_pago_id'] = 2;
        $params['folio_con_fila_id'] = $_POST['folio'];
        $params['Phone1'] = $_POST['telefono_1'];

        if((int)$session[0]->plaza_id === 29){
            $params['producto_id'] = 23;
        }

        $rfc = (new rfc())->calcular_rfc($_POST['nombre'],$_POST['apellido_paterno'],$_POST['apellido_materno'],
            $_POST['fecha_nacimiento']);

        $params['rfc'] = $rfc;
        $params['LicTradNum'] = $rfc;
        $params['U_Beneficiario'] = $params['CardFName'];
        //print_r($params);exit;


        $alta = $this->make_post_request($end_point, $params,true);
        if(isset($alta->error)){
            if((int)$alta->error === 1){
                $error = (new errores())->error('Error al insertar contrato',$alta);
                print_r($error);
                die('ERROR');
            }
        }

        if(isset($alta->envia_call_center)) {
            if ($header) {
                header("Location: ./index.php?seccion=adm_session&envia_call_center=1&accion=inicio&session_em3=$session_em3&session_id=" . (new generales())->session_id);
                exit;
            }
        }


        $cont_prospecto_alta = array();
        if(isset($_FILES['fachada'])) {
            $doc_documento_modelo = new doc_documento($this->link);
            $doc_tipo_documento_modelo = new doc_tipo_documento($this->link);

            $filtro = array('doc_tipo_documento.descripcion' => 'FACHADA');

            $r_tipo_doc = $doc_tipo_documento_modelo->filtro_and(filtro: $filtro);
            if (errores::$error) {
                return $this->retorno_error(mensaje: 'Error al obtener tipo de doc', data: $r_tipo_doc, header: $header, ws: $ws);
            }


            $doc_documento = array();
            $doc_documento['doc_tipo_documento_id'] = $r_tipo_doc->registros[0]['doc_tipo_documento_id'];


            $extension = pathinfo($_FILES['fachada']['name'])['extension'];

            $file = array();
            $file['name'] = $alta->token . "." . $extension;
            $file['tmp_name'] = $_FILES['fachada']['tmp_name'];


            $r_alta = $doc_documento_modelo->alta_documento($doc_documento, $file);
            if (errores::$error) {
                return $this->retorno_error(mensaje: 'Error al guardar foto', data: $r_alta, header: $header, ws: $ws);
            }
            $doc_documento_id = $r_alta->registro_id;

            $cont_prospecto_alta['doc_documento_id'] = $doc_documento_id;

        }

        if(!isset($_POST['imagen_fachada'])){
            $_POST['imagen_fachada'] = '';
        }

        if($_POST['imagen_fachada'] === ''){
            $error = (new errores())->error('Error suba la fachada',$_POST);
            print_r($error);
            die('ERROR');
        }

        $cont_prospecto_modelo = new cont_prospecto($this->link);


        $cont_prospecto_alta['descripcion'] = $_POST['nombre'].' '.$_POST['apellido_paterno'].' '.$_POST['apellido_materno'];
        $cont_prospecto_alta['descripcion'] .= ".".$alta->token;
        $cont_prospecto_alta['folio'] = $_POST['folio'];
        $cont_prospecto_alta['nombre'] = $_POST['nombre'];
        $cont_prospecto_alta['ap'] = $_POST['apellido_paterno'];
        $cont_prospecto_alta['am'] = $_POST['apellido_materno'];
        $cont_prospecto_alta['fecha_nac'] = $_POST['fecha_nacimiento'];
        $cont_prospecto_alta['telefono'] = $_POST['telefono_1'];
        $cont_prospecto_alta['celular'] = $_POST['celular'];
        $cont_prospecto_alta['latitud'] = $_POST['latitud'];
        $cont_prospecto_alta['longitud'] = $_POST['longitud'];
        $cont_prospecto_alta['usuario_em3'] = $session[0]->usuario_id;
        $cont_prospecto_alta['ohem'] = $session[0]->ohem_id;
        $cont_prospecto_alta['token'] = $alta->token;
        $cont_prospecto_alta['imagen_fachada'] = $_POST['imagen_fachada'];

        $r_alta = $cont_prospecto_modelo->alta_registro($cont_prospecto_alta);
        if(errores::$error){
            return $this->retorno_error(mensaje:  'Error al guardar contrato',data: $r_alta, header: $header, ws: $ws);
        }

        if($header) {
            header("Location: ./index.php?seccion=adm_session&token=$alta->token&accion=inicio&session_em3=$session_em3&mensaje=Exito&tipo_mensaje=exito&session_id=" . (new generales())->session_id);
            exit;
        }


        return $alta;
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

    final public function get_cont_prospecto(
        bool $aplica_template = false, bool $header = false, bool $ws = true): array|string|stdClass
    {
        $folio = $_GET['folio'];

        $con_prospecto_modelo = new cont_prospecto($this->link);
        $filtro['cont_prospecto.folio'] = $folio;
        $rs = $con_prospecto_modelo->filtro_and(filtro: $filtro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener registros',data: $rs);
        }

        return $rs;


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

        if((int)$result[0]->usuario_id === 726 || (int)$result[0]->usuario_id === 6918){
            $_GET['camara'] = 1;
        }
        $_GET['camara'] = 1;

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

        $token_html = '';
        if(isset($_GET['token'])){
            $token_html = "<h1 class='h-side-title page-title page-title-big text-color-primary'>TOKEN: $_GET[token]</h1>";
        }
        $this->token_html = $token_html;


        $cont_prospecto_modelo = new cont_prospecto($this->link);
        $filtro['cont_prospecto.usuario_em3'] = $result[0]->usuario_id;

        $r_mis_contratos = $cont_prospecto_modelo->filtro_and(filtro:$filtro,limit: 5,order: array('cont_prospecto.id'=>'DESC'));
        if(errores::$error){
            return $this->retorno_error(mensaje:  'Error al obtener contratos',data: $r_mis_contratos, header: $header, ws: $ws);
        }

        $contratos = $r_mis_contratos->registros;

        $ruta_html = (new generales())->url_base;
        foreach ($contratos as $index=>$contrato){
            $contrato = (object)$contrato;

            $link_foto = "<a href='$ruta_html$contrato->doc_documento_ruta_relativa' target='_blank'>Ver</a>";

            $contratos[$index] = $contrato;
            $contratos[$index]->nombre_view = $contrato->cont_prospecto_nombre.' '.$contrato->cont_prospecto_ap;
            $contratos[$index]->fachada = $link_foto;
            $contratos[$index]->token = $contrato->cont_prospecto_token;

        }

        $this->registros = $contratos;

        $mensaje_cc = '';
        if(isset($_GET['envia_call_center'])){
            $mensaje_cc = "<h1 class='h-side-title page-title page-title-big text-color-primary'>
AFILIADO CON CONTRATO ANTERIOR APLICA REINCORPORACION, POR FAVOR MARQUE A CALL CENTER <a href='tel:4775820510'>4775820510</a></h1>";
        }

        $this->mensaje_cc = $mensaje_cc;


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
        $result = $this->make_post_request($end_point, $params,false);

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
