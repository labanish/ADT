<?php
foreach ($results as $result) {
    
}
?>
<!DOCTYPE html>
<html lang="en" >
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <style>
            table#drugs_table input{
                font-size:0.8em;
                width:100%;
            }
            table#drugs_table select{
                font-size:0.8em;
            }
            .icondose {
                background: #FFFFFF url(../../assets/images/dropdown.png) no-repeat 95% 4px;        
            }
            label.checkbox.inline{
                float:left;
            }
            .ui-dialog .ui-dialog-content {
                zoom:0.8;

            }
            .button_size{
                height:38px; font-size:15px; font-weight: bold;
            }
            @media screen{
                .modal-footer{
                    height: 20px;
                    padding: 5px 15px 15px;
                }
                .btn{
                    margin: 0;
                }
            } 
            .table-bordered input{
                width:auto;
            }  
            #tbl_printer input,#tbl_printer textarea{
                font-weight:bold;
            }     
            .table th, .table td{
                border-top: none;
            } 
            #tbl_printer tr.odd{
                background-color: aliceblue;
            }
            .ui-dialog{
                position:fixed;
            }
        </style>
        <script src="<?php echo base_url(); ?>assets/scripts/jquery.validate.min.js"></script>

        <script type="text/javascript">
            $(document).ready(function() {
                $('#removebtn').css('display: inline');
                showFirstRemove();
                show_remove_button();
                patient_ccc ='<?php echo $result['patient_number_ccc']; ?>';
                //Ask if patient is still pregnant
                var pregnant_status = '<?php echo $result['pregnant']; ?>';
                //Variable for qty error check
                alert_qty_check = true;
                if(pregnant_status=='1'){
                    bootbox.confirm("<h4>Pregnancy confirmation</h4>\n\<hr/><center>Is patient still pregnant?</center>","No", "Yes",
                    function(res){
                        if(res===false){//If answer is no, update pregnancy status
                            patient_ccc ='<?php echo $result['patient_number_ccc']; ?>';
                            //Check if the current regimen is OI Medicine and if not, hide the indication field
                            var _url = "<?php echo base_url() . 'patient_management/updatePregnancyStatus'; ?>";
                            //Get drugs
                            var request = $.ajax({
                                url: _url,
                                type: 'post',
                                data: {"patient_ccc": patient_ccc},
                                dataType: "json"
                            });
                        }
                    });
                }
                //ask if patient is still having tb
                var tb_status = '<?php echo $result['tb']; ?>';
                if(tb_status=='1'){
                    bootbox.confirm("<h4>TB confirmation</h4>\n\<hr/><center>Is patient still having TB?</center>","No", "Yes",
                    function(res){
                        if(res===false){//If answer is no, update tbstatus
                            patient_ccc ='<?php echo $result['patient_number_ccc']; ?>';
                            var _url = "<?php echo base_url() . 'patient_management/update_tb_status'; ?>";
                            //Get drugs
                            var request = $.ajax({
                                url: _url,
                                type: 'post',
                                data: {"patient_ccc": patient_ccc},
                                dataType: "json"
                            });
                        }
                    });
                }
                
                routine_check=0;
                
                $('form input,select,readonly').keydown(function(e) {
                    if (e.keyCode == 13) {
                        var inputs = $(this).parents("form").eq(0).find(":input");
                        if (inputs[inputs.index(this) + 1] != null) {
                            inputs[inputs.index(this) + 1].focus();
                        }
                        e.preventDefault();
                        return false;
                    }
                });

                //If facility has many dispensing points,stock type is selected dispensing point, otherwise, use Main pharmacy
                if ($("#ccc_store_id ").is(":visible")) {
                    stock_type = $("#ccc_store_id ").val();
                    stock_type_text = $("#ccc_store_id option:selected").text();
                }
                else {
                    stock_type = "2";
                    stock_type_text = 'main pharmacy';
                }

                $("#selectall").attr("checked", false);
                //function for select all
                $("#selectall").on('click', function() {
                    var status = $(this).is(":checked");
                    if (status == true) {
                        //check all
                        $(".label_checker").attr("checked", true);
                        $(".label_checker").val(1);
                    } else {
                        //uncheck all
                        $(".label_checker").attr("checked", false);
                        $(".label_checker").val(0);
                    }
                });

                //validate days to next appointment and date of next appointment
                $("input,select").on('change', function(i, v) {
                    var value = $(this).val();
                    var id = this.id;
                    if (value != '') {
                        if (id == "days_to_next") {
                            $('#next_appointment_date').validationEngine('hide');
                        } else if (id == "next_appointment_date") {
                            $('#days_to_next').validationEngine('hide');
                        }
                        $('#' + id).validationEngine('hide');
                    }
                });

                //Attach date picker for date of dispensing
                $("#dispensing_date").datepicker({
                    yearRange: "-120:+0",
                    maxDate: "0D",
                    dateFormat: $.datepicker.ATOM,
                    changeMonth: true,
                    changeYear: true
                });
                $("#dispensing_date").datepicker();
                $("#dispensing_date").datepicker("setDate", new Date());


                //function for changing dispensing date that checks if it matches last visit date
                $("#dispensing_date").on('change', function() {
                    var dispensing_date = $(this).val();
                    var last_visit_date = $("#last_visit").val();
                    if (last_visit_date) {
                        //check if dispensing date is equal to last visit date
                        if (last_visit_date == dispensing_date) {
                            //if equal ask for alert
                            bootbox.alert("<h4>Notice!</h4>\n\<center>You have dispensed drugs to this patient!</center>");
                        }
                    }
                    //calculate adherence
                    getAdherenceRate();
                });

           

                //drug label modal settings
                $("#open_print_label").dialog({
                    width: '1000',
                    modal: true,
                    height: '600',
                    autoOpen: false,
                    show: 'fold',
                    buttons: {
                        "Print": function() {
                            $("#print_frm").submit(); //Submit  the FORM
                        },
                        Cancel: function() {
                            $(this).dialog("close");
                        }
                    }
                });
                //print frm submit event
                $("#print_frm").on('submit', function(e) {
                    var postData = $('form#print_frm').serialize();
                    var formURL = $(this).attr("action");
                    $.ajax({
                        url: formURL,
                        type: "POST",
                        data: postData,
                        success: function(data, textStatus, jqXHR)
                        {
                          //data: return data from server
                          if (data == 0) {
                            bootbox.alert("<h4>Notice!</h4>\n\<center>You have not selected any drug label to print!</center>");
                          } else {
                            window.open(data);
                            $("#open_print_label").dialog("close");
                          }
                        }
                       });
                    //STOP default action
                    e.preventDefault(); 
                });

                //print drug labels functions
                $('#print_btn').on('click', function() {
                    $("#open_print_label").dialog("open");
                    $("#selectall").attr("checked", false);
                    var label_str = '<table id="tbl_printer" class="table table-condensed table-hover"><tbody>';

                    var _class = '';
                    $("#drugs_table > tbody > tr").each(function(i, v) {
                        if ((i + 1) % 2 == 0) {
                            _class = 'even';
                        } else {
                            _class = 'odd';
                        }
                        var row = $(v);
                        var drug_id = row.closest("tr").find(".drug").val();
                        if (drug_id != null) {
                            var drug_name = row.closest("tr").find(".drug option[value='" + drug_id + "']").text();
                            var drug_unit = row.closest("tr").find(".unit").val();
                            var expiry_date = row.closest("tr").find(".expiry").val();
                            var val = row.closest("tr").find('#doselist').val();
                            var dose_value = row.closest("tr").find('.dose option').filter(function() {
                                return this.value == val;
                            }).data('dose_val');
                            var dose_frequency = row.closest("tr").find('.dose option').filter(function() {
                                return this.value == val;
                            }).data('dose_freq');
                            var duration = row.find(".duration").val();
                            var qty = row.find(".qty_disp ").val();
                            var dose_hours = (24 / (dose_value * dose_frequency));
                            var patient_name = $('#patient_details').val();

                            //get instructions
                            var base_url = "<?php echo base_url(); ?>";
                            var link = base_url + "dispensement_management/getInstructions/" + drug_id;
                            $.ajax({
                                url: link,
                                async: false,
                                type: 'POST',
                                success: function(data) {
                                    var s = data;
                                    s = s.replace(/(^\s*)|(\s*$)/gi, "");
                                    s = s.replace(/[ ]{2,}/gi, " ");
                                    drug_instructions = s.replace(/\n /, "\n");
                                    //append data
                                    label_str += '<tr class="' + _class + '">\
                                                    <td class="span1 ">\
                                                    <label class="checkbox inline">\
                                                    <input type="checkbox" name="print_check[' + i + ']" class="label_checker" value="0"/>\
                                                    </label>\
                                                    </td>\
                                                    <td class="span10">\
                                                    <div class="row-fluid">\
                                                      <div class="span9">\
                                                       <label class="inline">\
                                                       Drugname:\
                                                       <input type="text" name="print_drug_name[]" class="span9 label_drug" value="' + drug_name + '" required readonly/>\
                                                       </label>\
                                                      </div>\
                                                      <div class="span3">\
                                                      <label class="inline">\
                                                       Qty:\
                                                      <input type="number" name="print_qty[]" class="span3 label_qty" value="' + qty + '" required readonly/>\
                                                      </label>\
                                                      </div>\
                                                    </div>\
                                                    <div class="row-fluid">\
                                                     <div class="span12">\
                                                      <label class="inline">\
                                                      Tablets/Capsules:\
                                                      <input type="number" name="print_dose_value[]" class="span1 label_dose_value" value="' + dose_value + '" required/> to be taken\
                                                      <input type="number" name="print_dose_frequency[]" class="span1 label_dose_frequency" value="' + dose_frequency + '" required/> times a day after every\
                                                      <input type="number" name="print_dose_hours[]" class="span1 label_hours" value="' + dose_hours + '" required/> hours\
                                                      </label>\
                                                     </div>\
                                                    </div>\
                                                    <div class="row-fluid">\
                                                     <div class="span12">\
                                                      <label class="inline">\
                                                      Before/After Meals:\
                                                      <textarea name="print_drug_info[]"  row="5" class="span8 label_info">' + drug_instructions + '</textarea>\
                                                      </label>\
                                                      </div>\
                                                    </div>\
                                                    <div class="row-fluid">\
                                                     <div class="span4">\
                                                        <label class="inline">\
                                                         Name: <input type="text" name="print_patient_name" class="span9 label_patient" value="' + patient_name + '" required readonly/>\
                                                        </label>\
                                                     </div>\
                                                     <div class="span4">\
                                                      <label class="inline">\
                                                        Pharmacy: <input type="text" name="print_pharmacy[]" class="span8 label_patient" value="Pharmacy"/>\
                                                       </label>\
                                                     </div>\
                                                     <div class="span4">\
                                                        <label class="inline">\
                                                        Date: <input type="text" name="print_date" class="span6 label_date" value="<?php echo date('d/m/Y'); ?>" readonly/>\
                                                        </label>\
                                                       </div>\
                                                    </div>\
                                                    <div class="row-fluid">\
                                                      <div class="span12">\
                                                          <label style="text-align:center;">Keep all medicines in a cold dry place out of reach of children.</label>\
                                                       </div>\
                                                     </div>\
                                                     <div class="row-fluid">\
                                                      <div class="span6">\
                                                        <label class="inline">\
                                                        Facility Name:<input type="text" name="print_facility_name" class="span8 label_facility" value="<?php echo $this->session->userdata("facility_name"); ?>" readonly/>\
                                                        </label>\
                                                      </div>\
                                                      <div class="span6">\
                                                        <label class="inline">\
                                                        Facility Phone:<input type="text" name="print_facility_phone" class="span4 label_contact" value="<?php echo $this->session->userdata("facility_phone"); ?>" readonly/>\
                                                        </label>\
                                                      </div>\
                                                     </div>\
                                                    </td>\
                                                    <td class="span1">\
                                                    <label class="inline">\
                                                    No. of Labels\
                                                    <input name="print_count[]" class="span1" style="height: 2em;" type="number" value="1">\
                                                    </label>\
                                                    </td>\
                                                    </tr>';
                                }
                            });
                        } else {
                            $("#open_print_label").dialog("close");
                        }
                    });
                    //remove all
                    $('.label_selectall').nextAll().remove();
                    //insertafter
                    label_str += '</tbody></table></div>';
                    $(label_str).insertAfter(".label_selectall");

                    //function to select individual
                    $(".label_checker").on('click', function() {
                        cb = $(this);
                        cb.val(cb.prop('checked'));
                    });
                });

                <?php $this->session->set_userdata('record_no', $result['id']); ?>
                $("#patient").val("<?php echo $result['patient_number_ccc']; ?>");
                var first_name = "<?php echo strtoupper($result['first_name']); ?>";
                var other_name = "<?php echo strtoupper($result['other_name']); ?>";
                var last_name = "<?php echo strtoupper($result['last_name']); ?>";
                $("#patient_details").val(first_name + " " + other_name + " " + last_name);
                $("#height").val("<?php echo $result['height']; ?>");
                <?php
                if ($last_regimens) {
                ?>
                    $("#last_regimen_disp").val("<?php echo $last_regimens['regimen_code'] . " | " . $last_regimens['regimen_desc']; ?>");
                    $("#last_regimen").val("<?php echo $last_regimens['id']; ?>");
                <?php
                }
                ?>

                var last_visit_date = "<?php echo @$last_regimens['dispensing_date']; ?>";
                if (last_visit_date) {
                    $("#last_visit").val(last_visit_date);
                    var last_visit = last_visit_date;
                    var last_visit_date = "<?php echo date('d-M-Y', strtotime(@$last_regimens['dispensing_date'])); ?>";
                    $("#last_visit_date").attr("value", last_visit_date);
                    //check if dispensing date is equal to last visit date
                    var dispensing_date = $("#dispensing_date").val();
                    if (dispensing_date == last_visit) {
                        //if equal ask for alert
                        bootbox.alert("<h3>Notice!</h3>\n\<hr/><center>You have dispensed drugs to this patient!</center>");
                    }
                }

                //Get Prev Appointment
                <?php
                if ($appointments) {
                ?>
                    var today = new Date();
                    var appointment_date = $.datepicker.parseDate('yy-mm-dd', "<?php echo $appointments['appointment']; ?>");
                    var timeDiff = today.getTime() - appointment_date.getTime();
                    var diffDays = Math.floor(timeDiff / (1000 * 3600 * 24));
                    if (diffDays > 0) {
                        var html = "<span style='color:#ED5D3B;'>Late by <b>" + diffDays + "</b> days.</span>";
                    } else {
                        var html = "<span style='color:#009905'>Not Due for <b>" + Math.abs(diffDays) + "</b> days.</span>";
                    }

                    $("#days_late").append(html);
                    $("#days_count").attr("value", diffDays);
                    $("#last_appointment_date").attr("value", "<?php echo @$appointments['appointment']; ?>");
                    $("#last_appointment_date").attr("value", "<?php echo date('d-M-Y', strtotime(@$appointments['appointment'])); ?>");
                <?php
                }
                ?>
                $("input,select").on('change', function(i, v) {
                    var value = $(this).val();
                    var id = this.id;
                    if (value != '') {
                        if (id == "days_to_next") {
                            $('#next_appointment_date').validationEngine('hide');
                        } else if (id == "next_appointment_date") {
                            $('#days_to_next').validationEngine('hide');
                        }
                        $('#' + id).validationEngine('hide');
                    }
                });

                //Attach date picker for date of dispensing
                $("#dispensing_date").datepicker({
                    yearRange: "-120:+0",
                    maxDate: "0D",
                    dateFormat: $.datepicker.ATOM,
                    changeMonth: true,
                    changeYear: true
                });
                $("#dispensing_date").datepicker();
                $("#dispensing_date").datepicker("setDate", new Date());

                //Add listener to check purpose
                $("#purpose").change(function() {
                    //reset drug tables
                    //resetRoutineDrugs();
                  //  var regimen = $("#current_regimen option:selected").attr("value");
                   // var last_regimen = $("#last_regimen").attr("value");
                    purpose_visit = $("#purpose :selected").text().toLowerCase();
                    //If purpose of visit is not switch regimen, current regimen is last regimen
                    if (purpose_visit === 'switch regimen' ||  purpose_visit === '--select one--') {
                        $("#current_regimen").val("0");
                    } else {
                        $("#current_regimen").val(last_regimen);
                        //Populate drugs by triggering change event
                        $("#current_regimen").trigger("change");
                        $("#purpose_refill_text").val('');
                        
                        //If purpose is Start ART, check if patient has WHO stage
                        if(purpose_visit === 'start art'){
                            $("#current_regimen").val("0");
                            $("#purpose_refill_text").val(purpose_visit);
                            var _url = "<?php echo base_url() . 'patient_management/getWhoStage'; ?>";
                            //Get drugs
                            var request = $.ajax({
                                url: _url,
                                type: 'post',
                                data: {"patient_ccc": patient_ccc},
                                dataType: "json"
                            });
                            request.done(function(data) {
                                if(data.patient_who==0){//If no WHO Stage, prompt to enter it
                                        var length_who = data.who_stage.length;
                                        length_who = length_who-1;
                                        var select_who ="<select id='who_stage' name='who_stage'>";  
                                        $.each(data.who_stage,function(i,v){
                                            select_who+="<option value='"+data.who_stage[i]['id']+"'>"+data.who_stage[i]['name']+"</option>";
                                            if(length_who==i){
                                                select_who+='</select>';
                                                bootbox.confirm("<h4>WHO Stage </h4>\n\<hr/><center>Patient does not have a WHO Stage, Please select one "+select_who+"</center>","Cancel", "Save",
                                                function(res){
                                                    if(res===true){//If answer is no, update pregnancy status
                                                        var who_selected = $('#who_stage').val();
                                                        //Check if the current regimen is OI Medicine and if not, hide the indication field
                                                        var _url = "<?php echo base_url() . 'patient_management/updateWhoStage'; ?>";
                                                        //Get drugs
                                                        var request = $.ajax({
                                                            url: _url,
                                                            type: 'post',
                                                            data: {"patient_ccc": patient_ccc,"who_stage": who_selected},
                                                            dataType: "json"
                                                        });
                                                    }
                                                });
                                            }
                                        });
                                        
                                }
                            });
                            request.fail(function(jqXHR, textStatus) {
                                bootbox.alert("<h4>Who Error </h4>\n\<hr/>\n\<center>Could not retrieve Who information : </center>" + textStatus);
                            });
                        }else{
                        	//Check is dispensing point was selected
                        	
                        }
                    }

                    //adherence rate
                    getAdherenceRate();

                });

                //Add datepicker for the next appointment date
                $("#next_appointment_date").datepicker({
                    changeMonth: true,
                    changeYear: true,
                    dateFormat: $.datepicker.ATOM,
                    onSelect: function(dateText, inst) {
                        var base_date = new Date();
                        var today = new Date(base_date.getFullYear(), base_date.getMonth(), base_date.getDate());
                        var today_timestamp = today.getTime();
                        var one_day = 1000 * 60 * 60 * 24;
                        var appointment_timestamp = $("#next_appointment_date").datepicker("getDate").getTime();
                        var difference = appointment_timestamp - today_timestamp;
                        var days_difference = difference / one_day;
                        $("#days_to_next").attr("value", days_difference);
                        retrieveAppointedPatients();
                    }
                });

                //Add listener to the 'days_to_next' field so that the date picker can reflect the correct number of days!
                $("#days_to_next").change(function() {
                
                    var days = $("#days_to_next").attr("value");
                    var base_date = new Date();
                    var appointment_date = $("#next_appointment_date");
                    var today = new Date(base_date.getFullYear(), base_date.getMonth(), base_date.getDate());
                    var today_timestamp = today.getTime();
                    var appointment_timestamp = (1000 * 60 * 60 * 24 * days) + today_timestamp;
                    appointment_date.datepicker("setDate", new Date(appointment_timestamp));
                    retrieveAppointedPatients();

                    //Loop through Table to calculate pill counts for all rows
                    $.each($(".drug"), function(i, v) {
                        var row = $(this);
                        var qty_disp = row.closest("tr").find(".qty_disp").val();
                        var dose_val = row.closest("tr").find(".dose option:selected").attr("dose_val");
                        var dose_freq = row.closest("tr").find(".dose option:selected").attr("dose_freq");
                    });
                });

                //Dynamically change the list of drugs once a current regimen is selected
                $("#current_regimen").change(function() {
					if($("#ccc_store_id").val()==""){//If dispensing point not selected, prompt user to select it first
						bootbox.alert("<h4>Dispensing point</h4>\n\<hr/>\n\<center>Please select a dispensing point first! </center>" );
						$("#reset").trigger("click");
						$("#ccc_store_id").css('border','solid 3px red');
						return;
					}
                    var selected_regimen = $(this).val();
                    //Check if the current regimen is OI Medicine and if not, hide the indication field
                    var _url = "<?php echo base_url() . 'dispensement_management/getDrugsRegimens'; ?>";
                    //Get drugs
                    var request = $.ajax({
                        url: _url,
                        type: 'post',
                        data: {"selected_regimen": selected_regimen, "stock_type": stock_type},
                        dataType: "json",
                        async: false
                    });
                    request.done(function(data) {
                        resetRoutineDrugs();
                        $(".drug option").remove();
                        $(".drug").append($("<option value='0'>-Select Drug-</option>"));
                        $.each(data, function(key, value) {
                            $(".drug").append($("<option value='" + value.id + "'>" + value.drug + "</option>"));
                        });
                    });
                    request.fail(function(jqXHR, textStatus) {
                        bootbox.alert("<h4>Drug Details Alert</h4>\n\<hr/>\n\<center>Could not retrieve drug details : </center>" + textStatus);
                    });


                    var regimen = $("#current_regimen option:selected").attr("value");
                    var last_regimen = $("#last_regimen").attr("value");

                    if (last_regimen != 0) {
                        if ($("#last_regimen_disp").val().toLowerCase().indexOf("oi") == -1) {
                            //contains oi
                            if (regimen != last_regimen) {
                                $("#regimen_change_reason_container").show();
                                $("#regimen_change_reason").addClass("validate[required]");
                            } else {
                                $("#regimen_change_reason").removeClass("validate[required]");
                                $("#regimen_change_reason_container").hide();
                                $("#regimen_change_reason").val("");

                                if(purpose_visit == 'routine refill'){
                                    routine_check=1;
                                    //append visits
                                    visits=<?php echo json_encode($visits); ?>;
                                    total_visits = (visits.length) -1;
                                    var count = 0;
                                    getRoutineDrugs(visits,total_visits,count); 
                                }
                            }
                        }
                    } else {
                        $("#regimen_change_reason_container").hide();
                        $("#regimen_change_reason").val("");
                    }
                });

                //store type change event
                $("#ccc_store_id").change(function() {
                	if($(this).val()!=''){
                		$("#ccc_store_id").css('border','none');
                	}else{
                		$("#ccc_store_id").css('border','solid 3px red');
                	}
                    stock_type = $("#ccc_store_id ").val();
                    stock_type_text = $("#ccc_store_id option:selected").text();
                    $("#stock_type_text").val(stock_type_text);
                    $("#current_regimen").trigger("change");
                    reinitialize();
                    storeSession($(this).val());
                });

                //drug change event
                $(".drug").change(function() {
                    var row = $(this);
                    var drug_name = row.find("option:selected").text();
                    resetFields(row);
                    row.closest("tr").find(".batch option").remove();
                  //  row.closest("tr").find(".batch").append($("<option value='0'>Loading ...</option>"));
                   // bootbox.alert(drug_name);
                    var row = $(this);
                    var selected_drug = $(this).val();
                    var patient_no = $("#patient").val();
                    var weighting = $("#weight").val();
                    var paed_dosage='';


                    //Check if patient allergic to selected drug
                    var _url = "<?php echo base_url() . 'dispensement_management/drugAllergies'; ?>";
                    var request = $.ajax({
                        url: _url,
                        type: 'post',
                        dataType: "json",
                        data: {
                            "selected_drug": selected_drug,
                            "patient_no": patient_no
                        }
                    });
                    request.done(function(data) {
                        //If patient is allergic to selected drug,alert user
                        if (data == 1) {
                            bootbox.alert("<h4>Allergy Alert!</h4>\n\<hr/><center>This patient is allergic to "+drug_name+"</center>");
                            //Remove row
                            var rows=$("#drugs_table > tbody").find("tr").length;
                            if(rows > 1){
                                row.closest('tr').remove();  
                            }
                            else{
                                row.closest('tr').find(".drug").val(0);
                                row.closest('tr').find(".drug").trigger("change");
                            }
                        } else {
                            <?php
                            if ($prev_visit) {
                            ?>
                                var prev_visit_arr =<?php echo $prev_visit; ?>;
                                //Loop through prev_dispensing table and check with current drug selected if a match is found populate pill count
                                $.each(prev_visit_arr, function(i, v) {
                                    var prev_drug_id = v['drug_id'];
                                    var prev_drug_qty = v['mos'];
                                    var prev_qty = v['quantity'];
                                    var prev_date = v['dispensing_date'];
                                    var prev_value = v['value'];
                                    var prev_frequency = v['frequency'];
                                    if (v['pill_count'] != "") {
                                        var prev_pill_count = v['pill_count'];//Previous pill count will be used to calculate expected pill count
                                    } else {
                                        var prev_pill_count = 0;//Previous pill count will be used to calculate expected pill count 
                                    }

                                    //If drug was previously dispensed
                                    if (selected_drug == prev_drug_id) {
                                        var base_date = new Date();
                                        var today = new Date(base_date.getFullYear(), base_date.getMonth(), base_date.getDate());
                                        var today_timestamp = today.getTime();
                                        var one_day = 1000 * 60 * 60 * 24;
                                        var appointment_timestamp = Date.parse(prev_date);
                                        var difference = today_timestamp - appointment_timestamp;
                                        var days_difference = difference / one_day;
                                        if (days_difference > 0) {
                                            days_difference = days_difference.toFixed(0);
                                        } else {
                                            days_difference = 0;
                                        }

                                        var group_A = (prev_qty - prev_pill_count);
                                        var group_B = (days_difference * (prev_value * prev_frequency))
                                        var prev_drug_qty = (group_A - group_B);

                                        if (prev_drug_qty < 0) {
                                            prev_drug_qty = 0;
                                        }
                                        row.closest("tr").find(".pill_count").val(prev_drug_qty);
                                        return false;
                                    }
                                });
                            <?php
                             }
                            ?>

                            var dose = "";
                            //Get batches that have not yet expired and have stock balance
                            var _url = "<?php echo base_url() . 'inventory_management/getBacthes'; ?>";

                            var request = $.ajax({
                                url: _url,
                                type: 'post',
                                data: {"selected_drug": selected_drug, "stock_type": stock_type},
                                dataType: "json",
                                async: false
                            });
                            request.done(function(data) {
                                var url_dose = "<?php echo base_url() . 'dispensement_management/getDoses'; ?>";
                                //Get doses
                                var request_dose = $.ajax({
                                    url: url_dose,
                                    type: 'post',
                                    dataType: "json"
                                });
                                request_dose.done(function(data) {
                                    row.closest("tr").find(".dose option").remove();
                                    $.each(data, function(key, value) {
                                        row.closest("tr").find(".dose").append("<option value='" + value.Name + "'  data-dose_val='" + value.value + "' data-dose_freq='" + value.frequency + "' >" + value.Name + "</option> ");
                                    });
                                });

                                row.closest("tr").find(".batch option").remove();
                                row.closest("tr").find(".batch").append($("<option value='0'>Select</option>"));
                                $.each(data, function(key, value) {
                                    var _class='';
                                    if(routine_check==1){
                                        //used for generating automatic table rows for drugs                                         
                                        if(key==0){
                                            _class='selected';     
                                        }                                          
                                    }else{
                                        row.closest("tr").find(".duration").val(value.duration);
                                        row.closest("tr").find(".qty_disp").val(value.quantity); 
                                        row.closest("tr").find(".dose").val(value.dose); 
                                    }
                                    row.closest("tr").find(".unit").val(value.Name);
                                    row.closest("tr").find(".batch").append("<option "+_class+" value='" + value.batch_number + "'>" + value.batch_number + "</option> ");
                                    row.closest("tr").find(".comment").val(value.comment);
                                    dose = value.dose;
                               
 //Dosage for Paediatrics

        if (selected_drug=='12'){

            if (weighting>=3 && weighting<=5.9){

                paed_dosage="1 tab";
                //row.closest("tr").find(".comment").append($(paed_dosage));
                
            }else if (weighting>=6 && weighting<=9.9){
                paed_dosage="1.5 tab";
            }else if (weighting>=10 && weighting<=13.9){
                paed_dosage="2 tab";
            }else if (weighting>=14 && weighting<=19.9){
                paed_dosage="2.5 tab";
            }else if (weighting>=20 && weighting<=24.9){
                paed_dosage="3 tab";
            }else if (weighting>=25 && weighting<=34.9){
                paed_dosage="300 +500mg";
            }
           // bootbox.alert("The Paediatrics Dosage equals to "+ paed_dosage);
            //row.closest("tr").find(".qty_disp").val(paed_dosage);
            row.closest("tr").find(".dose").val("");
            row.closest("tr").find(".dose").val(paed_dosage);
            
            
        }
         });



                                //Get brands
                                var new_url = "<?php echo base_url() . 'dispensement_management/getBrands'; ?>";
                                var request_brand = $.ajax({
                                    url: new_url,
                                    type: 'post',
                                    data: {"selected_drug": selected_drug},
                                    dataType: "json"
                                });
                                request_brand.done(function(data) {
                                    row.closest("tr").find(".brand option").remove();
                                    row.closest("tr").find(".brand").append("<option value='0'>None</option> ");
                                    $.each(data, function(key, value) {
                                        row.closest("tr").find(".brand").append("<option value='" + value.id + "'>" + value.brand + "</option> ");
                                    });

                                });
                                request_brand.fail(function(jqXHR, textStatus) {
                                    boottbox.alert("<h4>Brands Notice</h4>\n\<hr/><center>Could not retrieve the list of brands :</center> " + textStatus);
                                });

                                //Get indications(opportunistic infections)
                                var url_indication = "<?php echo base_url() . 'dispensement_management/getIndications'; ?>";
                                var request_dose = $.ajax({
                                    url: url_indication,
                                    type: 'post',
                                    dataType: "json",
                                    data:{
                                        "drug_id":selected_drug
                                    }
                                });
                                request_dose.done(function(data) {
                                    row.closest("tr").find(".indication option").remove();
                                    row.closest("tr").find(".indication").append("<option value='0'>None</option> ");
                                    // Check if regimen selected is OI so as to display indication
                                    $.each(data, function(key, value) {
                                        row.closest("tr").find(".indication").append("<option value='" + value.Indication + "'>" + value.Indication + " | " + value.Name + "</option> ");
                                    });
                                });

                            });
                            request.fail(function(jqXHR, textStatus) {
                                bootbox.alert("<h4>Indication Alert</h4>\n\<hr/><center>Could not retrieve the list of batches : </center>" + textStatus);
                            });
                            
                            //trigger batch changes
                            row.closest("tr").find(".batch").trigger("change");
                        }

                    });
                });

                //batch change event
                $(".batch").change(function() {
                    if ($(this).prop("selectedIndex") > 1) {
                        bootbox.alert("<h4>Expired Batch</h4>\
                                                    <hr/>\n\
                                                <center>This is not the first expiring batch</center>");
                    }
                    var row = $(this);
                    //Get batch details(balance,expiry date)
                    if ($(this).val() != 0) {
                        var batch_selected = $(this).val();
                        var selected_drug = row.closest("tr").find(".drug").val();
                        var _url = "<?php echo base_url() . 'inventory_management/getBacthDetails'; ?>";
                        var request = $.ajax({
                            url: _url,
                            type: 'post',
                            data: {"selected_drug": selected_drug, "stock_type": stock_type, "batch_selected": batch_selected},
                            dataType: "json"
                        });
                        request.done(function(data) {
                            row.closest("tr").find(".expiry").val(data[0].expiry_date);
                            row.closest("tr").find(".soh ").val(data[0].balance);   
                            $(".qty_disp").trigger('keyup',[row]);  
                        });
                        request.fail(function(jqXHR, textStatus) {
                            bootbox.alert("<h4>Batch Details Alert</h4>\n\<hr/><center>Could not retrieve batch details : </center>" + textStatus);
                        });
                    }
                });
                //quantity disepensed change event
                $(".qty_disp").keyup(function(event,current_row) {
                    if (typeof current_row !== "undefined" && current_row){//Check if current_row parameter was passed
                      row = current_row;
                    }else{//when not triggering the keyup event
                        var row = $(this);
                        alert_qty_check = true;
                    }
                    var selected_value = $(this).attr("value");
                    var stock_at_hand = row.closest("tr").find(".soh ").attr("value");
                    var stock_validity = stock_at_hand - selected_value;
                    
                    if (stock_validity < 0) {
                        if(alert_qty_check==true){//Check to only show the error message once
                            bootbox.alert("<h4>Quantity-Stock Alert</h4>\n\<hr/><center>Quantity Cannot Be larger Than Stock at Hand</center>");
                        }
                        row.closest("tr").find(".qty_disp").css("background-color","red");
                        row.closest("tr").find(".qty_disp").addClass("input_error");
                        alert_qty_check=false;
                    }
                    else {
                        row.closest("tr").find(".qty_disp").css("background-color", "white");
                        row.closest("tr").find(".qty_disp").removeClass("input_error");
                    }

                });

                //next pill count change event
                $(".next_pill").change(function() {
                    var row = $(this);
                    var qty_disp = row.closest("tr").find(".qty_disp").val();
                    var dose_val = row.closest("tr").find(".dose option:selected").attr("dose_val");
                    var dose_freq = row.closest("tr").find(".dose option:selected").attr("dose_freq");
                });

                //function to calculate qty_dispensed based on dosage and duration
                $(".duration").on('keyup', function() {
                    var row = $(this);
                    var duration = $(this).val();
                    var val = row.closest("tr").find('#doselist').val();
                    var dose_val = row.closest("tr").find('.dose option').filter(function() {
                        return this.value == val;
                    }).data('dose_val');
                    var dose_freq = row.closest("tr").find('.dose option').filter(function() {
                        return this.value == val;
                    }).data('dose_freq');
                    //formula(duration*dose_value*dose_frequency)
                    var qty_disp = duration * dose_val * dose_freq;
                    row.closest("tr").find(".qty_disp").val(qty_disp);
                    alert_qty_check = true;
                    $(".qty_disp").trigger('keyup',[row]);
                });

                //function to add drug row in table 
                $(".add").click(function() {
                    routine_check=0;
                    var last_row = $('#drugs_table tr:last');
                    var drug_selected = last_row.find(".drug").val();
                    var quantity_entered = last_row.find(".qty_disp").val();
                    if (last_row.find(".qty_disp").hasClass("input_error")) {
                        bootbox.alert("<h4>Excess Quantity Alert</h4>\n\<hr/><center>Error !Quantity dispensed is greater than qty available!</center>");
                    }

                    else if (drug_selected == 0) {
                        bootbox.alert("<h4>Drug Alert</h4>\n\<hr/><center>You have not selected a drug!</center>");
                    }
                    else if (quantity_entered == "" || quantity_entered == 0) {
                        bootbox.alert("<h4>Quantity Alert</h4>\n\<hr/><center>You have not entered any quantity!</center>");
                    }
                    else {
                        var cloned_object = $('#drugs_table tr:last').clone(true);
                        var drug_row = cloned_object.attr("drug_row");
                        var next_drug_row = parseInt(drug_row) + 1;
                        var row_element = cloned_object;

                        //Second thing, retrieve the respective containers in the row where the drug is
                        row_element.find(".unit").attr("value", "");
                        row_element.find(".batch").empty();
                        //Fixing the expiry date in dispensing
                        var expiry_id = "expiry_date_" + next_drug_row;
                        var expiry_date = row_element.find(".expiry").attr("value", "");
                        expiry_date.attr("id", expiry_id);
                        var expiry_selector = "#" + expiry_id;

                        $(expiry_selector).datepicker({
                            defaultDate: new Date(),
                            changeYear: true,
                            changeMonth: true
                        });

                        row_element.find(".dose").attr("value", "");
                        row_element.find(".duration").attr("value", "");
                        row_element.find(".qty_disp").attr("value", "");
                        row_element.find(".brand").attr("value", "");
                        row_element.find(".soh").attr("value", "");
                        row_element.find(".indication").attr("value", "");
                        row_element.find(".pill_count").attr("value", "");
                        row_element.find(".next_pill_count").attr("value", "");
                        row_element.find(".comment").attr("value", "");
                        row_element.find(".missed_pills").attr("value", "");
                        row_element.find(".missed_pills").removeAttr("readonly");
                        row_element.find(".remove").show();
                        cloned_object.attr("drug_row", next_drug_row);
                        cloned_object.insertAfter('#drugs_table tr:last');
                        showFirstRemove();

                        return false;
                    }

                });

                //function to remove drug row in table 
                $(".remove").click(function() {
                       var rows=$("#drugs_table > tbody").find("tr").length;
                       var rem_row=this;
                          if(rows> 1){
                            bootbox.confirm("<h4>Remove?</h4>\n\<hr/><center>Are you sure?</center>", function(res){
                                  if(res)
                                     $(rem_row).closest('tr').remove();  
                                  
                              });
                                                                       
                    }else{
                       bootbox.alert("<h4>Remove Alert!</h4>\n\<hr/><center>Error!Cannot Delete Last Row Try Reset!</center>");
                    }
                });
                $("#reset").click(function (e){
                    e.preventDefault();
                    reinitialize();
                    clearForm("#dispense_form");
                    resetRoutineDrugs();
                });
                function resetFields(row) {
                    row.closest("tr").find(".qty_disp").val("");
                    row.closest("tr").find(".soh").val("");
                    //row.closest("tr").find(".indication").val("");
                    row.closest("tr").find(".duration").val("");
                    row.closest("tr").find(".expiry").val("");
                    row.closest("tr").find(".pill_count").val("");
                    row.closest("tr").find(".missed_pills").val("");
                }

                function retrieveAppointedPatients() {
                    $("#scheduled_patients").html("");
                    $('#scheduled_patients').hide();
                    //Function to Check Patient Number exists
                    var base_url = "<?php echo base_url(); ?>";
                    var appointment = $("#next_appointment_date").val();
                    var link = base_url + "patient_management/getAppointments/" + appointment;
                    $.ajax({
                        url: link,
                        type: 'POST',
                        dataType: 'json',
                        success: function(data) {
                            var all_appointments_link = "<a class='link' target='_blank' href='<?php echo base_url() . 'report_management/getScheduledPatients/'; ?>" + appointment + "/" + appointment + "' style='font-weight:bold;color:red;'>View appointments</a>";
                            var html = "Patients Scheduled on Date: <b>" + data[0].total_appointments + "</b> Patients" + all_appointments_link;
                            var new_date = new Date(appointment);
                            var formatted_date_day = new_date.getDay();
                            var days_of_week = ["Sunday", "Monday", "Tuseday", "Wednesday", "Thursday", "Friday", "Saturday"];
                            if (formatted_date_day == 6 || formatted_date_day == 0) {
                                bootbox.alert("<h4>Weekend Alert</h4>\n\<hr/><center>It will be on " + days_of_week[formatted_date_day] + " During the Weekend </center>");
                                if (parseInt(data[0].total_appointments) > parseInt(data[0].weekend_max)) {
                                    bootbox.alert("<h4>Excess Appointments</h4>\n\<hr/><center>Maximum Appointments for Weekend Reached</center>");
                                }
                            } else {
                                if (parseInt(data[0].total_appointments) > parseInt(data[0].weekday_max)) {
                                    bootbox.alert("<h4>Excess Appointments</h4>\n\<hr/><center>Maximum Appointments for Weekday Reached</center>");
                                }

                            }

                            $("#scheduled_patients").append(html);
                            $('#scheduled_patients').show();
                        }
                    });
                }

            });//end document ready

            //Function to validate required fields
            function processData(form) {
                  var form_selector = "#" + form;
                  var validated = $(form_selector).validationEngine('validate');
                    if(!validated) {
                        return false;
                    }else{
                        return saveData();      
                    }
            }

            //Function to post data to the server
            function saveData(){
                $("#btn_submit").attr("readonly","readonly");
                var facility="<?php echo $facility ?>";
                var timestamp = new Date().getTime();
                var user="<?php echo $user;?>";
                var all_rows=$('#tbl-dispensing-drugs>tbody>tr');
                var msg = '';
                
                //Loop through all rows to check values
                $.each(all_rows,function(i,v){
                    var last_row = $(this);
                    var drug_name = last_row.find(".drug option:selected").text();

                    if(last_row.find(".drug").val()==0){
                        msg+='There is no commodity selected<br/>';
                    }
                    if(last_row.find(".batch").val()==0){
                        msg+='<b>'+drug_name + '</b><br/> There is no batch for the commodity selected<br/>';
                    }
                    if(last_row.find(".qty_disp").val()==0 || last_row.find(".qty_disp").val()=="" || isNaN(last_row.find(".qty_disp").val())==true){
                        msg+='<b>'+drug_name + '</b><br/> You have not entered the quantity being dispensed for a commodity entered<br/>';
                    }
                    if(last_row.find(".qty_disp").hasClass("input_error")){
                        msg+='<b>'+drug_name + '</b><br/> There is a commodity that has a quantity greater than the quantity available<br/>';
                    }
                
                });

                //Show Bootbox
                if(msg !=''){
                   bootbox.alert("<h4>Alert!</h4>\n\<hr/><center>"+msg+"</center>");
                   return;
                }

                
                var rowCount = $('#tbl-dispensing-drugs>tbody tr').length;
                return true;
            }
            
            function clearForm(form) {
              // iterate over all of the inputs for the form
              // element that was passed in
              $(':input', form).each(function() {
                var type = this.type;
                var tag = this.tagName.toLowerCase(); // normalize case
                // it's ok to reset the value attr of text inputs,
                // password inputs, and textareas
                if (type == 'text' || type == 'password' || tag == 'textarea')
                if ( $('input').is('[readonly]') ) { 
                
                }else{
                    this.value = "";
                }
                // checkboxes and radios need to have their checked state cleared
                // but should *not* have their 'value' changed
                else if (type == 'checkbox' || type == 'radio')
                  this.checked = false;
                // select elements need to have their 'selectedIndex' property set to -1
                // (this works for both single and multiple select elements)
                else if (tag == 'select')
                    if($(this).attr('id')!='ccc_store_id'){
                        this.selectedIndex = -1;
                    } 
              });
            };

            function retrieveFormValues() {
                //This function loops the whole form and saves all the input, select, e.t.c. elements with their corresponding values in a javascript array for processing
                var dump = Array;
                $.each($("input, select, textarea"), function(i, v) {
                    var theTag = v.tagName;
                    var theElement = $(v);
                    var theValue = theElement.val();
                    if (theElement.attr('type') == "radio") {
                        var text = 'input:radio[name=' + theElement.attr('name') + ']:checked';
                        dump[theElement.attr("name")] = $(text).attr("value");
                    } else {
                        dump[theElement.attr("name")] = theElement.attr("value");
                    }
                });
                return dump;
            }

            function retrieveFormValues_Array(name) {
                var dump = new Array();
                var counter = 0;
                $.each($("input[name=" + name + "], select[name=" + name + "], select[name=" + name + "]"), function(i, v) {
                    var theTag = v.tagName;
                    var theElement = $(v);
                    var theValue = theElement.val();
                    dump[counter] = theElement.attr("value");
                    counter++;
                });
                return dump;
            }

            function getPillCount(dose_qty, dose_frequency, total_actual_drugs) {
                var days_issued = $("#days_to_next").val();
                var error_message = "";
                if (!days_issued) {
                    error_message += "Days to Next Appointment not Selected \r\n";
                }
                if (!dose_qty) {
                    error_message += "Dose has no Value \r\n";
                }
                if (!dose_frequency) {
                    error_message += "Dose has no Frequency \r\n";
                }
                if (!total_actual_drugs) {
                    error_message += "No Quantity to Dispense Selected \r\n";
                }
                if (error_message) {
                    // alert(error_message);
                } else {
                    var drugs_per_day = (dose_qty * dose_frequency);
                    var total_expected_drugs = (drugs_per_day * days_issued);
                    var pill_count = (total_actual_drugs - total_expected_drugs);
                    return pill_count;
                }
            }

            function getActualPillCount(days_issued, dose_qty, dose_frequency, prev_pill_count, prev_qty) {
                var error_message = "";
                if (!dose_qty) {
                    error_message += "Dose has no Value \r\n";
                }
                if (!dose_frequency) {
                    error_message += "Dose has no Frequency \r\n";
                }
                if (!prev_pill_count) {
                    error_message += "No Quantity to Dispense Selected \r\n";
                }
                if (error_message) {
                    //alert(error_message);
                } else {
                    var group_A = (prev_qty - prev_pill_count);
                    var group_B = (days_issued * (dose_qty * dose_frequency))
                    var pill_count = (group_A - group_B);
                    return pill_count;
                }
            }
            function reinitialize() {
                alert_qty_check =true;
                $(".unit").val('');
                $(".batch option").remove();
                $(".expiry").val('');
                $(".dose").val('');
                $(".dose option").remove();
                $("#doselist").val('');
                $(".pill_count ").val('');
                $(".next_pill_count ").val('');
                $(".duration").val('');
                $(".qty_disp ").val('');
                $(".soh").val('');
                $(".brand option").remove();
                $(".indication option").remove();
                $(".comment ").val('');
                $(".missed_pills ").val('');
                $(".qty_disp").css("background-color", "white");
                $(".qty_disp").removeClass("input_error");

            }
            //function generate routine drugs
            function getRoutineDrugs(visits,total_visits,count){
              //loop and add rows 
              for(var count=0;count<(visits.length-1);count++){
                 var row = $('#drugs_table tr:last');
                 var cloned_row = row.clone(true);
                 //show remove link for cloned row
                 cloned_row.find(".remove").show();
                 //insert after cloned row
                 cloned_row.insertAfter('#drugs_table tr:last');
              }

              //loop through rows on table and assign commodities
              var rows=$('#drugs_table tbody>tr');
              $.each(rows,function(i,v){
                var current_row=$(this);
                //select drug
                current_row.find(".drug").val(visits[i].drug_id);
                //trigger drug change event
                current_row.find(".drug").trigger("change");
                //add previous dose 
                current_row.find(".dose").val(visits[i].dose);
                //add previous duration
                current_row.find(".duration").val(visits[i].duration);
                //add previous quantity dispensed
                current_row.find(".qty_disp").val(visits[i].quantity);
                //$(".qty_disp").trigger('keyup',[current_row]);
              });
              //Make focus on first line drug
              $('#drugs_table tbody>tr:first').find('.duration').select();
              showFirstRemove();
              
            }
            //function reset routine drugs
            function resetRoutineDrugs(){
              //remove all table tr's except first one
              $("#drugs_table tbody").find('tr').slice(1).remove();
              var row = $('#drugs_table tr:last');
              //default options
              row.find(".unit").val("");
              row.find(".batch option").remove();
              row.find(".expiry").val("");
              row.find(".dose option").remove();
              row.find(".dose").val("");
              row.find(".pill_count").val("");
              row.find(".duration").val("");
              row.find(".qty_disp").val("");
              row.find(".soh").val("");
              row.find(".indication option").remove();
              routine_check=0;
              hideFirstRemove();
            }
            //old function to calculate adherence rate(%)
            function getAdherenceRate_old(){
                $("#adherence").attr("value", " ");
                $("#adherence").removeAttr("readonly");

                var purpose_of_visit = $("#purpose option:selected").val();
                var day_percentage = 0;

                if(purpose_of_visit.toLowerCase().indexOf("routine") == -1 || purpose_of_visit.toLowerCase().indexOf("pmtct") == -1) {
                    <?php
                     if ($appointments) {
                    ?>
                        var dispensing_date = $("#dispensing_date").val();
                        var today = $.datepicker.parseDate('yy-mm-dd', dispensing_date);
                        var appointment_date = $.datepicker.parseDate('yy-mm-dd', "<?php echo $appointments['appointment']; ?>");
                        var timeDiff = today.getTime() - appointment_date.getTime();
                        var days_count = Math.floor(timeDiff / (1000 * 3600 * 24));

                        if (days_count <= 0) {
                            day_percentage = "100%";
                        } else if (days_count > 0 && days_count <= 2) {
                            day_percentage = ">=95%";
                        } else if (days_count > 2 && days_count < 14) {
                            day_percentage = "84-94%";
                        } else if (days_count >= 14) {
                            day_percentage = "<85%";
                        }
                        $("#adherence").attr("value", day_percentage);
                        $("#adherence").attr("readonly", "readonly");
                    <?php
                      }
                    ?>
                }
            }
            //function to calculate adherence rate(%)
            function getAdherenceRate(){
                $("#adherence").attr("value", " ");
                $("#adherence").removeAttr("readonly");

                var purpose_of_visit = $("#purpose option:selected").val();
                var day_percentage = 0;

                if(purpose_of_visit.toLowerCase().indexOf("routine") == -1 || purpose_of_visit.toLowerCase().indexOf("pmtct") == -1) {
                    <?php
                     if ($appointments) {
                    ?>
                        var dispensing_date = $.datepicker.parseDate('yy-mm-dd', $("#dispensing_date").val());
                        var prev_visit_date = $.datepicker.parseDate('yy-mm-dd', "<?php echo @$last_regimens['dispensing_date']; ?>");
                        var appointment_date = $.datepicker.parseDate('yy-mm-dd', "<?php echo $appointments['appointment']; ?>");
                        
                        var days_to_next_appointment = Math.floor((prev_visit_date.getTime() - appointment_date.getTime()) / (1000 * 3600 * 24));
                        var days_missed_appointment = Math.floor((appointment_date.getTime() - dispensing_date.getTime()) / (1000 * 3600 * 24));
                                         
                        //Formula
                        day_percentage = ((days_to_next_appointment - days_missed_appointment) / days_to_next_appointment) * 100;
                        day_percentage = day_percentage.toFixed(2) + "%";
                        
                        $("#adherence").attr("value", day_percentage);
                        $("#adherence").attr("readonly", "readonly");
                    <?php
                      }
                    ?>
                }
            }


            function showFirstRemove(){
              $("#drugs_table tbody > tr:first").find(".remove").show();
            }
            function show_remove_button()
            {
                $('#drug_table tbody > tr: first').find("#removebtn").show();
            }
            function hideFirstRemove(){
              //$("#drugs_table tbody > tr:first").find(".remove").hide();
            }

            function storeSession(ccc_id){
                var url = "<?php echo base_url().'dispensement_management/save_session'; ?>"
                $.post(url,{'session_name':'ccc_store_id','session_value':ccc_id});
            }

        </script>

    </head>
    <body>
        <div class="full-content" style="background: #E98CBF">
            <div id="sub_title" >
                <a href="<?php echo base_url() . 'patient_management ' ?>">Patient Listing </a> <i class=" icon-chevron-right"></i><a href="<?php echo base_url() . 'patient_management/viewDetails/' . $result['id'] ?>"><?php echo strtoupper($result['first_name'] . ' ' . $result['other_name'] . ' ' . $result['last_name']) ?></a> <i class=" icon-chevron-right"></i><strong>Dispensing details</strong>
                <hr size="1">
            </div>
            <h3>Dispense Drugs</h3>
            <form id="dispense_form"  name="dispense_form" class="dispense_form" method="post"  action="<?php echo base_url() . 'dispensement_management/save'; ?>"   >
                <textarea name="sql" id="sql" style="display:none;"></textarea>
                <input type="hidden" id="hidden_stock" name="hidden_stock"/>
                <input type="hidden" id="days_count" name="days_count"/>
                <input type="hidden" id="stock_type_text" name="stock_type_text" value="main pharmacy" />
                <input type="hidden" id="purpose_refill_text" name="purpose_refill_text" value="" />
                <input type="hidden" id="patient_source" name="patient_source" value="<?php echo $result['patient_source']; ?>" />
                <div class="column-2">
                    <fieldset>
                        <legend>
                            Dispensing Information 
                        </legend>
                        <div class="max-row">
                        	<div class="mid-row">
			                    <?php
			                    $ccc_stores = $this->session->userdata('ccc_store');
			                    // print_r($ccc_stores);
			                    $count_ccc = count($ccc_stores);
			                    $selected = '';
			                    if ($count_ccc > 0) {//In case on has more than one dispensing point
			                        echo "<label><span class='astericks'>*</span>Select dispensing point</label>
			                                <select name='ccc_store_id' id='ccc_store_id' class='validate[required]'>
			                                	<option value=''>Select One</option>";
			                        //Check if facility has more than one dispensing point
			                        foreach ($ccc_stores as $value) {
			                            $name = $value['Name'];
			                            if ($this -> session -> userdata('ccc_store_id')) {
			                             	$ccc_storeid = $this -> session -> userdata('ccc_store_id');
			                                if ($value['id'] === $ccc_storeid) {
			                                    $selected = "selected";
			                                } else {
			                                    $selected = "";
			                                }
			                            } 
			                            echo "<option value='" . $value['id'] . "' " . $selected . ">" . $name . "</option>";
			                        }
			                        echo "</select>";
			                    }
			
			                    //$this->session->set_userdata('ccc_store',$name);
			                    ?>
			                </div>
                        </div>
		 				
                        <div class="max-row">
                            <div class="mid-row">
                                <label>Patient Number CCC</label>
                                <input readonly="" id="patient" name="patient" class="validate[required]"/>
                            </div>
                            <div class="mid-row">
                                <label>Patient Name</label>
                                <input readonly="" id="patient_details" name="patient_details"  />
                            </div>
                        </div>

                        <div class="max-row">
                            <div class="mid-row">
                                <label><span class='astericks'>*</span>Dispensing Date</label>

                                <input  type="text"name="dispensing_date" id="dispensing_date" class="validate[required]">
                            </div>
                            <div class="mid-row">
                                <label><span class='astericks'>*</span>Purpose of Visit</label>

                                <select  type="text"name="purpose" id="purpose" class="validate[required]" style='width:100%;'>
                                    <option value="">--Select One--</option>
                                    <?php
                                    foreach ($purposes as $purpose) {
                                        echo "<option value='" . $purpose['id'] . "'>" . $purpose['Name'] . "</option>";
                                    }
                                    ?>
                                </select>
                                </label>
                            </div>
                        </div>
                        <div class="max-row">
                            <div class="mid-row">
                                <label>Current Height(cm)</label>
                                <input  type="text"name="height" id="height" class="validate[required]">
                            </div>
                            <div class="mid-row">
                                <label><span class='astericks'>*</span>Current Weight(kg)</label>
                                <input  type="text"name="weight" id="weight" class="validate[required]" >
                            </div>
                        </div>
                        <div class="max-row">
                            <div class="mid-row">
                                <label><span class='astericks'>*</span>Days to Next Appointment</label>
                                <input  type="text" name="days_to_next" id="days_to_next" class="validate[required]">
                            </div>
                            <div class="mid-row">
                                <label><span class='astericks'>*</span>Date of Next Appointment</label>
                                <input  type="text" name="next_appointment_date" id="next_appointment_date" class="validate[required]" >
                            </div>
                        </div>


                        <div class="max-row">
                            <br/>
                            <span id="scheduled_patients" style="display:none;background:#9CF;padding:5px;">

                            </span>

                        </div>
                        <div class="max-row">
                            <div class="mid-row">
                                <label id="scheduled_patients" class="message information close" style="display:none"></label><label>Last Regimen Dispensed</label>
                                <input type="text"name="last_regimen_disp" value="none" id="last_regimen_disp" readonly="">
                                <input type="hidden" name="last_regimen" value="0" id="last_regimen" value="0">
                            </div>

                            <div class="mid-row">
                                <label><span class='astericks'>*</span>Current Regimen</label>
                                <select type="text"name="current_regimen" id="current_regimen"  class="validate[required]" style='width:100%;' >
                                    <option value="">-Select One--</option>
                                    <?php
                                    foreach ($regimens as $regimen) {
                                        echo "<option value='" . $regimen['id'] . "'>" . $regimen['Regimen_Code'] . " | " . $regimen['Regimen_Desc'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="max-row">
                            <div class="mid-row">
                                <div style="display:none" id="regimen_change_reason_container">
                                    <label><span class='astericks'>*</span>Regimen Change Reason</label>
                                    <select type="text"name="regimen_change_reason" id="regimen_change_reason" >
                                        <option value="">--Select One--</option>
                                        <?php
                                        foreach ($regimen_changes as $changes) {
                                            echo "<option value='" . $changes['id'] . "'>" . $changes['Name'] . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="max-row">
                            <div class="mid-row">
                                <label>Appointment Adherence (%)</label>
                                <input type="text" name="adherence" id="adherence"/>
                            </div>
                            <div class="mid-row">
                                <label> Poor/Fair Adherence Reasons </label>
                                <select type="text"name="non_adherence_reasons" id="non_adherence_reasons"  style='width:100%;'>
                                    <option value="">-Select One--</option>
                                    <?php
                                    foreach ($non_adherence_reasons as $reasons) {
                                        echo "<option value='" . $reasons['id'] . "'>" . $reasons['Name'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                    </fieldset>
                </div>
                <div class="column-2">
                    <fieldset>
                        <legend>
                            Previous Patient Information
                        </legend>
                        <div class="max-row">
                            <div class="mid-row">
                                <label> Appointment Date</label>
                                <input readonly="" id="last_appointment_date" name="last_appointment_date"/>
                            </div>
                        </div>
                        <div class="max-row">
                            <div class="mid-row">
                                <label>Previous Visit Date</label>
                                <input readonly="" id="last_visit_date" name="last_visit_date"/>
                                <input type="hidden" id="last_visit"/>
                            </div>
                        </div>
                        <div class="max-row">
                            <table class="data-table prev_dispense" id="last_visit_data" style="float:left;width:100%;">
                                <thead>
                                <th>Drug Dispensed</th>
                                <th>Quantity Dispensed</th>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($visits) {
                                        foreach ($visits as $visit) {
                                            echo "<tr><td>" . $visit['drug'] . "</td><td style='text-align:center'>" . $visit['quantity'] . "</td></tr>";
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </fieldset>
                </div>

               

                <div class="content-rowy" style="height:auto;">
                    <table border="0" class="data-table" id="drugs_table" style="">
                        <thead>
                        <th class="subsection-title" colspan="15">Select Drugs</th>
                       <!--  <tr style="font-size:0.8em">
                            <th>Drug</th>
                            <th>Unit</th>
                            <th >Batch No.&nbsp;</th>
                            <th>Expiry&nbsp;Date</th>
                            <th>Dose</th>
                            <th><b>Expected</b><br/>Pill Count</th>
                            <th><b>Actual</b><br/> Pill Count</th>
                            <th>Duration</th>
                            <th>Qty. disp</th>
                            <th>Stock on Hand</th>
                            <th>Brand Name</th>
                            <th>Indication</th>
                            <th>Comment</th>
                            <th>Missed Pills</th>
                            <th style="">Action</th>
                        </tr> -->
                        </thead>
                        <tbody>
                            <tr drug_row="0">
                                <td><select name="drug[]" id="drug" class="drug input-large span3"></select></td>
                                <td>
                                    <input type="text" name="unit[]" class="unit input-small" style="" readonly="" />
                                    <input type="hidden" name="comment[]" class="comment input-small" style="" readonly="" />
                                </td>
                                <td><select name="batch[]" class="batch input-small next_pill span2"></select></td>
                                <td>
                                    <input type="text" name="expiry[]" name="expiry" class="expiry input-small" id="expiry_date" readonly="" size="15"/>
                                </td>
                                <td class="dose_col">
                                    <input  name="dose[]" list="dose" id="doselist"  style="width:95%;height:25px;" class="input-small next_pill dose">
                                    <datalist id="dose" class="dose"><select name="dose1[]" class="dose"></select></datalist>
                                </td>
                                <td>
                                    <input type="text" name="pill_count[]" class="pill_count input-small" readonly="readonly" />
                                </td>
                                <td>
                                    <input type="text" name="next_pill_count[]" class="next_pill_count input-small"qty  />
                                </td>
                                <td>
                                    <input type="text" name="duration[]" class="duration input-small" />
                                </td>
                                <td>
                                    <input type="text" name="qty_disp[]" class="qty_disp input-small next_pill validate[requireds]"  id="qty_disp"/>
                                <td>
                                    <input type="text" name="soh[]" class="soh input-small" readonly="readonly"/>
                                </td>
                                </td>
                                <td><select name="brand[]" class="brand input-small"></select></td>

                                <td>
                                    <select name="indication[]" class="indication input-small " style="">
                                        <option value="0">None</option>
                                    </select></td>
                                <td>
                                    <input type="text" name="comment[]" class="comment input-small" />
                                </td>
                                <td>
                                    <input type="text" name="missed_pills[]" class="missed_pills input-small" />
                                </td>
                                <td>
                                    <a class="add btn-small">Add</a>|<a id="removebtn" class="remove btn-small">Remove</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </div>
                <div id="submit_section">
                    <input type="reset" class="btn btn-danger button_size" id="reset" value="Reset Fields" />
                    <input type="button" class="btn button_size" id="print_btn" value="Print Labels" />
                    <input type="submit" form="dispense_form" id="btn_submit " class="btn actual button_size" id="submit"  value="Dispense Drugs"/>
                </div>
            </form>

        </div>

        <!-- Modal -->
        <div id="open_print_label" name="open_print_label" title="Label Printer" class="container-fluid">
            <!--select all row-->
            <div class="drugrow"> 
                <form id="print_frm" method="post" action="<?php echo base_url(); ?>dispensement_management/print_test">
                    <div class="row label_selectall">
                        <div class="span1" style="padding: 4px 5px;">
                            <label class="checkbox inline">
                                <input type="checkbox" id="selectall" class="label_checker" value="0">All
                            </label>
                        </div>

                </form>
            </div>
        </div>
        <!--end modal-->
</body>
</html>