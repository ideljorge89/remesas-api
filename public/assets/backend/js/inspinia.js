/*
 *
 *   INSPINIA - Responsive Admin Theme
 *   version 2.4
 *
 */


$(document).ready(function () {


    $('.change_state').popover({
        html: true,
        trigger: 'manual'
    }).click(function (e) {
        $('.change_state').not(this).popover('hide');
        $(this).popover('toggle');
    });
    $(document).click(function (e) {
        if (!$(e.target).is('.change_state')) {
            $('.change_state').popover('hide');
        }
    });

    $(".show-option").tooltip({
        show: {
            effect: "slideDown",
            delay: 250
        },
        container: 'body'
    });

    $(function () {
        $('.lazy').lazy();
    });

    function tooltip() {
        setTimeout(function () {
            $('[data-toggle="tooltip"]').tooltip({
                show: {
                    effect: "slideDown",
                    delay: 250
                },
                container: 'body'
            });
        }, 1000)
    }

    tooltip()
    var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));

    elems.forEach(function (html) {
        var switchery = new Switchery(html);
    });

    // Add body-small class if window less than 768px
    if ($(this).width() < 769) {
        $('body').addClass('body-small')
    } else {
        $('body').removeClass('body-small')
    }


    // MetsiMenu
    $('#side-menu').metisMenu();

    //// Collapse ibox function
    //$('.collapse-link').click(function () {
    //    var ibox = $(this).closest('div.ibox');
    //    var button = $(this).find('i');
    //    var content = ibox.find('div.ibox-content');
    //    content.slideToggle(200);
    //    button.toggleClass('fa-chevron-up').toggleClass('fa-chevron-down');
    //    ibox.toggleClass('').toggleClass('border-bottom');
    //    setTimeout(function () {
    //        ibox.resize();
    //        ibox.find('[id^=map-]').resize();
    //    }, 50);
    //});
    //
    //// Close ibox function
    //$('.close-link').click(function () {
    //    var content = $(this).closest('div.ibox');
    //    content.remove();
    //});

    // Fullscreen ibox function
    $('.fullscreen-link').click(function () {
        var ibox = $(this).closest('div.ibox');
        var button = $(this).find('i');
        $('body').toggleClass('fullscreen-ibox-mode');
        button.toggleClass('fa-expand').toggleClass('fa-compress');
        ibox.toggleClass('fullscreen');
        setTimeout(function () {
            $(window).trigger('resize');
        }, 100);
    });

    // Close menu in canvas mode
    $('.close-canvas-menu').click(function () {
        $("body").toggleClass("mini-navbar");
        SmoothlyMenu();
    });

    // Run menu of canvas
    $('body.canvas-menu .sidebar-collapse').slimScroll({
        height: '100%',
        railOpacity: 0.9
    });

    // Open close right sidebar
    $('.right-sidebar-toggle').click(function () {
        $('#right-sidebar').toggleClass('sidebar-open');
    });

    // Initialize slimscroll for right sidebar
    $('.sidebar-container').slimScroll({
        height: '100%',
        railOpacity: 0.4,
        wheelStep: 10
    });

    // Open close small chat
    $('.open-small-chat').click(function () {
        $(this).children().toggleClass('fa-comments').toggleClass('fa-remove');
        $('.small-chat-box').toggleClass('active');
    });

    // Initialize slimscroll for small chat
    $('.small-chat-box .content').slimScroll({
        height: '234px',
        railOpacity: 0.4
    });

    // Small todo handler
    $('.check-link').click(function () {
        var button = $(this).find('i');
        var label = $(this).next('span');
        button.toggleClass('fa-check-square').toggleClass('fa-square-o');
        label.toggleClass('todo-completed');
        return false;
    });

    // Append config box / Only for demo purpose
    // Uncomment on server mode to enable XHR calls
    //$.get("skin-config.html", function (data) {
    //    if (!$('body').hasClass('no-skin-config'))
    //        $('body').append(data);
    //});

    // Minimalize menu
    $('.navbar-minimalize').click(function () {
        $("body").toggleClass("mini-navbar");
        SmoothlyMenu();

    });

    // Tooltips demo
    $('.tooltip-demo').tooltip({
        selector: "[data-toggle=tooltip]",
        container: "body"
    });

    // Move modal to body
    // Fix Bootstrap backdrop issu with animation.css
    $('.modal').appendTo("body");

    // Full height of sidebar
    function fix_height() {
        var heightWithoutNavbar = $("body > #wrapper").height() - 61;
        $(".sidebard-panel").css("min-height", heightWithoutNavbar + "px");

        var navbarHeigh = $('nav.navbar-default').height();
        var wrapperHeigh = $('#page-wrapper').height();

        if (navbarHeigh > wrapperHeigh) {
            $('#page-wrapper').css("min-height", navbarHeigh + "px");
        }

        if (navbarHeigh < wrapperHeigh) {
            $('#page-wrapper').css("min-height", $(window).height() + "px");
        }

        if ($('body').hasClass('fixed-nav')) {
            if (navbarHeigh > wrapperHeigh) {
                $('#page-wrapper').css("min-height", navbarHeigh - 60 + "px");
            } else {
                $('#page-wrapper').css("min-height", $(window).height() - 60 + "px");
            }
        }

    }

    fix_height();

    // Fixed Sidebar
    $(window).bind("load", function () {
        if ($("body").hasClass('fixed-sidebar')) {
            $('.sidebar-collapse').slimScroll({
                height: '100%',
                railOpacity: 0.9
            });
        }
    });

    // Move right sidebar top after scroll
    $(window).scroll(function () {
        if ($(window).scrollTop() > 0 && !$('body').hasClass('fixed-nav')) {
            $('#right-sidebar').addClass('sidebar-top');
        } else {
            $('#right-sidebar').removeClass('sidebar-top');
        }
    });

    $(window).bind("load resize scroll", function () {
        if (!$("body").hasClass('body-small')) {
            fix_height();
        }
    });

    $("[data-toggle=popover]")
        .popover();

    // Add slimscroll to element
    $('.full-height-scroll').slimscroll({
        height: '100%'
    })

    loadToastr()
    $('#collapse_menu').on('click', function () {
        side = $.cookie('side_bar');
        if (side == 'min') {
            $.cookie('side_bar', 'max', '/')
        } else $.cookie('side_bar', 'min', '/')
    })
    $(document).on('hidden.bs.modal', function (e) {
        if ($(e.target).attr('data-refresh') == 'true') {
            // Remove modal data
            $(e.target).removeData('bs.modal');
            // Empty the HTML of modal
            $(e.target).find('.modal-form').html('<div style="height: 200px;padding-top: 70px;"><div class="sk-spinner sk-spinner-circle">' +
                '<div class="sk-circle1 sk-circle"></div>' +
                '<div class="sk-circle2 sk-circle"></div>' +
                '<div class="sk-circle3 sk-circle"></div>' +
                '<div class="sk-circle4 sk-circle"></div>' +
                '<div class="sk-circle5 sk-circle"></div>' +
                '<div class="sk-circle6 sk-circle"></div>' +
                '<div class="sk-circle7 sk-circle"></div>' +
                '<div class="sk-circle8 sk-circle"></div>' +
                '<div class="sk-circle9 sk-circle"></div>' +
                '<div class="sk-circle10 sk-circle"></div>' +
                '<div class="sk-circle11 sk-circle"></div>' +
                '<div class="sk-circle12 sk-circle"></div>' +
                '</div></div>');
        }
    });
    dataLink = function () {

        $('[data-toggle="tooltip"]').tooltip();

        $("a[data-target=#modal-form]").click(function (ev) {
            ev.preventDefault();
            var target = $(this).attr("href");
            var w = $(this).data('width')
            if ($(this).data('loading') == false || $(this).data('loading') == undefined) {
                // load the url and show modal on success
                $(this).data('loading', true)
                /*$(this).addClass('disabled')*/

                var tty = this;
                setTimeout(function () {
                    $(tty).data('loading', false)
                    /*$(tty).removeClass('disabled')*/
                }, 500);
                $("#modal-form .modal-content").load(target, function () {
                    $("#modal-form > .modal-dialog").attr('class', 'modal-dialog')
                    var width = w || $("#modal-form").data('width')
                    if (width) {
                        $("#modal-form > .modal-dialog").removeClass($("#modal-form").data('width'))

                        $("#modal-form > .modal-dialog").addClass(width)
                    }

                    $("#modal-form").modal("show");
                    $('[data-toggle="tooltip"]').tooltip({
                        'container': 'body'
                    });
                    setTimeout(function () {
                        $(window).trigger('resize');
                    }, 100);
                });
            }

        })
    }
    dataLink()
    $('.footable').footable({
        "empty": "Sin resultados",
        "filtering": {
            "connectors": false,
            "placeholder": "Buscar ..."
        },
        "paging": {
            "position": "center",
            "countFormat": "Mostrando {CP} of {TP} páginas| Total registros:{TR}"
        }
    }).on("preinit.ft.paging after.ft.paging after.ft.filtering after.ft.sorting", function (e,v,f) {
        dataLink()
        return true;
    })
    $('input[type=number]').on('keypress', function (e) {
        return e.metaKey || // cmd/ctrl
            e.which <= 0 || // arrow keys
            e.which == 8 || // delete key
            /[0-9]/.test(String.fromCharCode(e.which)); // numbers
    })

    //$(window).keydown(function(event){
    //    if(event.keyCode == 13) {
    //        event.preventDefault();
    //        return false;
    //    }
    //});
    $.ajaxSetup({
        error: function (jqXHR, textStatus, errorThrown) {

            if (jqXHR.status === 0) {

                alert('Not connect: Verify Network.');

            } else if (jqXHR.status == 404) {

                alert('Requested page not found [404]');

            } else if (jqXHR.status == 500) {

                alert('Internal Server Error [500].');

            } else if (textStatus === 'parsererror') {

                alert('Requested JSON parse failed.');

            } else if (textStatus === 'timeout') {

                alert('Time out error.');

            } else if (textStatus === 'abort') {

                alert('Ajax request aborted.');

            } else {

                alert('Uncaught Error: ' + jqXHR.responseText);

            }

        }
    })


    $('.chosen-select').chosen({
        allow_single_deselect: true,
        //disable_search_threshold: 10,
        width: "100%",
        //no_results_text: 'Oops, nothing found!'
    });
    $('select').chosen({
        allow_single_deselect: true,
        //disable_search_threshold: 10,
        width: "100%",
        //no_results_text: 'Oops, nothing found!'
    });
});

function peso_formatter(value, options, rowData) {
    if (value) {
        return $.fmatter.util.NumberFormat(value, {
            decimalSeparator: ',',
            decimalPlaces: 3,
            suffix: '',
            thousandsSeparator: '',
            prefix: ''
        })
    }
}

function formatter(value, options, rowData) {
    if (value) {
        value = value.toUpperCase()
        switch (value) {
            case 'CANCELADO':
                return "<label class='label label-danger'>" + value + "</label>"
                break;
            case 'REPORTADO':
                return "<label class='label label-info'>" + value + "</label>"
                break;
            case 'OFERTADO':
                return "<label class='label label-warning'>" + value + "</label>"
                break;
            case 'VENDIDO':
                return "<label class='label label-default'>" + value + "</label>"
                break;
            case 'BUQUEADO':
                return "<label class='label label-warning-light'>" + value + "</label>"
                break;
            case 'EXPORTADO':
                return "<label class='label label-primary'>" + value + "</label>"
                break;
        }
        return value;
    }
}

$('.table-responsive').on('show.bs.dropdown', function () {
    $('.table-responsive').css("overflow", "inherit");
});

$('.table-responsive').on('hide.bs.dropdown', function () {
    $('.table-responsive').css("overflow", "auto");
})


// Minimalize menu when screen is less than 768px
$(window).bind("resize", function () {
    if ($(this).width() < 769) {
        $('body').addClass('body-small')
    } else {
        $('body').removeClass('body-small')
    }
});

// Local Storage functions
// Set proper body class and plugins based on user configuration
$(document).ready(function () {
    if (localStorageSupport) {

        var collapse = localStorage.getItem("collapse_menu");
        var fixedsidebar = localStorage.getItem("fixedsidebar");
        var fixednavbar = localStorage.getItem("fixednavbar");
        var boxedlayout = localStorage.getItem("boxedlayout");
        var fixedfooter = localStorage.getItem("fixedfooter");

        var body = $('body');

        if (fixedsidebar == 'on') {
            body.addClass('fixed-sidebar');
            $('.sidebar-collapse').slimScroll({
                height: '100%',
                railOpacity: 0.9
            });
        }

        if (collapse == 'on') {
            if (body.hasClass('fixed-sidebar')) {
                if (!body.hasClass('body-small')) {
                    body.addClass('mini-navbar');
                }
            } else {
                if (!body.hasClass('body-small')) {
                    body.addClass('mini-navbar');
                }

            }
        }

        if (fixednavbar == 'on') {
            $(".navbar-static-top").removeClass('navbar-static-top').addClass('navbar-fixed-top');
            body.addClass('fixed-nav');
        }

        if (boxedlayout == 'on') {
            body.addClass('boxed-layout');
        }

        if (fixedfooter == 'on') {
            $(".footer").addClass('fixed');
        }
    }
});

// check if browser support HTML5 local storage
function localStorageSupport() {
    return (('localStorage' in window) && window['localStorage'] !== null)
}

function loading_show() {
    $('.loadingfy.sk-spinner').css('width', '100px');
    $('.loadingfy.sk-spinner').css('height', '100px');
    $('#wrapper').css('opacity', .3);
    $('.sk-spinner').parent().show();
}

function loading_hide() {
    $('.loadingfy.sk-spinner').parent().hide();
    $('#wrapper').css('opacity', 1);

}


// For demo purpose - animation css script
function animationHover(element, animation) {
    element = $(element);
    element.hover(
        function () {
            element.addClass('animated ' + animation);
        },
        function () {
            //wait for animation to finish before removing classes
            window.setTimeout(function () {
                element.removeClass('animated ' + animation);
            }, 2000);
        });
}

function SmoothlyMenu() {
    if (!$('body').hasClass('mini-navbar') || $('body').hasClass('body-small')) {
        // Hide menu in order to smoothly turn on when maximize menu
        $('#side-menu').hide();
        // For smoothly turn on menu
        setTimeout(
            function () {
                $('#side-menu').fadeIn(400);
            }, 200);
    } else if ($('body').hasClass('fixed-sidebar')) {
        $('#side-menu').hide();
        setTimeout(
            function () {
                $('#side-menu').fadeIn(400);
            }, 100);
    } else {
        // Remove all inline style from jquery fadeIn function to reset menu state
        $('#side-menu').removeAttr('style');
    }
}

// Dragable panels
function WinMove() {
    var element = "[class*=col]";
    var handle = ".ibox-title";
    var connect = "[class*=col]";
    $(element).sortable(
        {
            handle: handle,
            connectWith: connect,
            tolerance: 'pointer',
            forcePlaceholderSize: true,
            opacity: 0.8
        })
        .disableSelection();
}


function loadToastr() {

    $(document).ready(function () {
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "progressBar": true,
            "preventDuplicates": false,
            "positionClass": "toast-top-right",
            "onclick": null,
            "showDuration": "100",
            "hideDuration": "100",
            "timeOut": "6000",
            "extendedTimeOut": "250",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        }

        $messages = $("[data-action='toastr']")
        for (i = 0; i < $messages.length; i++) {
            var $action = $($messages[i]).data('method');
            title = $($messages[i]).data('title');
            message = $($messages[i]).data('message');
            switch ($action) {
                case "success":
                    toastr.success(message, title);
                    break;
                case "info":
                    toastr.info(message, title);
                    break;
                case "warning":
                    toastr.warning(message, title);
                    break;
                case "error":
                    toastr.error(message, title);
                    break;
                default:

            }

        }
    })
}

function showToastr(type, title, message) {
    switch (type) {
        case "success":
            toastr.success(message, title);
            break;
        case "info":
            toastr.info(message, title);
            break;
        case "warning":
            toastr.warning(message, title);
            break;
        case "error":
            toastr.error(message, title);
            break;
        default:

    }
}


$.validator.methods.range = function (value, element, param) {
    var globalizedValue = value.replace(",", ".");
    return this.optional(element) || (globalizedValue >= param[0] && globalizedValue <= param[1]);
}

$.validator.methods.number = function (value, element) {
    return this.optional(element) || /^-?(?:\d+|\d{1,3}(?:[\s\.,]\d{3})+)(?:[\.,]\d+)?$/.test(value);
}
