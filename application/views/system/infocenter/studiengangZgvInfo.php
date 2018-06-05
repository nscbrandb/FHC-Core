<?php
$this->load->view(
	'templates/FHC-Header',
	array(
		'title' => 'ZGV Info',
		'jquery' => true,
		'bootstrap' => true,
		'fontawesome' => true,
		'sbadmintemplate' => true,
		'customCSSs' => 'public/css/sbadmin2/admintemplate_contentonly.css'
	)
);
?>
<body>
	<div id="wrapper">
		<div id="page-wrapper">
			<div class="container-fluid">
				<div class="row">
					<div class="col-lg-12">
						<h3 class="page-header">Zugangsvoraussetzungen <?php echo $studiengang_kurzbz; ?> - <?php echo $studiengang_bezeichnung; ?></h3>
					</div>
				</div>
				<div id="data">
					<?php if (empty($data)): ?>
						Keine Zugangsvoraussetzungen für den Studiengang definiert
						<?php
					else:
						echo json_decode($data);
					endif;
					?>
				</div>
			</div>
		</div>
	</div>
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>