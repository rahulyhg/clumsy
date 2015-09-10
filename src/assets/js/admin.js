$(function(){

    $('.click-once:not(.must-confirm)').click(function(){
        $(this).add($(this).siblings('.click-once')).prop('disabled', true);
        $(this).addClass('loading');
    });

    $('.with-tooltip').tooltip();
    $('.navbar').on('show.bs.dropdown', function(event) {
        $el = $(event.target);
        if ($el.hasClass('with-tooltip')){
            $el.tooltip('destroy');
            $el.one('hide.bs.dropdown', function(event) {
                $el.tooltip();
            });
        }
    });

    $('.delete').click(function(e){
        $(this).closest('form').next('.delete-form').submit();
    });
    $('.delete-form').submit(function(e){
        var msg = $(this).hasClass('user') ? handover.admin.strings.delete_confirm_user : handover.admin.strings.delete_confirm;
        if (confirm(msg)) {
            $form = $(this).prev('form');
            $del = $('.delete', $form);
            $del.add('button[type="submit"]', $form).prop('disabled', true);
            $del.addClass('loading');
            return true;
        }
        return false;
    });

    if ($('.rich-text').length) {
        tinymce.init($.extend(
        {
            selector: ".rich-text",
            content_css: handover.admin.urls.base+'/../packages/clumsy/cms/css/tinymce.css',
            menubar : false,
            toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright | bullist numlist | removeformat",
            statusbar : false,
            plugins: "autoresize"
        },
        typeof handover.admin.tinymce === 'undefined' ? {} : handover.admin.tinymce
        ));
    }

    // If a translatable panel pane has an error that prevents saving, switch to it
    if ($('.panel-translatable').length && $('.panel-translatable .has-error').length) {
        $('.tab-pane').each(function(i,el){
            if ($('.has-error', el).length) {
                var target = $(el).attr('id');
                $('a[href="#'+target+'"], a[data-target="'+target+'"]').tab('show');
                return false;
            }
        });
    }

    if ($('.datepicker').length) {
        $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd'
        });
    }

    if ($('.datetimepicker').length) {
        $('.datetimepicker').datetimepicker({
            dateFormat: 'yy-mm-dd',
            timeFormat: 'HH:mm:ss'
        });
    }

    if ($('.timepicker').length) {
        $('.timepicker').timepicker({
            timeFormat: 'HH:mm:ss'
        });
    }

    if ($('.colorpicker').length) {
        $('.colorpicker').iris(
            $.extend(
                {
                    hide: true,
                },
                typeof handover.admin.colorpicker === 'undefined' ? {} : handover.admin.colorpicker
            )
        );

        $(document).on('click',function(e){
            if ($(e.target).attr('class') == 'form-control colorpicker') {
                $(e.target).iris('show');
            }
            else{
                var container = $('.iris-picker, .iris-picker-inner');
                if (typeof e === 'undefined' || (!container.is(e.target) && container.has(e.target).length === 0)){
                    $('.colorpicker').iris('hide');
                }
            }
        });
    }

    if ($('.coordinates').length){

        $('.coordinates').each(function(){

            var $wrapper = $(this),
                $lat = $wrapper.find('input').eq(0),
                $lng = $wrapper.find('input').eq(1);

            var map,
                marker = null;

            var setMarker = function(lat, lng) {
                if (marker === null) {
                    if (lat !== null && lng !== null) {
                        marker = new google.maps.Marker({
                            map: map,
                            draggable: true,
                            position: new google.maps.LatLng(parseFloat(lat), parseFloat(lng))
                        });
                        map.setZoom(16);
                        map.panTo(marker.position);

                        google.maps.event.addListener(marker, 'dragend', function() {
                            var position = marker.getPosition();
                            $lat.val(position.lat());
                            $lng.val(position.lng());
                        });
                    }
                }
                else {
                    marker.setPosition(new google.maps.LatLng(lat, lng));
                    map.panTo(marker.position);
                }
            };

            var updateMap = function(event) {
                var lat = event.latLng.lat();
                var lng = event.latLng.lng();

                setMarker(lat,lng);

                $lat.val(lat);
                $lng.val(lng);
            };

            var initializeMap = function() {
                var mapOptions = $.extend(
                    {
                        center: {
                            lat: 38.709792,
                            lng: -9.133609
                        },
                        zoom: 14
                    },
                    typeof handover.admin.mapOptions === 'undefined' ? {} : handover.admin.mapOptions
                );

                map = new google.maps.Map($wrapper.find('.map')[0], mapOptions);

                var lat = $lat.val();
                var lng = $lng.val();

                if (lat !== '' && lng !== '') {
                    setMarker(lat, lng);
                }

                google.maps.event.addListener(map, 'rightclick',updateMap);
            };

            google.maps.event.addDomListener(window, 'load', initializeMap);
        });
    }

    $booleans = $('.active-boolean');
    if ($booleans.length) {
        $booleans.click(function(e){
            e.stopPropagation();
            $.post(handover.admin.urls.update,
            {
                _token: $('input[name="_token"]').val(),
                model: $(this).closest('table').data('model'),
                id: $(this).data('id'),
                column: $(this).data('column'),
                column_type: 'boolean',
                value: $(this).prop('checked')
            });
        });
        $booleans.closest('td').click(function(){
            $(this).find('.active-boolean').click();
        });
    }

    if ($('.filter-box').length) {
        var chosen_op = {
            width: '100%',
            no_results_text: handover.admin.strings.filter_no_results
        };

        $('.filter-box select').chosen(chosen_op);

        $('.filter-box select').chosen().change(function(e,data){
            $('#filter-submit-btn').removeAttr('disabled');

            if (typeof data.selected != "undefined") {
               $('#filter-form').append('<input type="hidden" name="'+$(this).data('name')+'[]" value="'+data.selected+'">');
               $(this).parents('.filter-box').find('button').removeAttr('disabled');
            }
            else{
                $('input[value="' + data.deselected + '"]').remove();

                if ($('input[name="' + $(this).data('name') + '[]"]').length === 0) {
                    $(this).parents('.filter-box').find('button').attr('disabled','');
                }
            }
        });

        $('#filter-submit-btn').click(function(){
            $('#filter-form').submit();
        });

        $('#filter-clear-btn').click(function(){
            $('#filter-form input:not([name="_token"],.filter-nested)').remove();
            $('#filter-form').submit();
        });
    }

    $('a[role="tab"]').on('shown.bs.tab', function (e) {
        var tab_id = $(e.target).attr('href').substring(1);
        var $tab = $('div.tab-pane#' + tab_id);

        $tab.find('.photoset-row').attr('style','overflow: hidden;');
    });

    if ($('.reorder-table').length) {

        var reorder = $(".reorder-table tbody").sortable({
            revert: true,
            helper: "clone",
            scroll: false,
            axis: "y",
            placeholder: "sortable-placeholder"

        }).disableSelection();

        reorder.on('sortupdate',function(event,ui){
            $('.reorder-table tbody > tr td:first-child').fadeOut().promise().done(function(){
                $('.reorder-table tbody > tr').each(function(index){
                    $(this).find(' > td:first').text(index + 1);
                }).promise().done(function(){
                    $('.reorder-table tbody > tr td:first-child').fadeIn();
                });
            });
        });
    }

});