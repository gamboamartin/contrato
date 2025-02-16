<?php /** @var gamboamartin\contrato\controllers\controlador_adm_session $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <section class="top-title">
                    <h1 class="h-side-title page-title page-title-big text-color-primary">Contratos</h1>
                    <?php echo $controlador->token_html; ?>
                    <?php echo $controlador->mensaje_cc; ?>
                </section>

                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <form method="post" action="<?php echo $controlador->link_alta_bd; ?>" class="form-additional"
                          enctype="multipart/form-data">
                       <h3>Nuevo</h3>

                        <div class="control-group col-sm-12">
                            <label class="control-label" for="folio">Folio</label>
                            <div class="controls">
                                <select class="form-control selectpicker color-secondary folio" data-live-search="true"
                                        id="folio" name="folio" required tabindex="-98">
                                    <option value="">Selecciona un folio</option>
                                    <?php echo $controlador->options_folio; ?>
                                </select>
                            </div>
                        </div>

                        <div class="control-group col-sm-12">
                            <label class="control-label" for="nombre">Nombre</label>
                            <div class="controls">
                                <input type="text" name="nombre" value="" class="form-control" required
                                       id="nombre" placeholder="Nombre" title="Nombre" pattern="^[a-zA-Z]+(\s?[a-zA-Z])*\s?$">
                            </div>
                        </div>

                        <div class="control-group col-sm-12">
                            <label class="control-label" for="nombre">Apellido Paterno</label>
                            <div class="controls">
                                <input type="text" name="apellido_paterno" value="" class="form-control" required
                                       id="apellido_paterno" placeholder="Apellido Paterno" title="Apellido Paterno"
                                       pattern="^[a-zA-Z]+(\s?[a-zA-Z])*\s?$">
                            </div>
                        </div>

                        <div class="control-group col-sm-12">
                            <label class="control-label" for="nombre">Apellido Materno</label>
                            <div class="controls">
                                <input type="text" name="apellido_materno" value="" class="form-control" required
                                       id="apellido_materno" placeholder="Apellido Materno" title="Apellido Materno"
                                       pattern="^[a-zA-Z]+(\s?[a-zA-Z])*\s?$">
                            </div>
                        </div>

                        <div class="control-group col-sm-12">
                            <label class="control-label" for="nombre">Fecha Nac</label>
                            <div class="controls">
                                <input type="date" name="fecha_nacimiento" value="" class="form-control" required
                                       id="fecha_nacimiento" placeholder="Fecha Nacimiento" title="Fecha nac">
                            </div>
                        </div>

                        <div class="control-group col-sm-12">
                            <label class="control-label" for="telefono_1">Tel</label>
                            <div class="controls">
                                <input type="text" name="telefono_1" value="" class="form-control" required
                                       id="telefono_1" placeholder="Telefono" title="Tel" pattern="^[0-9]{10}$">
                            </div>
                        </div>


                        <div class="control-group col-sm-12">
                            <label class="control-label" for="celular">Cel</label>
                            <div class="controls">
                                <input type="text" name="celular" value="" class="form-control" required
                                       id="celular" placeholder="Celular" title="Cel" pattern="^[0-9]{10}$">
                            </div>
                        </div>


                        <div class="control-group col-sm-12">
                            <label class="control-label" for="fachada">Fachada</label>
                            <div class="controls">
                                <input type="file" name="fachada" value="" class="form-control" required="" id="fachada">
                            </div>
                        </div>

                        <div class="control-group">
                            <button type="submit" class="btn btn-success alta_bd" style="width: 100%;"
                                    value="Alta" name="btn_action_next">Alta</button><br>
                        </div>

                        <input type="hidden" name="latitud" id="latitud" >
                        <input type="hidden" name="longitud" id="longitud" >

                    </form>
                </div>

                <div class="col-md-12 table table-responsive">
                    <h2>Mis Contratos</h2>
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Fachada</th>
                            <th>TOKEN</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php  foreach ($controlador->registros as $contrato){ ?>
                        <tr>
                            <td>
                                <?php  echo $contrato->nombre_view; ?>
                            </td>
                            <td>
                                <?php  echo $contrato->fachada; ?>
                            </td>
                            <td>
                                <?php  echo $contrato->token; ?>
                            </td>
                        </tr>
                        <?php  } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
if(isset($_GET['camara'])){
?>

    <!—Aquí el video embebido de la webcam -->
    <div class='col-md-12'>
        <video id='video' playsinline autoplay></video>
    </div>
    <!—El elemento canvas -->
    <div class='controller'>
        <button id='snap'>Capture</button>
    </div>
    <!—Botón de captura -->
   

    <script>
        'use strict';

        const video = document.getElementById('video');
        const snap = document.getElementById('snap');
        const canvas = document.getElementById('canvas');
        const errorMsgElement = document.querySelector('span#errorMsg');

        const constraints = {
            audio: false,
            video: {
                width: 640, height: 480
            }
        };

        // Acceso a la webcam
        async function init() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                handleSuccess(stream);
            } catch (e) {
                errorMsgElement.innerHTML = `navigator.getUserMedia error:${e.toString()}`;
            }
        }
        // Correcto!
        function handleSuccess(stream) {
            window.stream = stream;
            video.srcObject = stream;
        }
        // Load init
        init();
        // Dibuja la imagen
        var context = video.getContext('2d');
        snap.addEventListener('click', function() {
            context.drawImage(video, 0, 0, 640, 480);
        });

</script>

<?php
}
?>



