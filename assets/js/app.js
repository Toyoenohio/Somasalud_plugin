jQuery(document).ready(function($) {
    console.log('WCBW: App V7 (Contact Method Added)');

    let isSingleMode = $('.wcbw-wrapper').hasClass('single-mode');

    let state = {
        cat_id: null,
        product_id: isSingleMode ? wcbw_vars.single_id : null,
        resource_id: 0,
        doctor_name: '',
        date: $('#wcbw-date').val(),
        selected_slot: null
    };

    init();

    function init() {
        setupListeners();
        if (isSingleMode) {
            loadSlots();
        } else {
            loadSpecialties();
        }
    }

    function setupListeners() {
        $(document).on('click', '.btn-back', function(e) {
            e.preventDefault();
            let target = $(this).data('to');
            if (isSingleMode && target == 1) return;
            goToStep(target);
        });

        $('#wcbw-date').on('change', function() {
            state.date = $(this).val();
            if(state.product_id) loadSlots();
        });

        $('#btn-go-calendar').on('click', function() {
            goToStep(2);
            loadSlots();
        });

        $(document).on('click', '.spec-card', function() {
            let id = $(this).data('id');
            state.cat_id = id;
            
            $('.spec-card').removeClass('selected');
            $(this).addClass('selected');
            
            $('#services-selection').fadeIn();
            $('#service-dropdown').html('<option>Cargando servicios...</option>');

            $.post(wcbw_vars.ajax_url, { action: 'wcbw_get_services_by_cat', cat_id: id }, function(res) {
                let opts = '<option value="">Seleccione servicio...</option>';
                if(res.success && res.data.length) {
                    res.data.forEach(s => opts += `<option value="${s.id}">${s.name}</option>`);
                } else {
                    opts = '<option value="">No hay servicios disponibles</option>';
                }
                $('#service-dropdown').html(opts);
            });
        });

        $('#service-dropdown').on('change', function() {
            let pid = $(this).val();
            state.product_id = pid || null;
            $('#btn-go-calendar').prop('disabled', !pid);
        });

        $(document).on('click', '.time-btn', function(e) {
            e.preventDefault();
            
            state.product_id  = $(this).data('pid');
            state.resource_id = $(this).data('rid'); 
            state.doctor_name = $(this).data('docname');
            let rawValue      = $(this).data('value'); 

            state.selected_slot = {
                time_display: $(this).text(), 
                value: rawValue 
            };

            $('#summary-text').html(`
                <p><strong>Servicio:</strong> ${state.doctor_name}</p>
                <p><strong>Fecha:</strong> ${formatDate(state.date)} a las ${state.selected_slot.time_display}</p>
            `);
            
            goToStep(3);
        });

        $('#btn-process').on('click', function() {
            processDirectOrder();
        });

        $('#rut').on('input', function() {
            let val = $(this).val().replace(/[^0-9kK]/g, '');
            if (val.length > 1) {
                let body = val.slice(0, -1).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                let dv = val.slice(-1).toUpperCase();
                $(this).val(body + '-' + dv);
            } else {
                $(this).val(val);
            }
        });
    }

    function loadSpecialties() {
        $.post(wcbw_vars.ajax_url, { action: 'wcbw_get_specialties' }, function(res) {
            if(res.success && res.data.length) {
                let html = '';
                res.data.forEach(i => html += `<div class="spec-card" data-id="${i.id}"><img src="${i.image}" alt="${i.name}"><h4>${i.name}</h4></div>`);
                $('#specialties-grid').html(html);
            } else {
                $('#specialties-grid').html('<p>No se encontraron categorías.</p>');
            }
        });
    }

    function loadSlots() {
        $('#slots-container').html('<div class="spinner">Consultando disponibilidad...</div>');
        $('#slots-container').removeClass('error-box'); 

        $.post(wcbw_vars.ajax_url, { 
            action: 'wcbw_get_booking_slots', 
            product_id: state.product_id, 
            date: state.date 
        }, function(res) {
            $('#slots-container').empty();
            if(res.success && res.data.length > 0) {
                res.data.forEach(doc => {
                    let times = '';
                    doc.slots.forEach(slot => {
                        times += `<button class="time-btn" data-pid="${doc.product_id}" data-rid="${doc.resource_id}" data-docname="${doc.doctor_name}" data-value="${slot.value}">${slot.time}</button>`;
                    });
                    $('#slots-container').append(`
                        <div class="doctor-row">
                            <div class="doc-info"><strong>${doc.doctor_name}</strong><br><small>${doc.price}</small></div>
                            <div class="doc-times">${times}</div>
                        </div>`);
                });
            } else {
                let msg = res.data && typeof res.data === 'string' ? res.data : 'No hay horas disponibles para esta fecha.';
                $('#slots-container').html('<div class="alert-box">' + msg + '</div>');
            }
        });
    }

    function processDirectOrder() {
        let rut = $('#rut').val();
        let contactMethod = $('#contact_method').val(); // Capturar nueva opción

        if(rut.length < 8) { alert('RUT inválido.'); return; }
        if($('#fullname').val() === '' || $('#email').val() === '') { alert('Complete nombre y correo.'); return; }
        if(contactMethod === '') { alert('Por favor, seleccione una plataforma de atención.'); return; } // Validación

        let btn = $('#btn-process');
        btn.text('Generando pedido...').prop('disabled', true);

        let data = {
            action:         'wcbw_process_direct_order',
            nonce:          wcbw_vars.nonce,
            product_id:     state.product_id,
            resource_id:    state.resource_id,
            date:           state.date, 
            time:           state.selected_slot.time_display, 
            rut:            rut,
            name:           $('#fullname').val(),
            email:          $('#email').val(),
            phone:          $('#phone').val(),
            contact_method: contactMethod // Enviar al PHP
        };

        $.post(wcbw_vars.ajax_url, data, function(res) {
            if (res.success) {
                window.location.href = res.data.redirect;
            } else {
                alert('Error al crear el pedido: ' + res.data);
                btn.text('Ir a Pagar').prop('disabled', false);
            }
        }).fail(function() {
             alert('Error de conexión con el servidor.');
             btn.text('Ir a Pagar').prop('disabled', false);
        });
    }

    function goToStep(s) {
        $('.step-pane').hide().removeClass('active');
        $(`#step-${s}`).fadeIn().addClass('active');
        $('.wcbw-steps li').removeClass('active');
        $(`.wcbw-steps li[data-step="${s}"]`).addClass('active');
    }

    function formatDate(d) {
        if(!d) return '';
        let p = d.split('-');
        return `${p[2]}/${p[1]}/${p[0]}`; 
    }
});