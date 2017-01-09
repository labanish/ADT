
	<input type="hidden" id="edit_selected_report" />
<input type="hidden" id="selected_report" />
<div id="date_range_report" title="Select Date Range">
	<table>
		<tr class="show_report_type" style="display: none">
			<td align="left"><strong>Select Report Type :</strong></td>
			<td>
				<select name="commodity_summary_report_type" id="commodity_summary_report_type" class="report_type input-large">
					<option value="0">-- Select Report Type --</option>
					<option value="1">Main Store</option>
					<option value="2">Pharmacy</option>
				</select>
			</td>
		</tr>
		<tr>
			<td><strong class="label">From: </strong></td>
			<td><input type="text"name="date_range_from" id="date_range_from"></td>
		</tr>
		<tr>
			<td><strong class="label">To: </strong></td>
			<td><input type="text"name="date_range_to" id="date_range_to"></td>
		</tr>
		
	</table>
	<br>
	<button id="generate_date_range_report"  style="height:30px; font-size: 13px; width: 200px;">
		Generate Report
	</button>
</div>
<div id="donor_date_range_report" title="Select Date Range and Donor">
	<table>
		<tr>
			<td><strong class="label">Select Donor: </strong></td>
			<td>
				<select name="donor" id="donor">
					<option value="0">All Donors</option><option value="1">GOK</option><option value="2">PEPFAR</option>
				</select>
			</td>
		</tr>
		<tr>
			<td><strong class="label">From: </strong></td>
			<td>
				<input type="text"name="donor_date_range_from" id="donor_date_range_from">
			</td>
		</tr>
		<tr>
			<td><strong class="label">To: </strong></td>
			<td><input type="text"name="donor_date_range_to" id="donor_date_range_to"></td>
		</tr>
	</table>
	<br>
	<button id="donor_generate_date_range_report"  style="height:30px; font-size: 13px; width: 200px;">
		Generate Report
	</button>
</div>
<div id="single_date">
	<table>
		<tr>
			<td><strong class="label">Select Date </strong></td>
			<td>
				<input type="text"name="filter_date" id="single_date_filter">
			</td>
		</tr>
	</table>
	<br>
	<button id="generate_single_date_report"  style="height:30px; font-size: 13px; width: 200px;">
		Generate Report
	</button>
</div>
<div id="year">
	<table>
		<tr>
			<td><strong class="label">Report Year: </strong></td>
			<td>
				<input type="text"name="filter_year" id="single_year_filter">
			</td>
		</tr>
	</table>
	<br>
	<button id="generate_single_year_report"  style="height:30px; font-size: 13px; width: 200px;">
		Generate Report
	</button>
</div>
<div id="no_filter">
	<tr class="show_report_type" style="display: none">
			<td align="left"><strong>Select Report Type :</strong></td>
			<td>
				<select name="commodity_summary_report_type_1" id="commodity_summary_report_type_1" class="report_type input-large">
					<option value="0">-- Select Report Type --</option>
					<option value="1">Main Store</option>
					<option value="2">Pharmacy</option>
				</select>
			</td>
	</tr>
	
	<br>
	<button id="generate_no_filter_report"  style="height:30px; font-size: 13px; width: 200px;">
		Generate Report
	</button>
</div>