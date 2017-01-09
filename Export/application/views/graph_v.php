<?php
    $chartSize=0;
	if($resultArraySize<=6){
		$chartSize='300';
	}
	if($resultArraySize==7){
		$chartSize='400';
	}
	if($resultArraySize>7){
		$chartSize='600';
	}
	if($resultArraySize>10){
		$chartSize='900';
	}
	if($resultArraySize>15){
		$chartSize='1200';
	}
	if($resultArraySize>20){
		$chartSize='1500';
	}
	if($resultArraySize>25){
		$chartSize='3000';
	}
?>

<script>
	$(function () {
	
		$('<?php echo "#" . $container; ?>').highcharts({
			chart:{
				height:<?php echo $chartSize;?>,
			    type:'<?php echo $chartType ?>',
			},
			title: {
			    text:'<?php echo $chartTitle; ?>',
			    x: -20
			},
			xAxis:{
			    categories: <?php echo $categories; ?>,
			    title: {
			        text: '<?php echo $xAxix; ?>'
			    }
		    },
			yAxis: {
				min: 0,
				title: {
						text: '<?php echo $yAxix; ?>',
					    align: 'high'
				},
				labels: {
				    overflow: 'justify'
				}
			},
			tooltip: {
				valueSuffix: '<?php echo $suffix; ?>'
			},
			plotOptions: {
				column: {
				    dataLabels: {
				        enabled: true
				    }
				}
			},
			legend: {
	            layout: 'vertical',
	            align: 'right',
	            verticalAlign: 'middle',
	            borderWidth: 0
	        },
			credits: {
				enabled: false
			},
			series:<?php echo $resultArray?>
		});
    });
</script>
<div class="graph" style="height:auto !important;zoom:1.3;">
	<div id="<?php echo $container?>"  style="width:98%"></div>
</div>



