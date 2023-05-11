jQuery(document).ready(function($){

    // FORM SUBMIT GIF
	var $internal = $('body.parent-pageid-39, body.parent-pageid-22');

	$(document).on({
		ajaxStart: function() { $internal.addClass("loading"); },
		ajaxStop: function() { $internal.removeClass("loading"); }    
	});

    // GET URL PARAMETERS
    var getUrlParameter = function getUrlParameter(sParam) {
        var sPageURL = window.location.search.substring(1),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;
  
        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');
  
            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
            }
        }
    };

    // FORM SUBMIT - FILE
    // add class .file-upload to form
    $('.da-form.file-upload').submit(function(e){
        e.preventDefault();

        var form = $(this);
        var url = form.attr('action');

        $.ajax({
            type: "POST",
            url: url,
            data: new FormData(form[0]),
            contentType: false,
            processData: false,
            success: function(data) {
                console.log(data);
                alert(data);
            }, error:function(error){
                console.log(error);
                alert("Error: " + error.responseText);
            }
        });

    });

    // FORM SUBMIT - RETURN URL
    // add class .return-url to form
    $('.da-form.return-url').submit(function(e){
        e.preventDefault();

        var form = $(this);
        var url = form.attr('action');

        $.ajax({
            type: "POST",
            url: url,
            data: form.serialize(),
            dataType: 'JSON',
            success: function(data) {
                console.log(data);
                window.open(data + '?' + Math.random());
            }, error:function(error){
                console.log(error);
                alert("Error: " + error.responseText);
            }
        });

    });

    // FORM SUBMIT - STANDARD
    // add class .standard to form
    $('.da-form.standard').submit(function(e){
        e.preventDefault();

        var form = $(this);
        var url = form.attr('action');

        $.ajax({
            type: "POST",
            url: url,
            data: form.serialize(),
            success: function(data) {
                console.log(data);
                alert(data);
            }, error:function(error){
                console.log(error);
                alert("Error: " + error.responseText);
            }
        });

    });

    // TEST MODE
    var test_mode = getUrlParameter('testing');

    if(test_mode){
        console.warn('TEST MODE ENABLED');
        $('form').append('<input type="hidden" name="testing" value="1">');

        $.ajaxSetup({
          data: {
            testing: "1"
          }
        });
    }

    // TABLE EDIT TOOLS
    $('.da-table.editable').on('click', 'tbody tr', function(){
        var $position = $(this).offset();

        if($(this).hasClass('selected')){
            $('.da-edit-tools').removeClass('show');
            $(this).removeClass('selected');
        } else{
            $('.da-edit-tools').addClass('show').css($position);
            $('.selected').removeClass('selected');
            $(this).addClass('selected');
        }
    });

    // POPUP
    $('body').on('click', '#popup-overlay', function(){
        $('#popup-overlay, .popup').removeClass('show');
    });

    // DELIVERY NOTES
    // Generate PDF
    $('.page-id-72 a.generate-pdf ').click(function(e){
        e.preventDefault();
        
        var $truckID = $('input[name="truckid"]').val();
        var $pdfType = $(this).hasClass('scanning') ? 'scanning' : 'delivery';

        if(!$truckID){
            alert('No Truck ID entered. Please filter table by Truck ID before generating form.');
            return;
        }
            
        $.ajax({
            type: "POST",
            url: 'https://dash-delagua.com/da-forms/delivery-notes/outbound-trucks.php',
            data: {'truckID':$truckID, 'pdfType':$pdfType},
            success: function(data) {   
                console.log(data);
                window.open('https://dash-delagua.com/da-forms/delivery-notes/outbound_scanning_form.pdf?' + Math.random());
            }, error:function(error){
                console.log(error);
                alert("Error: " + error.responseText);
            }
        });
        
    });

    // NEW Generate PDF
    $('.page-id-282 a.generate-pdf ').click(function(e){
        e.preventDefault();
        
        var $truckID = $('input[name="truckid"]').val();
        var $pdfType = $(this).hasClass('scanning') ? 'scanning' : 'delivery';

        if(!$truckID){
            alert('No Truck ID entered. Please filter table by Truck ID before generating form.');
            return;
        }
            
        $.ajax({
            type: "POST",
            url: 'https://dash-delagua.com/da-forms/delivery-notes/new-outbound-trucks.php',
            data: {'truckID':$truckID, 'pdfType':$pdfType},
            success: function(data) {   
                console.log(data);
                window.open('https://dash-delagua.com/da-forms/delivery-notes/outbound_scanning_form.pdf?' + Math.random());
            }, error:function(error){
                console.log(error);
                alert("Error: " + error.responseText);
            }
        });
        
    });

    // Generate Table
    $('.page-id-72 input[name="truckid"]').on('change', function(){
        var $truckID = $(this).val();

        $('table.hidden, .pdf-buttons').addClass('show');
        $('table .show').remove();

        $.ajax({
            type: "POST",
            url: 'https://dash-delagua.com/da-forms/delivery-notes/generate-table.php',
            data: {'truckID':$truckID},
            dataType: 'JSON',
            success: function(data) {
                console.log(data);

                for($i=0;$i<data.length;$i++){
                    var $tdid = data[$i]['tdid'];
                    var $destid = data[$i]['destid'];
                    var $investorid = data[$i]['investorid'];
                    var $prefix = data[$i]['prefix'];
                    var $cell = data[$i]['Cell'];
                    var $cellid = data[$i]['Cellid'];
                    var $sector = data[$i]['Sector'];
                    var $sectorid = data[$i]['Sectorid'];
                    var $district = data[$i]['District'];
                    var $districtid = data[$i]['Districtid'];
                    var $province = data[$i]['Province'];
                    var $provinceid = data[$i]['Provinceid'];
                    var $posters = data[$i]['posters'];
                    var $stoves = data[$i]['stoves'];
                    var $location = data[$i]['location'];

                    var $clone = $('.clone').clone().removeClass('clone').addClass('show').appendTo('tbody');

                    $clone.find('.tdid').text($tdid);
                    $clone.find('.destid').text($destid);
                    $clone.find('.cell').text($cell).attr('data-id', $cellid);
                    $clone.find('.sector').text($sector).attr('data-id', $sectorid);
                    $clone.find('.district').text($district).attr('data-id', $districtid);
                    $clone.find('.province').text($province).attr('data-id', $provinceid);
                    $clone.find('.posters').text($posters);
                    $clone.find('.stoves').text($stoves);
                    $clone.find('.location').text($location);
                    $clone.find('.bcprefix').text($prefix);
                    $clone.find('.investorid').text($investorid);
                }
            }, error:function(error){
                console.log(error);
                alert("Error: " + error.responseText);
            }
        });
    });

    // NEW - Generate Table
    $('.page-id-282 input[name="truckid"]').on('change', function(){
        var $truckID = $(this).val();

        $('table.hidden, .pdf-buttons').addClass('show');
        $('table .show').remove();

        $.ajax({
            type: "POST",
            url: 'https://dash-delagua.com/da-forms/delivery-notes/new-generate-table.php',
            data: {'truckID':$truckID},
            dataType: 'JSON',
            success: function(data) {
                console.log(data);
                if(data.trips.length == 0){
                    alert('No information in database for selected Truck ID.');
                    return;
                }

                // append header to table
                $('table thead').append('<tr class="show"></tr>');
                $.each(data, function(i){
                    // FIX: currently only checking headers of first item, but second item could have more sub-levels
                    $.each(data[i][0], function(key){
                        if (key.indexOf('id-') >= 0){
                            return;
                        }
                        $('table thead tr').append('<th>' + key + '</th>');
                    });
                });

                // append rows to table
                for($i=0;$i<data['trips'].length;$i++){
                    $row = $('table tbody').append('<tr class="show row-'+$i+'"></tr>');
                    
                    $.each(data, function(i){
                        $.each(data[i][$i], function(key, value){
                            key = key.replace(/\s/g, '').toLowerCase();
                            if (key.indexOf('id-') >= 0){

                                // find td with class matching key and add value as data-id
                                var substr = '.' + key.replace('id-', '');
                                if( $(substr).length ){
                                    $('tr.row-'+$i+' td' + substr).attr('data-id', value);
                                    return;
                                }

                                // else just append as hidden td
                                $('tr.row-'+$i).append('<td class="hidden '+key+'">' + value + '</td>');
                                return;
                            }
                            $('tr.row-'+$i).append('<td class="'+key+'">' + value + '</td>');
                        });
                    });

                }

            }, error:function(error){
                console.log(error);
                alert("Error: " + error.responseText);
            }
        });
    });

    // Edit Buttons
    $('body').on('click', '.da-edit-tools a', function(e){
        e.preventDefault();

        var $formID = '#' + $('.popup form').attr('id');
        var $command = $(this).hasClass('new-row') ? 'new' : 'edit';

        $('#popup-overlay, .popup').addClass('show');
        $('.region.show').remove();
        $('.region select.country').val('');

        if($command == 'edit'){
            var $tdid = $('.selected .tdid').text();
            var $provinceID = $('.selected .province').data('id');
            var $districtID = $('.selected .district').data('id');
            var $sectorID = $('.selected .sector').data('id');
            var $cellDest = $('.selected .destid').text();
            var $cellStorage = $('.selected .cell').data('id');
            var $stoves = $('.selected .stoves').text();
            var $posters = $('.selected .posters').text();
            var $investorid = $('.selected .investorid').text();

            console.log($cellDest, $cellStorage);

            $('input[name="tdid"]').val($tdid);
            $('input[name="stoves"]').val($stoves);
            $('input[name="posters"]').val($posters);
            $('select#bcformat').val($investorid);
            // Rwanda
            $('select.country').val(1);
            getSubRegions(1, null, $formID);
            // Province
            $('select.province').val($provinceID);
            getSubRegions($provinceID, null, $formID);
            // District
            $('select.district').val($districtID);
            getSubRegions($districtID, null, $formID);
            // Sector
            $('select.sector').val($sectorID);
            getSubRegions($sectorID, 'Storage Cell', $formID);
            getSubRegions($sectorID, 'Distribution Cell', $formID);
            // Cell
            $('select.cell').eq(0).val($cellDest);
            $('select.cell').eq(1).val($cellStorage);
        } else {
            $('.popup form input[type="number"], .popup form input[type="hidden"], #province, #bcformat').val('');
        }

        var $tid = $('input[name="truckid"]').val();
        $('input[name="tid"]').val($tid);

    });

    // NEW Edit Buttons
    $('.page-id-282').on('click', '.da-edit-tools a', function(e){
        e.preventDefault();

        var $formID = '#' + $('.popup form').attr('id');
        var $command = $(this).hasClass('new-row') ? 'new' : 'edit';

        $('#popup-overlay, .popup').addClass('show');
        $('.region.show').remove();
        $('.region select.country').val('');

        if($command == 'edit'){
            var $tdid = $('.selected .tdid').text();
            var $destid = $('.selected .dest').text();
            var $stoves = $('.selected .stoves').text();
            var $posters = $('.selected .posters').text();
            var $bcprefix = $('.selected .id-investor').text();
            var $distrRegion = $('.selected .id-distrregion').text().toLowerCase();
            var $distrid = $('.selected .id-distr').text();

            $('input[name="tdid"]').val($tdid);
            $('input[name="stoves"]').val($stoves);
            $('input[name="posters"]').val($posters);
            $('select#bcformat').val($bcprefix);

            // set region selects
            var $countryIndex = $('.selected .country').index();
            var $rowLength = $('.selected td').length;

            for($j=$countryIndex; $j<$rowLength; $j++){
                var $id = $('.selected td').eq($j).data('id');
                var $lastRegion = $formID + ' .da-form-item.region:last';

                // FIX: potentially need to use this if statement to set the final dropdown when 'editing'
                if($('.selected td').eq($j).is('.hidden, .' + $distrRegion)){
                    console.log('skipped');
                    continue;
                }

                $($lastRegion + ' select').val($id);
                getSubRegions($id, null, $formID);

                if( $($lastRegion + ' select').hasClass($distrRegion) ){
                    $($lastRegion + ' label').text('Storage ' + $distrRegion);
                    $($lastRegion + ' select').val($destid);
                    getSubRegions($id, 'Distribution ' + $distrRegion, $formID);
                    $($lastRegion + ' select').val($distrid);
                    continue;
                }
            }
            
        } else {
            $('.popup form input[type="number"], .popup form input[type="hidden"], #province, #bcformat').val('');
        }

        var $tid = $('input[name="truckid"]').val();
        $('input[name="tid"]').val($tid);

    });

    // PRODUCT ORDER FORM
    // Get Barcode Format
    $('#investors, #products').on('change', function(){
        var $investorID = $('#investors').val();
        var $productID = $('#products').val();

        $('#format').find('option:gt(0)').remove();

        if($investorID && $productID){
            $.ajax({
                type: "POST",
                url: 'https://dash-delagua.com/da-forms/product-order/product-order.php',
                data: {'investorID':$investorID, 'productID':$productID},
                dataType: 'JSON',
                success: function(data) {
                    console.log(data);

                    for($i=0;$i<data.length;$i++){
                        var $bcfid = data[$i]['bcfid'];
                        var $prefix = data[$i]['prefix'];

                        $('#format').append('<option value="' + $bcfid + '">' + $prefix + '</option>');
                    }
                }, error:function(error){
                    console.log(error);
                    alert("Error: " + error.responseText);
                }
            });
        }
    });

    // Append Inputs
    $('#format').on('change', function(){
        var $bcfid = $(this).val();

        if($bcfid){
            $('.da-form-item.hidden').addClass('show');
        } else {
            $('.da-form-item.show').removeClass('show');
        }
    });

    $('#product-order').on('change', '.stoveCalc', function(e){
        $bcfid = $('#format').val();
        $stoves = $('input[name="numStoves"]').val();

        if($bcfid === '' || $stoves === ''){
            return;
        }

        $.ajax({
            type: "POST",
            url: 'https://dash-delagua.com/da-forms/product-order/calcStoves.php',
            data: {'bcfid':$bcfid, 'stoves':$stoves},
            dataType: 'JSON',
            success: function(data) {
                $('input[name="start"]').val(data.start);
                $('input[name="end"]').val(data.end);
            }, error:function(error){
                console.log(error);
                alert("Error: " + error.responseText);
            }
        });
    });

    // TEMPLATES
    // Beneficiary Adder
    $('.page-id-115').on('click', 'a[data-id="1"]', function(e){
        e.preventDefault();

        var $link = $(this).attr('href');

        $('#popup-overlay, #ba-popup').addClass('show');
        $('input[name="template-url"]').val($link);
    });

    // CHW Adder
    $('.page-id-115').on('click', 'a[data-id="4"]', function(e){
        e.preventDefault();

        var $link = $(this).attr('href');

        $('#popup-overlay, #chw-popup').addClass('show');
        $('input[name="template-url"]').val($link);
    });

    // Staff Adder
    $('.page-id-115').on('click', 'a[data-id="3"]', function(e){
        e.preventDefault();

        var $link = $(this).attr('href');

        $('#popup-overlay, #sa-popup').addClass('show');
        $('input[name="template-url"]').val($link);
    });

    // Region Adder
    $('.page-id-115').on('click', 'a[data-id="6"]', function(e){
        e.preventDefault();

        var $link = $(this).attr('href');

        $('#popup-overlay, #ra-popup').addClass('show');
        $('input[name="template-url"]').val($link);
    });

    $('#ra-popup').on('change', 'select.country', function(e){
        e.preventDefault();

        $country = $(this).val();
        $dropdown = $('select[name="boundary"]');
        $dropdown.find('option:gt(0)').remove();

        $.ajax({
            type: "POST",
            url: 'https://dash-delagua.com/da-forms/templates/ra-subregions.php',
            data: {'country':$country},
            dataType: 'JSON',
            success: function(data) {
                console.log(data);
                $dropdown.parent().addClass('show');
                for($i=0;$i<data.length;$i++){
                    $dropdown.append('<option data-level="'+data[$i].level+'" value="'+data[$i].name+'">'+data[$i].name+'</option>');
                }
            }, error:function(error){
                console.log(error);
                alert("Error: " + error.responseText);
            }
        });
    });

    $('#ra-popup').on('change', 'select[name="boundary"]', function(e){
        e.preventDefault();

        $level = parseInt($(this).find('option:selected').data('level').replace('Level', ''));
        $sublevel = 'Level' + ($level + 1);
        console.log($sublevel);
        $('input[name="sublevel"]').val($sublevel);
    });

    $('#chw-popup-form').on('change', 'select.country', function(e){
        e.preventDefault();

        var $country = $(this).val();

        $.ajax({
            type: "POST",
            url: 'https://dash-delagua.com/da-forms/templates/chw-regions.php',
            data: {'country':$country},
            dataType: 'JSON',
            success: function(data) {
                data ? $('input[name="workregion-chw"]').val(data.toLowerCase()) : $('input[name="workregion-chw"]').val('');
            }, error:function(error){
                console.log(error);
                alert("Error: " + error.responseText);
            }
        });
    });

    // Activity Schedule
    $('#activity-schedule').submit(function(e){
        e.preventDefault();

        var form = $(this);
        var url = form.attr('action');

        // Clear Table
        $('table tr.show, thead th').remove();

        // Show Button
        $('#export-activity-table').removeClass('hidden');

        $.ajax({
            type: "POST",
            url: url,
            data: form.serialize(),
            dataType: 'JSON',
            success: function(data) {
                if(data){
                    $('.da-table').removeClass('hidden');
                    // Append Header
                    $.each(data[0], function(key, value){
                        $('thead tr').append('<th>' + key + '</th>');
                    });
                    // Append Table Data
                    for($i=0;$i<data.length;$i++){
                        var $row = $('tr.clone').clone().removeClass('clone hidden').addClass('show');
                        $.each(data[$i], function(key, value){
                            $row.append('<td>' + value + '</td>');
                        });
                        $('tbody').append($row);
                    }
                }
            }, error:function(error){
                console.log(error);
                alert("Error: " + error.responseText);
            }
        });
    });

    // Export Activity Schedule
    $('#export-activity-table').click(function(e){
        e.preventDefault();

        var $headers = [];
        var $tableData = [];
        var $teamIDs = [];

        $('thead tr th').each(function(){
            $headers.push($(this).text());
        });

        $('tr.show').each(function(i){
            $tableData[i] = [];
            $(this).children('td').each(function(ii){
                $tableData[i][ii] = $(this).text();
            });
        });

        $('select[name="teamid"] option:gt(0)').each(function(){
            $teamIDs.push($(this).val());
        });

        $.ajax({
            type: "POST",
            url: 'https://dash-delagua.com/da-forms/activity-schedule/export-table.php',
            data: {'headers':$headers, 'tableData':$tableData, 'teamIDs':$teamIDs},
            dataType: 'JSON',
            success: function(data) {
                console.log(data);
                window.open('https://dash-delagua.com/da-forms/activity-schedule/' + data + '?' + Math.random());
            }, error:function(error){
                console.log(error);
                alert("Error: " + error.responseText);
            }
        });
    });

    // BENNIE UPLOADS
    // Edit Button
    $('.page-id-183').on('click', '#table_1_ok_edit_dialog', function(e){
        e.preventDefault();

        var $bluid = $('.selected td.column-bluid').text();
        var $status = $('#table_1_Status').val();
        var $filename = $('.selected td.column-filename').text();

        $.ajax({
            type: "POST",
            url: 'https://dash-delagua.com/da-forms/beneficiary-adder/bennie-uploads.php',
            data: {'bluid':$bluid, 'status':$status, 'filename':$filename},
            success: function(data) {
                console.log(data);
                alert(data);
                $('#wdt-frontend-modal').css('display', 'none');
                $('div.modal-backdrop').remove();
                location.reload();
            }, error:function(error){
                console.log(error);
                alert("Error: " + error.responseText);
            }
        });
    });

    // COUNTRY ADDER
    $('#add-boundary').click(function(e){
        e.preventDefault();

        var $clone = $('.clone');
        $clone.clone().removeClass('clone hidden').addClass('show').appendTo('.boundaries');

        var $index = $('.boundaries .show').length + 1;
        $('.show:last-child').find('label').text('Level ' + $index);
    });

    // WAREHOUSE ADDER
    $('#warehouse-adder').on('change', 'select#wh-type', function(e){
        e.preventDefault();

        var $type = $(this).val();

        if($type == 'Other'){
            $('.describe').addClass('show');
        } else {
            $('.describe').removeClass('show');
        }
    });

    // TID ADDER
    function selectWarehouse(){
        $('.central .alert').removeClass('show');
        $('#outbound-wh').find('option:gt(0)').remove();

        var $central = $('select#central').val();
        var $country = $('select.country').val();  

        if($central == 'No'){
            $('#tidadder').addClass('show-regions');
            // $('.central .alert').addClass('show');
        } else {
            $('#tidadder').removeClass('show-regions');
            $('.region:gt(1)').remove();
        } 

        for($i=($('.region').length-1); $i>0; $i--){
            $gid = $('.region select').eq($i).val();

            if($gid){
                break;
            }
        }

        console.log($central, $country, $gid);

        $.ajax({
            type: "POST",
            url: 'https://dash-delagua.com/da-forms/tid-adder/select-warehouse.php',
            data: {'central':$central, 'country':$country, 'gid':$gid},
            dataType: 'JSON',
            success: function(data) {
                console.log(data);
                if(data){
                    for($i=0;$i<data.length;$i++){
                        $('#outbound-wh').append('<option value="' + data[$i]['WHID'] + '">' + data[$i]['WarehouseName'] + '</option>');
                    }
                }
            }, error:function(error){
                console.log(error);
                alert("Error: " + error.responseText);
            }
        });
    }

    $('#tidadder').on('change', 'select#central', selectWarehouse);
    $('#tidadder').on('change', '.region select', selectWarehouse);

    $('input[name="numtrucks"]').on('change', function(){
        var $minval = parseInt($(this).attr('min'));
        var $maxval = parseInt($(this).attr('max'));
        var $value = parseInt($(this).val());

        if($value > $maxval){
            $(this).val($maxval);
        } else if($value < $minval){
            $(this).val($minval);
        }
    });

    // Carbon Project Adder
    $('#group-project').on('change', function(e){
        e.preventDefault();

        var $group = $(this).val();
        var $country = $('select[name="country"]').val();
        var $investor = $('select[name="investor"]').val();

        if($group == 'Yes'){
            $('.da-form-item.hidden').addClass('show');
            if($country != 0){
                $('select[name="instance-country"]').val($country);
            }
            if($investor != 0){
                $('select[name="instance-investor"]').val($investor);
            }
        } else {
            $('.da-form-item.hidden').removeClass('show');
        }
    });

    // Region Selection
    $('body').on('change', '.region select', function(e){
        var $form = $(this).closest('form');
        var $formID = '#' + $form.attr('id');
        var $parentID = $(this).val();
        var $regionLength = $form.find('.region').length - 1;
        var $index = $(this).parent().index($formID + ' .region');

        // Delivery Note Conditionals
        if($('body').hasClass('page-id-72')){
            if($(this).hasClass('sector')){
                $('.region:gt(' + ($index) + ')').remove();
                getSubRegions($parentID, 'Storage Cell', $formID);
                getSubRegions($parentID, 'Distribution Cell', $formID);
                return;
            } else if($(this).hasClass('cell')){
                return;
            }
        }

        // NEW Delivery Note Conditionals
        if($('body').hasClass('page-id-282')){
            var $distrRegion = $('.selected .id-distrregion').text().toLowerCase();
            var $lastRegion = $formID + ' .da-form-item.region:last';
            var $distrIndex = $('.selected td.' + $distrRegion).index();
            var $stoppingPoint = $('.selected td').eq($distrIndex - 1).attr('class');

            if( $($lastRegion + ' select').hasClass($stoppingPoint) ){
                getSubRegions($parentID, 'Storage ' + $distrRegion, $formID);
                getSubRegions($parentID, 'Distribution ' + $distrRegion, $formID);
                return;
            } else if($(this).hasClass('cell')){
                return;
            }
        }

        // CHW Adder Template
        if($formID == '#chw-popup-form'){
            var $stoppingPoint = $('input[name="workregion-chw"]').val();
            if($(this).hasClass($stoppingPoint)){
                return;
            }
        }

        // Activity Schedule
        if($form.hasClass('distrregion')){
            var $stoppingPoint = $('input[name="distrregion"]').val();

            if($(this).hasClass('country')){
                // Get Distribution Region
                $.ajax({
                    type: "POST",
                    url: 'https://dash-delagua.com/da-forms/activity-schedule/get-distrregion.php',
                    data: {'country':$parentID},
                    dataType: 'JSON',
                    success: function(data){
                        data ? $('input[name="distrregion"]').val(data.toLowerCase()) : $('input[name="distrregion"]').val('');
                    }
                });

                if($formID == '#activity-schedule'){
                    // Get Team IDs
                    $.ajax({
                        type: "POST",
                        url: 'https://dash-delagua.com/da-forms/activity-schedule/get-teamids.php',
                        data: {'country':$parentID},
                        dataType: 'JSON',
                        success: function(data){
                            if(!data){
                                return;
                            }
                            // Insert options into select
                            for($i=0;$i<data.length;$i++){
                                var $id = data[$i];

                                $('#teamid').append('<option value="' + $id + '">' + $id + '</option>');
                            }
                        }
                    });
                }

            } else if($(this).hasClass($stoppingPoint)){
                return;
            }
        }

        if($index < $regionLength){
            $($formID + ' .da-form-item.region:gt(' + ($index) + ')').remove();
        }

        getSubRegions($parentID, null, $formID);
    });

    function getSubRegions($parentID, $label, $formID){
        if(!$formID){
            console.log('Form does not have an ID.');
        }

        $.ajax({
            type: "POST",
            url: 'https://dash-delagua.com/da-forms/beneficiary-adder/get-subregions.php',
            data: {'parentID':$parentID},
            dataType: 'JSON',
            async: false,
            success: function(data){
                console.log(data);
                // if regions are returned
                if(data){
                    // duplicate region select
                    var $clone = $($formID + ' .region-clone').clone().removeClass('region-clone').addClass('show').insertAfter($formID + ' .da-form-item.region:last');

                    // Update label and class to RegionType
                    var $type = data[0]['type'];
                    $label ? $clone.find('label').text($label) : $clone.find('label').text($type);
                    $clone.find('select').addClass($type.toLowerCase());

                    // Insert options into select
                    for($i=0;$i<data.length;$i++){
                        var $id = data[$i]['id'];
                        var $name = data[$i]['name'];

                        $($formID + ' .da-form-item.region:last select').append('<option value="' + $id + '">' + $name + '</option>');
                    }
                }
            }
        });
    }

});