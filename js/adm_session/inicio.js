let txt_nombre = $("#nombre");
let nombre = '';

let txt_apellido_paterno = $("#apellido_paterno");
let apellido_paterno = '';

let txt_apellido_materno = $("#apellido_materno");
let apellido_materno = '';

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