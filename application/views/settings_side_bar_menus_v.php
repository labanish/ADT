<?php
	if(!isset($quick_link)){
	$quick_link = null;
	}  
?>

<!--div class="span3">

  <div class="well sidebar-nav-fixed ">
    
    <ul class="well dropdown-menu" role="menu" aria-labelledby="dropdownMenu" style="display: block; position: static; width: 200px; border:none">
        <li class="nav-header">QUICK LINKS</li>
        <li class="divider"></li>
        
        <li><a tabindex="-1" href="<?php echo site_url('genericname_management');?>" class="<?php if($quick_link == "generic"){echo "top_menu_active";}?>">Generic Names </a></li>
        <li class="divider"></li>
        <li><a tabindex="-1" href="<?php echo site_url("brandname_management");?>" class="<?php if($quick_link == "brand"){echo "top_menu_active";}?>">Brand Names </a></li>
        <li class="divider"></li>
        <li class="dropdown-submenu">
        	<a tabindex="-1" href="#">Regimens</a>
        	<ul class="dropdown-menu">
            	<li><a tabindex="-1" href="<?php echo site_url("regimen_management");?>" class="<?php if($quick_link == 'regimen'){echo 'top_menu_active';}?>">View Regimens</a></li>
	        	<li><a href="<?php echo site_url("regimen_drug_management");?>" class="<?php if($quick_link == "regimen_drug"){echo "top_menu_active";}?>">Regimen Drugs</a></li>
	        	<li><a tabindex="-1" href="<?php echo site_url("regimenchange_management");?>" class="<?php if($quick_link == "regimen_change_reason"){echo "top_menu_active";}?>">Regimen change reasons</a></li>
	        </ul>
        </li>
        <li class="divider"></li>
        <li class="dropdown-submenu">
        	<a tabindex="-1" href="#">Drugs</a>
        	<ul class="dropdown-menu">
            	<li><a href="<?php echo site_url("drugcode_management");?>">Drug Codes</a></li>
            	<li><a href="<?php echo site_url("dose_management");?>" class="<?php if($quick_link == "dose"){echo "top_menu_active";}?>">Drug Doses</a></li>
            	<li><a href="<?php echo site_url("indication_management");?>" class="<?php if($quick_link == "indications"){echo "top_menu_active";}?>">Drug Indications</a></li>
            	<li><a href="<?php echo site_url("drugsource_management");?>" class="<?php if($quick_link == "drug_sources"){echo "top_menu_active";}?>">Drug Sources</a></li>
            	<li><a href="<?php echo site_url("drugdestination_management");?>" class="<?php if($quick_link == "drug_destination"){echo "top_menu_active";}?>">Drug Destinations</a></li>
    		</ul>
        </li>
        <li class="divider"></li>
        
		<li class="dropdown-submenu">
        	<a tabindex="-1" href="#">Clients</a>
        	<ul class="dropdown-menu">
              <li><a href="<?php echo site_url("client_management");?>" class="<?php if($quick_link == "client_sources"){echo "top_menu_active";}?>">Client Sources</a></li>
              <li><a href="<?php echo site_url("client_support");?>" class="<?php if($quick_link == "client_supports"){echo "top_menu_active";}?>">Supported By</a></li>
              <li><a href="<?php echo site_url("nonadherence_management");?>" class="<?php if($quick_link == "non_adherence_reason"){echo "top_menu_active";}?>">Non Adherence reasons</a></li>
            </ul>
        </li>
        <li class="divider"></li>
        
		<li class="dropdown-submenu">
        	<a tabindex="-1" href="#">Facility</a>
        	<ul class="dropdown-menu">
              <li><a href="<?php echo site_url("facility_management");?>" class="<?php if($quick_link == "facility"){echo "top_menu_active";}?>">Facility Information</a></li>
              <li><a href="<?php echo site_url("patient_management/export");?>" class="<?php if($quick_link == "export"){echo "top_menu_active";}?>">Export Patient Master File</a></li>
              
            </ul>
        </li>
        
      </ul>
  </div><!--/.well -->
<!--/div--><!--/span-->