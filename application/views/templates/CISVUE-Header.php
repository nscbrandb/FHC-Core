<?php
$includesArray = array(
	'title' => $title ?? 'FH-Complete',
	'vue3' => true,
	'bootstrap5' => true,
	'fontawesome6' => true,
	'axios027' => true,
	'customJSModules' => array_merge([
		'public/js/apps/Cis.js'
	], $customJSModules ?? []),
	'customCSSs' => array_merge([
		'public/css/Cis4/Cis.css'
	], $customCSSs ?? [])
);

$this->load->view('templates/FHC-Header', $includesArray);


if (!isset($menu)) {
	$ci =& get_instance(); // get CI instance
	$ci->load->model('content/Content_model', 'ContentModel');
	$cis4_content_id = $ci->ContentModel->getMenuContentID();
	$result = $ci->ContentModel->getMenu($cis4_content_id, get_uid());
	$menu = getData($result)->childs ?? [];
}
?>

<script type="text/javascript">
	if (window.self !== window.top)
		document.body.classList.add("in-frame");
</script>

<header id="cis-header" class="navbar-dark">
	<cis-menu 
		root-url="<?= site_url(''); ?>" 
		logo-url="<?= base_url('/public/images/logo-300x160.png'); ?>" 
		avatar-url="<?= site_url('Cis/Pub/bild/person/' . getAuthPersonId()); ?>" 
		logout-url="<?= site_url('Cis/Auth/logout'); ?>"
		:selectedtypes="selectedtypes"
		:searchbaroptions="searchbaroptions" 
		:searchfunction="searchfunction"
		:menu="<?= htmlspecialchars(json_encode(array_values($menu)), ENT_QUOTES, 'UTF-8') ?>"
		></cis-menu>
</header>

<main id="cis-main" class="flex-grow-1 p-4">