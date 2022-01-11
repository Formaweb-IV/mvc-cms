$(document).ready(function(){

    //Sidenav en móviles
    $('.sidenav').sidenav();

    //Mensajes
    var input_tipo = $("input[name=tipo-mensaxe]");
    var input_texto = $("input[name=texto-mensaxe]");
    if (input_tipo.length && input_texto.length){
        //var contenido = $('<span class="'+ input_tipo.val() +'">'+ input_texto.val() +'</span>');
        M.toast({html: input_texto.val(), classes: input_tipo.val() + " lighten-5"});
    }

    //Ocultar toast
    $(".toast").click(function () {
        $(this).hide();
    });

    //Cambiar clave
    $("input[type=checkbox][name=cambiar_clave]").click(function () {
        $("#password").toggleClass( "hide" );
    });

    //Fecha
    $( ".datepicker" ).datepicker({
        firstDay: true,
        format: 'dd-mm-yyyy',
        i18n: {
            months: ["Xaneiro", "Febreiro", "Marzo", "Abril", "Maio", "Xuño", "Xullo", "Agosto", "Setembro", "Outubro", "Novembro", "Decembro"],
            monthsShort: ["Xan", "Feb", "Mar", "Abr", "Mai", "Xuñ", "Xul", "Ago", "Set", "Out", "Nov", "Dec"],
            weekdays: ["Domingo","Luns", "Martes", "Mércores", "Xoves", "Venres", "Sábado"],
            weekdaysShort: ["Dom","Lun", "Mar", "Mer", "Xov", "Ven", "Sab"],
            weekdaysAbbrev: ["D","L", "M", "Me", "X", "V", "S"]
        }
    });

});