<script type="text/javascript">
	$(document).ready(function() {
		var results=<?php echo json_encode($results);?>;
		console.log(results);
		var missed_pills_count_95 = 0;
		var missed_pills_count_85 = 0;
		var missed_pills_count_80 = 0;

		var two_missed_pills_count_95 = 0;
		var two_missed_pills_count_85 = 0;
		var two_missed_pills_count_80 = 0;

		var pills_count_95 = 0;
		var pills_count_85 = 0;
		var pills_count_80 = 0;

		var appoint_pills_count_95 = 0;
		var appoint_pills_count_85 = 0;
		var appoint_pills_count_80 = 0;

		var two_pills_count_95 = 0;
		var two_pills_count_85 = 0;
		var two_pills_count_80 = 0;

		//Appointment variables
		//Count for ARVs/OI in all levels of appointments
		var appointment_arv_95 = 0;
		var appointment_oi_95 = 0;
		var appointment_arv_85 = 0;
		var appointment_oi_85 = 0;
		var appointment_arv_80 = 0;
		var appointment_oi_80 = 0;

		//Variables to hold gender
		var appointment_male_95 = 0;
		var appointment_female_95 = 0;
		var appointment_male_85 = 0;
		var appointment_female_85 = 0;
		var appointment_male_80 = 0;
		var appointment_female_80 = 0;

		//Variables for Age
                var appointment_lt14_95 = 0;
		var appointment_lt15_95 = 0;
		var appointment_lt24_95 = 0;

		var appointment_lt14_85 = 0;
		var appointment_lt15_85 = 0;
		var appointment_lt24_85 = 0;

		var appointment_lt14_80 = 0;
		var appointment_lt15_80 = 0;
		var appointment_lt24_80 = 0;

		//Missed doses
		//Variables to hold total
		var missed_total_95 = 0;
		var missed_total_85 = 0;
		var missed_total_80 = 0;;
		var missed_2_total_95 = 0;
		var missed_2_total_85 = 0;
		var missed_2_total_80 = 0;

		//Variables to hold gender
		var missed_1_male_95 = 0;
		var missed_1_female_95 = 0;
		var missed_1_male_85 = 0;
		var missed_1_female_85 = 0;
		var missed_1_male_80 = 0;
		var missed_1_female_80 = 0;
		var missed_2_male_95 = 0;
		var missed_2_female_95 = 0;
		var missed_2_male_85 = 0;
		var missed_2_female_85 = 0;
		var missed_2_male_80 = 0;
		var missed_2_female_80 = 0;

		//Variables for Age
		var missed_1_lt14_95 = 0;
		var missed_1_lt15_95 = 0;
		var missed_1_lt24_95 = 0;
		var missed_2_lt14_95 = 0;
		var missed_2_lt15_95 = 0;
		var missed_2_lt24_95 = 0;

		var missed_1_lt14_85 = 0;
		var missed_1_lt15_85 = 0;
		var missed_1_lt24_85 = 0;
		var missed_2_lt14_85 = 0;
		var missed_2_lt15_85 = 0;
		var missed_2_lt24_85 = 0;

		var missed_1_lt14_80 = 0;
		var missed_1_lt15_80 = 0;
		var missed_1_lt24_80 = 0;
		var missed_2_lt14_80 = 0;
		var missed_2_lt15_80 = 0;
		var missed_2_lt24_80 = 0;

		//Pill Count
		var pill_total_95 = 0;
		var pill_total_85 = 0;
		var pill_total_80 = 0;
		var pill_2_total_95 = 0;
		var pill_2_total_85 = 0;
		var pill_2_total_80 = 0;

		//Variables to hold gender
		var pill_1_male_95 = 0;
		var pill_1_female_95 = 0;
		var pill_1_male_85 = 0;
		var pill_1_female_85 = 0;
		var pill_1_male_80 = 0;
		var pill_1_female_80 = 0;
		var pill_2_male_95 = 0;
		var pill_2_female_95 = 0;
		var pill_2_male_85 = 0;
		var pill_2_female_85 = 0;
		var pill_2_male_80 = 0;
		var pill_2_female_80 = 0;

		//Variables for Age
		var pill_1_lt14_95 = 0;
		var pill_1_lt15_95 = 0;
		var pill_1_lt24_95 = 0;
		var pill_2_lt14_95 = 0;
		var pill_2_lt15_95 = 0;
		var pill_2_lt24_95 = 0;

		var pill_1_lt14_85 = 0;
		var pill_1_lt15_85 = 0;
		var pill_1_lt24_85 = 0;
		var pill_2_lt14_85 = 0;
		var pill_2_lt15_85 = 0;
		var pill_2_lt24_85 = 0;

		var pill_1_lt14_80 = 0;
		var pill_1_lt15_80 = 0;
		var pill_1_lt24_80 = 0;
		var pill_2_lt14_80 = 0;
		var pill_2_lt15_80 = 0;
		var pill_2_lt24_80 = 0;

		var no_adherence = 0;
		var errors_count = 0;
		var count = 0;

		for(var i = 0; i < results.length; i++) {
			var parent_row = results[i];

			var adherence_by_appointment = 0;
			var pill_count = 0;
			var missed_pills = 0;

			var adherence_by_appointment = parent_row['adherence'];
			var pill_count = parent_row['pill_count'];
			var missed_pills = parent_row['missed_pills'];
			var dose_frequency = parent_row['frequency'];
			var gender = parent_row['gender'];
			var age = parent_row['age'];
			var adherence_by_self = "Not Computed";
			var adherence_by_pill = "Not Computed";
			var adherence_by_appointment = "Not Computed";
			var hey = parent_row['adherence'];
			hey = $.trim(hey);
			if(hey.length > 0) {
				//calculate adherence by missed pills
				if(dose_frequency == 1) {
					if(missed_pills < 2 && missed_pills >= 0) {
						missed_total_95++;
						if(gender == 1) {
							missed_1_male_95++;
						} else if(gender == 2) {
							missed_1_female_95++;
						}

						//Age
						if(age < 15) {
							missed_1_lt14_95++;
						} else if(age > 24) {
							missed_1_lt24_95++;
						} else {
							missed_1_lt15_95++;
						}
					} else if(missed_pills >= 2 && missed_pills <= 4) {
						missed_total_85++;
						if(gender == 1) {
							missed_1_male_85++;
						} else if(gender == 2) {
							missed_1_female_85++;
						}
						//Age
						if(age < 15) {
							missed_1_lt14_85++;
						} else if(age > 24) {
							missed_1_lt24_85++;
						} else {
							missed_1_lt15_85++;
						}
					} else if(missed_pills >= 5) {
						missed_total_80++;
						if(gender == 1) {
							missed_1_male_80++;
						} else if(gender == 2) {
							missed_1_female_80++;
						}
						//Age
						if(age < 15) {
							missed_1_lt14_80++;
						} else if(age > 24) {
							missed_1_lt24_80++;
						} else {
							missed_1_lt15_80++;
						}
					}
				} else if(dose_frequency == 2) {
					if(missed_pills <= 3 && missed_pills >= 0) {
						missed_2_total_95++;
						if(gender == 1) {
							missed_2_male_95++;
						} else if(gender == 2) {
							missed_2_female_95++;
						}

						//Age
						if(age < 15) {
							missed_2_lt14_95++;
						} else if(age > 24) {
							missed_2_lt24_95++;
						} else {
							missed_2_lt15_95++;
						}
					} else if(missed_pills >= 4 && missed_pills <= 8) {
						missed_2_total_85++;
						if(gender == 1) {
							missed_2_male_85++;
						} else if(gender == 2) {
							missed_2_female_85++;
						}

						//Age
						if(age < 15) {
							missed_2_lt14_85++;
						} else if(age > 24) {
							missed_2_lt24_85++;
						} else {
							missed_2_lt15_85++;
						}
					} else if(missed_pills >= 9) {
						missed_2_total_80++;
						if(gender == 1) {
							missed_2_male_80++;
						} else if(gender == 2) {
							missed_2_female_80++;
						}

						//Age
						if(age < 15) {
							missed_2_lt14_80++;
						} else if(age > 24) {
							missed_2_lt24_80++;
						} else {
							missed_2_lt15_80++;
						}
					}
				}
				//calculate adherence by pill count
				if(dose_frequency == 1) {             
					if(pill_count < 2 && pill_count >= 0) {
						pill_total_95++;
						if(gender == 1) {
							pill_1_male_95++;
						} else if(gender == 2) {
							pill_1_female_95++;
						}

						//Age
						if(age < 15) {
							pill_1_lt14_95++;
						} else if(age > 24) {
							pill_1_lt24_95++;
						} else {
							pill_1_lt15_95++;
						}

					} else if(pill_count >= 2 && pill_count <= 4) {
						pill_total_85++;
						if(gender == 1) {
							pill_1_male_85++;
						} else if(gender == 2) {
							pill_1_female_85++;
						}

						//Age
						if(age < 15) {
							pill_1_lt14_85++;
						} else if(age > 24) {
							pill_1_lt24_85++;
						} else {
							pill_1_lt15_85++;
						}
					} else if(pill_count >= 5) {
						pill_total_80++;
						if(gender == 1) {
							pill_1_male_80++;
						} else if(gender == 2) {
							pill_1_female_80++;
						}

						//Age
						if(age < 15) {
							pill_1_lt14_80++;
						} else if(age > 24) {
							pill_1_lt24_80++;
						} else {
							pill_1_lt15_80++;
						}
					}

				} else if(dose_frequency == 2) {
					if(pill_count <= 3 && pill_count >= 0) {
						pill_2_total_95++;
						if(gender == 1) {
							pill_2_male_95++;
						} else if(gender == 2) {
							pill_2_female_95++;
						}

						//Age
						if(age < 15) {
							pill_2_lt14_95++;
						} else if(age > 24) {
							pill_2_lt24_95++;
						} else {
							pill_2_lt15_95++;
						}
					} else if(pill_count >= 4 && pill_count <= 8) {
						pill_2_total_85++;
						if(gender == 1) {
							pill_2_male_85++;
						} else if(gender == 2) {
							pill_2_female_85++;
						}

						//Age
						if(age < 15) {
							pill_2_lt14_85++;
						} else if(age > 24) {
							pill_2_lt24_85++;
						} else {
							pill_2_lt15_85++;
						}
					} else if(pill_count >= 9) {
						pill_2_total_80++;
						if(gender == 1) {
							pill_2_male_80++;
						} else if(gender == 2) {
							pill_2_female_80++;
						}

						//Age
						if(age < 15) {
							pill_2_lt14_80++;
						} else if(age > 24) {
							pill_2_lt24_80++;
						} else {
							pill_2_lt15_80++;
						}
					}
				}
				//calculate adherence by appointment
				count++;
				adherence_rate = parent_row['adherence']; 
				if(adherence_rate == ">=95%" || adherence_rate == "100%") {
					appoint_pills_count_95++;
					if(parent_row['service'] == 5) {
						appointment_oi_95++;
					} else {
						appointment_arv_95++;
					}
					if(parent_row['gender'] == 1) {
						appointment_male_95++;
					}
					if(parent_row['gender'] == 2) {
						appointment_female_95++;
					}

					if(parent_row['age'] < 15) {
						appointment_lt14_95++;
					} else if(parent_row['age'] > 24) {
						appointment_lt24_95++;
					} else {
						appointment_lt15_95++;
					}
				} else if(adherence_rate == "85-94%" || adherence_rate == "84-94%") {
					appoint_pills_count_85++;
					if(parent_row['service'] == 5) {
						appointment_oi_85++;
					} else {
						appointment_arv_85++;
					}
					if(parent_row['gender'] == 1) {
						appointment_male_85++;
					}
					if(parent_row['gender'] == 2) {
						appointment_female_85++;
					}

					if(parent_row['age'] < 15) {
						appointment_lt14_85++;
					} else if(parent_row['age'] > 24) {
						appointment_lt24_85++;
					} else {
						appointment_lt15_85++;
					}
				} else if(adherence_rate == "<85%" || adherence_rate == "<84%") {
					appoint_pills_count_80++;
					if(parent_row['service'] == 5) {
						appointment_oi_80++;
					} else {
						appointment_arv_80++;
					}
					if(parent_row['gender'] == 1) {
						appointment_male_80++;
					}
					if(parent_row['gender'] == 2) {
						appointment_female_80++;
					}

					if(parent_row['age'] < 15) {
						appointment_lt14_80++;
					} else if(parent_row['age'] > 24) {
						appointment_lt24_80++;
					} else {
						appointment_lt15_80++;
					}
				} else {
					no_adherence++;
				}
			}

			if(dose_frequency > 2) {
				errors_count++;
			}

		}
                
		$("#appointment_total_95").text(appoint_pills_count_95);
		$("#appointment_total_85").text(appoint_pills_count_85);
		$("#appointment_total_80").text(appoint_pills_count_80);

		//Appointments ARVs/OI
		$("#appointment_arv_95").text(appointment_arv_95);
		$("#appointment_arv_85").text(appointment_arv_85);
		$("#appointment_arv_80").text(appointment_arv_80);

		//Appointments /OI
		$("#appointment_oi_95").text(appointment_oi_95);
		$("#appointment_oi_85").text(appointment_oi_85);
		$("#appointment_oi_80").text(appointment_oi_80);

		//Appointments for Age
		$("#appointment_lt14_95").text(appointment_lt14_95);
		$("#appointment_15-24_95").text(appointment_lt15_95);
		$("#appointment_gt24_95").text(appointment_lt24_95);

		$("#appointment_lt14_85").text(appointment_lt14_85);
		$("#appointment_15-24_85").text(appointment_lt15_85);
		$("#appointment_gt24_85").text(appointment_lt24_85);

		$("#appointment_lt14_80").text(appointment_lt14_80);
		$("#appointment_15-24_80").text(appointment_lt15_80);
		$("#appointment_gt24_80").text(appointment_lt24_80);

		//Apointment Gender
		$("#appointment_male_95").text(appointment_male_95);
		$("#appointment_female_95").text(appointment_female_95);
		$("#appointment_male_85").text(appointment_male_85);
		$("#appointment_female_85").text(appointment_female_85);
		$("#appointment_male_80").text(appointment_male_80);
		$("#appointment_female_80").text(appointment_female_80);

		//Missed dose total
		$("#missed_total_95").text(missed_total_95);
		$("#missed_2_total_95").text(missed_2_total_95);
		$("#missed_2_gt25_95avg").text((missed_total_95+missed_2_total_95)/2);
		$("#missed_total_85").text(missed_total_85);
		$("#missed_2_total_85").text(missed_2_total_85);
		$("#missed_2_gt25_85avg").text((missed_total_85+missed_2_total_85)/2);
		$("#missed_total_80").text(missed_total_80);
		$("#missed_2_total_80").text(missed_2_total_80);
		$("#missed_2_gt25_80avg").text((missed_total_80+missed_2_total_80)/2);

		//Pill Count total
		$("#pill_total_95").text(pill_total_95);
		$("#pill_2_total_95").text(pill_2_total_95);
		$("#pill_2_gt25_95avg").text((pill_total_95+pill_2_total_95)/2);
		$("#pill_total_85").text(pill_total_85);
		$("#pill_2_total_85").text(pill_2_total_85);
		$("#pill_2_gt25_85avg").text((pill_total_85+pill_2_total_85)/2);
		$("#pill_total_80").text(pill_total_80);
		$("#pill_2_total_80").text(pill_2_total_80);
		$("#pill_2_gt25_80avg").text((pill_total_80+pill_2_total_80)/2);

		//Missed dose gender
		$("#missed_1_male_95").text(missed_1_male_95);
		$("#missed_1_male_85").text(missed_1_male_85);
		$("#missed_1_male_80").text(missed_1_male_80);
		$("#missed_1_female_95").text(missed_1_female_95);
		$("#missed_1_female_85").text(missed_1_female_85);
		$("#missed_1_female_80").text(missed_1_female_80);

		$("#missed_2_male_95").text(missed_2_male_95);
		$("#missed_2_male_85").text(missed_2_male_85);
		$("#missed_2_male_80").text(missed_2_male_80);
		$("#missed_2_female_95").text(missed_2_female_95);
		$("#missed_2_female_85").text(missed_2_female_85);
		$("#missed_2_female_80").text(missed_2_female_80);

		//Pill Count gender
		$("#pill_1_male_95").text(pill_1_male_95);
		$("#pill_1_male_85").text(pill_1_male_85);
		$("#pill_1_male_80").text(pill_1_male_80);
		$("#pill_1_female_95").text(pill_1_female_95);
		$("#pill_1_female_85").text(pill_1_female_85);
		$("#pill_1_female_80").text(pill_1_female_80);

		$("#pill_2_male_95").text(pill_2_male_95);
		$("#pill_2_male_85").text(pill_2_male_85);
		$("#pill_2_male_80").text(pill_2_male_80);
		$("#pill_2_female_95").text(pill_2_female_95);
		$("#pill_2_female_85").text(pill_2_female_85);
		$("#pill_2_female_80").text(pill_2_female_80);

		//Missed dose age
		$("#missed_1_lt14_95").text(missed_1_lt14_95);
		$("#missed_1_lt14_85").text(missed_1_lt14_85);
		$("#missed_1_lt14_80").text(missed_1_lt14_80);

		$("#missed_1_15-24_95").text(missed_1_lt15_95);
		$("#missed_1_15-24_85").text(missed_1_lt15_85);
		$("#missed_1_15-24_80").text(missed_1_lt15_80);

		$("#missed_1_gt25_95").text(missed_1_lt24_95);
		$("#missed_1_gt25_85").text(missed_1_lt24_85);
		$("#missed_1_gt25_80").text(missed_1_lt24_80);

		$("#missed_2_lt14_95").text(missed_2_lt14_95);
		$("#missed_2_lt14_85").text(missed_2_lt14_85);
		$("#missed_2_lt14_80").text(missed_2_lt14_80);

		$("#missed_2_15-24_95").text(missed_2_lt15_95);
		$("#missed_2_15-24_85").text(missed_2_lt15_85);
		$("#missed_2_15-24_80").text(missed_2_lt15_80);

		$("#missed_2_gt25_95").text(missed_2_lt24_95);
		$("#missed_2_gt25_85").text(missed_2_lt24_85);
		$("#missed_2_gt25_80").text(missed_2_lt24_80);

		//Pill count age		
		$("#pill_1_lt14_95").text(pill_1_lt14_95);
		$("#pill_1_lt14_85").text(pill_1_lt14_85);
		$("#pill_1_lt14_80").text(pill_1_lt14_80);
		
		$("#pill_1_15-24_95").text(pill_1_lt15_95);
		$("#pill_1_15-24_85").text(pill_1_lt15_85);
		$("#pill_1_15-24_80").text(pill_1_lt15_80);
	
		$("#pill_1_gt25_95").text(pill_1_lt24_95);
		$("#pill_1_gt25_85").text(pill_1_lt24_85);
		$("#pill_1_gt25_80").text(pill_1_lt24_80);

		$("#pill_2_lt14_95").text(pill_2_lt14_95);
		$("#pill_2_lt14_85").text(pill_2_lt14_85);
		$("#pill_2_lt14_80").text(pill_2_lt14_80);
		
		$("#pill_2_15-24_95").text(pill_2_lt15_95);
		$("#pill_2_15-24_85").text(pill_2_lt15_85);
		$("#pill_2_15-24_80").text(pill_2_lt15_80);
		
		$("#pill_2_gt25_95").text(pill_2_lt24_95);
		$("#pill_2_gt25_85").text(pill_2_lt24_85);
		$("#pill_2_gt25_80").text(pill_2_lt24_80);

		$("#total_count").text(count);
		missed_count=parseInt(count-(missed_total_95+missed_2_total_95+missed_total_85+missed_2_total_85+missed_total_80+missed_2_total_80));
		$("#missed_count").text(missed_count);
		pill_count=parseInt(count-(pill_total_95+pill_2_total_95+pill_total_85+pill_2_total_85+pill_total_80+pill_2_total_80));
		$("#pill_count").text(pill_count);
		function numberWithCommas(x) {
		   return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
		}
	});
</script>

<div id="wrapperd">
	<div  class="full-content">
		<?php $this->load->view("reports/reports_top_menus_v")
		?>
		<h4 style="text-align: center">Patients Adherence Summary
		<br>
		<br>
		Between
		<input type="text" id="start_date" value="<?php echo $from; ?>">
		and
		<input type="text" id="end_date" value="<?php echo $to; ?>">
		</h4>
		<hr size="1" style="width:80%">
		<table align="center" class="table table-condensed" style="width:50%">
		<tr>
		    <td style="text-align:center;"><h5 class="report_title">Number Of Patients: <span id="total_count"></span></h5></td>
		    <td style="text-align:center;"><h5 class="report_title">Patients without Pill Count: <span id="pill_count"></span></h5></td>
		    <td style="text-align:center;"><h5 class="report_title">Patients without Missed Doses: <span id="missed_count"></span></h5></td>
		</tr>
		</table>
		<div id="adherence_form">
			<div style="text-align: center;	width:100%;margin:0 auto;">
				<h3>Adherence By Appointment</h3>
			</div>
			<table class="listing_table" border="1" id="appointment_listing"  cellpadding="3" cellspacing="5" align="center" width="100%">
				<thead>
					<tr>
				
						<th class="h1" rowspan="2">Adherence %</th>
						<th class="h1" rowspan="2">Total</th>
						<th class="h1 _status" colspan="2">Status</th>
						<th class="h1 _sex" colspan="2" >Sex</th>
						<th class="h1 _age" colspan="3" >Age(years)</th>
					</tr>
					<tr>
						<th class="_status">ARVs</th>
						<th class="_status">OIs</th>
						<th class="_sex">Male</th>
						<th class="_sex">Female</th>
						<th class="_age" width="45">&lt;15</th>
						<th class="_age" width="45">15-24</th>
						<th class="_age">&gt;24</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>&gt;=95 % (Within 2 days)</td><td id="appointment_total_95" class="_total"></td><td id="appointment_arv_95" class="_status"></td><td id="appointment_oi_95" class="_status"></td><td id="appointment_male_95" class="_sex"></td><td id="appointment_female_95" class="_sex"></td><td id="appointment_lt14_95" class="_age"></td><td id="appointment_15-24_95" class="_age"><td id="appointment_gt24_95" class="_age"></td>
					</tr>
					<tr>
						<td>85 - 94 %(3 - 14 days)</td><td id="appointment_total_85" class="_total"></td><td id="appointment_arv_85" class="_status"></td><td id="appointment_oi_85" class="_status"></td><td id="appointment_male_85" class="_sex"></td><td id="appointment_female_85" class="_sex"></td><td id="appointment_lt14_85" class="_age"></td><td id="appointment_15-24_85" class="_age"><td id="appointment_gt24_85" class="_age"></td>
					</tr>
					<tr>
						<td>&lt; 85 % (14 and above)</td><td id="appointment_total_80" class="_total"></td><td id="appointment_arv_80" class="_status"></td><td id="appointment_oi_80" class="_status"></td><td id="appointment_male_80" class="_sex"></td><td id="appointment_female_80" class="_sex"></td><td id="appointment_lt14_80" class="_age"></td><td id="appointment_15-24_80" class="_age"><td id="appointment_gt24_80" class="_age"></td>
					</tr>
				</tbody>
			</table>
			<div style="text-align: center;	width:100%;margin:0 auto;">
				<h3>Adherence By Missed Doses</h3>
			</div>
			<table class="listing_table" border="1" id="missed_doses"  cellpadding="3" cellspacing="5">
				<thead>
					<tr>
						<th class="h1" rowspan="2">Percentage (%)</th>
						<th class="h1" rowspan="2">No .of Doses Missed(1x)</th>
						<th class="h1" rowspan="2">Total(1x) Dosing</th>
						<th class="h1 _sex" colspan="2" >Sex</th>
						<th class="h1 _age" colspan="3" >Age(years)</th>
						<th class="h1" rowspan="2">No .of Doses Missed(2x)</th>
						<th class="h1" rowspan="2">Total(2x) Dosing</th>
						<th class="h1 _sex" colspan="2" >Sex</th>
						<th class="h1 _age" colspan="3" >Age(years)</th>
						<th class="h1" >Avg</th>
					</tr>
					<tr>
						<th class="_sex">Male</th>
						<th class="_sex">Female</th>
						<th class="_age">&lt;15</th>
						<th class="_age" width="45">15-24</th>
						<th class="_age">&gt;24</th>
						<th class="_sex">Male</th>
						<th class="_sex">Female</th>
						<th class="_age">&lt;15</th>
						<th class="_age" width="45">15-24</th>
						<th class="_age">&gt;24</th>
						<th class="_age">(1x + 2x)/2</th>
                                                
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>&gt;=95%</td><td>&lt; 2 Doses</td><td class="_total" id="missed_total_95"></td><td id="missed_1_male_95"></td><td id="missed_1_female_95"></td><td id="missed_1_lt14_95"></td><td id="missed_1_15-24_95"></td><td id="missed_1_gt25_95"></td><td>&lt;=3 Doses</td><td id="missed_2_total_95"></td><td id="missed_2_male_95"></td><td id="missed_2_female_95"></td><td id="missed_2_lt14_95"></td><td id="missed_2_15-24_95"></td><td id="missed_2_gt25_95"></td><td class="_total" id="missed_2_gt25_95avg"></td>
					</tr>
					<tr>
						<td>85 - 94%</td><td>2 -4 Doses</td><td class="_total" id="missed_total_85"></td><td id="missed_1_male_85"></td><td id="missed_1_female_85"></td><td id="missed_1_lt14_85"></td><td id="missed_1_15-24_85"></td><td id="missed_1_gt25_85"></td><td>4 - 8 Doses</td><td id="missed_2_total_85"></td><td id="missed_2_male_85"></td><td id="missed_2_female_85"></td><td id="missed_2_lt14_85"></td><td id="missed_2_15-24_85"></td><td id="missed_2_gt25_85"></td><td class="_total" id="missed_2_gt25_85avg"></td>
					</tr>
					<tr>
                                            <td>&lt; 85%</td><td>&gt;= 5 Doses</td><td class="_total" id="missed_total_80"></td><td id="missed_1_male_80"></td><td id="missed_1_female_80"></td><td id="missed_1_lt14_80"></td><td id="missed_1_15-24_80"></td><td id="missed_1_gt25_80"></td><td>&gt; =9 Doses</td><td id="missed_2_total_80"></td><td id="missed_2_male_80"></td><td id="missed_2_female_80"></td><td id="missed_2_lt14_80"></td><td id="missed_2_15-24_80"></td><td  id="missed_2_gt25_80"></td><td class="_total" id="missed_2_gt25_80avg"></td>
					</tr>
				</tbody>
			</table>
			<div style="text-align: center;	width:100%;margin:0 auto;">
				<h3>Adherence By Pill Count</h3>
			</div>
			<table class="listing_table" border="1" id="pill_count"  cellpadding="3" cellspacing="5">
				<thead>
					<tr>
						<th class="h1" rowspan="2">Percentage (%)</th>
						<th class="h1" rowspan="2">No .of Doses Missed(1x)</th>
						<th class="h1" rowspan="2">Total(1x) Dosing</th>
						<th class="h1 _sex" colspan="2" >Sex</th>
						<th class="h1 _age" colspan="3" >Age(years)</th>
						<th class="h1" rowspan="2">No .of Doses Missed(2x)</th>
						<th class="h1" rowspan="2">Total(2x) Dosing</th>
						<th class="h1 _sex" colspan="2" >Sex</th>
						<th class="h1 _age" colspan="3" >Age(years)</th>
						<th class="h1" >Avg</th>
					</tr>
					<tr>
						<th class="_sex">Male</th>
						<th class="_sex">Female</th>
						<th class="_age">&lt;15</th>
						<th class="_age" width="45">15-24</th>
						<th class="_age">&gt;24</th>
						<th class="_sex">Male</th>
						<th class="_sex">Female</th>
						<th class="_age">&lt;15</th>
						<th class="_age" width="45">15-24</th>
						<th class="_age">&gt;24</th>
						<th class="_age">(1x + 2x)/2</th>
					</tr>
				</thead>
				<tbody>
				
					<tr>
						<td>&gt;=95%</td><td>&lt; 2 Doses</td><td class="_total" id="pill_total_95"></td><td id="pill_1_male_95"></td><td id="pill_1_female_95"></td><td id="pill_1_lt14_95"></td><td id="pill_1_15-24_95"></td><td id="pill_1_gt25_95"></td><td>&lt;=3 Doses</td><td id="pill_2_total_95"></td><td id="pill_2_male_95"></td><td id="pill_2_female_95"></td><td id="pill_2_lt14_95"></td><td id="pill_2_15-24_95"></td><td id="pill_2_gt25_95"></td><td class="_total"  id="pill_2_gt25_95avg"></td>
					</tr>
					<tr>
						<td>85 - 94%</td><td>2 -4 Doses</td><td class="_total" id="pill_total_85"></td><td id="pill_1_male_85"></td><td id="pill_1_female_85"></td><td id="pill_1_lt14_85"></td><td id="pill_1_15-24_85"></td><td id="pill_1_gt25_85"></td><td>4 - 8 Doses</td><td id="pill_2_total_85"></td><td id="pill_2_male_85"></td><td id="pill_2_female_85"></td><td id="pill_2_lt14_85"></td><td id="pill_2_15-24_85"></td><td id="pill_2_gt25_85"></td><td class="_total" id="pill_2_gt25_85avg"></td>
					</tr>
					<tr>
						<td>&lt; 85%</td><td>&gt;= 5 Doses</td><td class="_total" id="pill_total_80"></td><td id="pill_1_male_80"></td><td id="pill_1_female_80"></td><td id="pill_1_lt14_80"></td><td id="pill_1_15-24_80"></td><td id="pill_1_gt25_80"></td><td>&gt; =9 Doses</td><td id="pill_2_total_80"></td><td id="pill_2_male_80"></td><td id="pill_2_female_80"></td><td id="pill_2_lt14_80"></td><td id="pill_2_15-24_80"></td><td id="pill_2_gt25_80"></td><td class="_total" id="pill_2_gt25_80avg"></td>
					</tr>
				</tbody>
			</table>
			<p>
				&nbsp;
			</p>
		</div>
	</div>
