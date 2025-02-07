let txt_nombre = $("#nombre");
let nombre = '';

let txt_apellido_paterno = $("#apellido_paterno");
let apellido_paterno = '';

let txt_apellido_materno = $("#apellido_materno");
let apellido_materno = '';

let latitud_input = $("#latitud");
let longitud_input = $("#longitud");

let latitud = -1111;
let longitud = -1111;

txt_nombre.change(function () {
    nombre = $(this).val();
    nombre = nombre.toUpperCase();
    nombre = nombre.trim();
    txt_nombre.val(nombre);
});

txt_apellido_paterno.change(function () {
    apellido_paterno = $(this).val();
    apellido_paterno = apellido_paterno.toUpperCase();
    apellido_paterno = apellido_paterno.trim();
    txt_apellido_paterno.val(apellido_paterno);
});

txt_apellido_materno.change(function () {
    apellido_materno = $(this).val();
    apellido_materno = apellido_materno.toUpperCase();
    apellido_materno = apellido_materno.trim();
    txt_apellido_materno.val(apellido_materno);
});

getLocation();

function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(showPosition, showError);
    } else {
        alert("Geolocalización no soportada por este navegador.");
    }

}

function showPosition(position) {
     latitud = position.coords.latitude;
     longitud = position.coords.longitude;

    latitud_input.val(latitud);
    longitud_input.val(longitud);
    alert(latitud_input.val());
    alert(longitud_input.val());
    // Enviar los datos a PHP usando AJAX
   /* var xhttp = new XMLHttpRequest();
    xhttp.open("POST", "procesar_coordenadas.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("latitud=" + latitud + "&longitud=" + longitud);*/
}

function showError(error) {
    switch(error.code) {
        case error.PERMISSION_DENIED:
            alert("El usuario denegó la solicitud de geolocalización.");
            break;
        case error.POSITION_UNAVAILABLE:
            alert("Información de ubicación no disponible.");
            break;
        case error.TIMEOUT:
            alert("La solicitud para obtener la ubicación del usuario ha superado el tiempo de espera.");
            break;
        case error.UNKNOWN_ERROR:
            alert("Ha ocurrido un error desconocido.");
            break;
    }
}