


<link href="<?php echo base_url().'assets/styles/datatable/jquery.dataTables.min.css'; ?>" type="text/css" rel="stylesheet"/>
<link href="<?php echo base_url().'assets/styles/datatable/select.dataTables.min.css'; ?>" type="text/css" rel="stylesheet"/>

<!-- Latest compiled and minified JavaScript -->
<script src="./assets/scripts/datatable/jquery-1.12.0.min.js"></script>
<script src="./assets/scripts/datatable/jquery.dataTables.min.js"></script>
<script src="./assets/scripts/datatable/dataTables.select.min.js"></script>


<style type="text/css">

  .dataTable {

    letter-spacing:0px;
  }

  .dataTable thead {
  }
  table.dataTable{
      zoom:0.85;  
  }
  .table-bordered input {
    width:8em;
  }
   .dataTable tr td{
    padding: 0px;
   }

  .dataTable tr td input[type=text] {
   
    border: 0px solid #cdcdcd;
    border-color: #fff;
   
}
  
</style>



<script type="text/javascript">
  $(document).ready(function() {
    $('#example').DataTable( {
        columnDefs: [ {
            orderable: false,
            className: 'select-checkbox',
            targets:   0
        } ],
        select: {
            style:    'os',
            selector: 'td:first-child'
        },
        order: [[ 1, 'asc' ]]
    } );
} );
</script>

<div style="width:98%;">



<form action="<?php  echo base_url().'new_patients/insert_into_db' ?>" method="post">
<input type="submit" name="approve" value="Approve"  class="btn btn-success"/>
<input type="submit" name="disapprove" value="Disapprove"  class="btn btn-danger"/>
<!--<input type="submit" name="submit" value="Disapprove"  class="btn btn-danger"/>-->

<table id="example" class="display table table-bordered table-condensed table-hover" cellspacing="0" cellpadding="0" width="100%">





        <thead>
            <tr>
              <th>Select</th>
              <th>CCC Number</th>
              <th>First Name</th>
              <th>Last Name</th>
              <th>Other Name</th>
              <th>Gender</th>
              <th>Date Enrolled</th>
              
            </tr>
        </thead>
        <tfoot>
            <tr>
              <th>Select</th>
              <th>CCC Number</th>
              <th>First Name</th>
              <th>Last Name</th>
              <th>Other Name</th>
              <th>Gender</th>
              <th>Date Enrolled</th>
             
            </tr>
        </tfoot>
        <tbody>
<?php
 //$checked = array();
foreach ($new_patients as $new_patient)
{
  $id=$new_patient['id'];
  $patient_number_ccc=$new_patient['patient_number_ccc'];
  $first_name=$new_patient['first_name'];
  $last_name=$new_patient['last_name'];
  $other_name=$new_patient['other_name'];
  $date_enrolled=$new_patient['date_enrolled'];
  $gender=$new_patient['gender'];
  if($gender==1){
    $g='male';
  }
  else{
    $g='female';

  }
  

?>
<tr>

  <td><input type="checkbox" name="select[]"/></td>
    <td><input type="text" name="patient_number_ccc[]" value="<?php echo $patient_number_ccc;?>" readonly="readonly" style="background:none; width:100%"/></td>
    <td><input type="text" name="first_name[]" style="text-transform:uppercase; background:none; width:100%" value="<?php echo $first_name;?>" readonly="readonly" /></td>
    <td><input type="text" name="last_name[]" style="text-transform:uppercase; background:none; width:100%" value="<?php echo $last_name;?>" readonly="readonly" /></td>
    <td><input type="text" name="other_name[]" style="text-transform:uppercase; background:none; width:100%" value="<?php echo $other_name;?>" readonly="readonly" /></td>
    <td><input type="text" name="gender[]" value="<?php echo $g;?>" readonly="readonly" style="background:none; width:100%"/></td>
    <td><input type="text" name="date_enrolled[]" value="<?php echo $date_enrolled;?>" readonly="readonly" style="background:none; width:100%"/></td>
    
    </tr>
    <?php } ?>

            
        </tbody>
    </table>
    <p></p>






<!--<button id="saveToDb" name="submit" style="margin-top:20px">Approve</button>-->

</form>



