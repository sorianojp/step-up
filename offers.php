<?php
/**
 * offers
 * 
 * @package Sngine
 * @author Zamblek
 */

// fetch bootloader
require('bootloader.php');

// offers enabled
if(!$system['pages_enabled'] || !$system['offers_enabled']) {
	_error(404);
}

// user access
if($user->_logged_in || !$system['system_public']) {
	user_access();
}

try {

	// get view content
	$_GET['view'] = (isset($_GET['view']))? $_GET['view'] : '';
	switch ($_GET['view']) {
		case '':
			// get promoted offers
			$promoted_offers = array();
			$get_promoted = $db->query("SELECT posts.post_id FROM posts INNER JOIN posts_offers ON posts.post_id = posts_offers.post_id WHERE posts.post_type = 'offer' AND posts_offers.end_date >= CURDATE() AND posts.boosted = '1' ORDER BY RAND() LIMIT 3") or _error("SQL_ERROR");
			while($promoted_offer = $get_promoted->fetch_assoc()) {
				$promoted_offer = $user->get_post($promoted_offer['post_id']);
				if($promoted_offer) {
					$promoted_offers[] = $promoted_offer;
				}
			}
			/* assign variables */
			$smarty->assign('promoted_offers', $promoted_offers);

			// prepare query
			/* prepare where query */
			$where_query = "";
			/* prepare pager url */
			$url = "";

			// page header
			page_header($system['system_title'].' - '.__("Offers"), __($system['system_description_offers']));

			// get offers categories
			$categories = $user->get_categories("offers_categories");
			/* assign variables */
			$smarty->assign('categories', $categories);
			break;

		case 'search':
			// check query
			if(!isset($_GET['query']) || is_empty($_GET['query'])) {
				redirect('/offers');
			}
			/* assign variables */
			$smarty->assign('query', $_GET['query']);

			// prepare query
			/* prepare where query */
			$where_query = sprintf('AND (posts.text LIKE %1$s OR posts_offers.title LIKE %1$s)', secure($_GET['query'], 'search') );
			/* prepare pager url */
			$url = "/search/".$_GET['query'];

			// page header
			page_header($system['system_title'].' - '.__("Offers"), __($system['system_description_offers']));

			// get offers categories
			$categories = $user->get_categories("offers_categories");
			/* assign variables */
			$smarty->assign('categories', $categories);
			break;

		case 'category':
			// check category
			$current_category = $user->get_category("offers_categories", $_GET['category_id'], true);
			if(!$current_category) {
				_error(404);
			}
			/* assign variables */
			$smarty->assign('current_category', $current_category);

			// prepare query
			/* prepare where query */
			$where_query = sprintf("AND posts_offers.category_id = %s", secure($current_category['category_id'], 'int'));
			/* prepare pager url */
			$url = "/category/".$current_category['category_id']."/".$current_category['category_url'];

			// page header
			page_header($system['system_title'].' - '.__("Offers").' - '.__($current_category['category_name']), __($current_category['category_description']));

			// get offers categories (only sub-categories)
			if(!$current_category['sub_categories'] && !$current_category['parent']) {
				$categories = $user->get_categories("offers_categories");
			} else {
				$categories = $user->get_categories("offers_categories", $current_category['category_id']);
			}
			/* assign variables */
			$smarty->assign('categories', $categories);
			break;

		default:
			_error(404);
			break;
	}

	// get offers
	require('includes/class-pager.php');
	$params['selected_page'] = ( (int) $_GET['page'] == 0) ? 1 : $_GET['page'];
	$total = $db->query("SELECT COUNT(*) as count FROM posts INNER JOIN posts_offers ON posts.post_id = posts_offers.post_id WHERE posts.post_type = 'offer' AND posts_offers.end_date >= CURDATE()".$where_query) or _error("SQL_ERROR");
	$params['total_items'] = $total->fetch_assoc()['count'];
	$params['items_per_page'] = $system['min_results_even'];
	$params['url'] = $system['system_url'].'/offers'.$url.'/%s';
	$pager = new Pager($params);
	$limit_query = $pager->getLimitSql();

	// get posts
	$rows = array();
	$get_rows = $db->query("SELECT posts.post_id FROM posts INNER JOIN posts_offers ON posts.post_id = posts_offers.post_id WHERE posts.post_type = 'offer' AND posts_offers.end_date >= CURDATE()".$where_query." ORDER BY post_id DESC ".$limit_query) or _error("SQL_ERROR");
	while($row = $get_rows->fetch_assoc()) {
		$row = $user->get_post($row['post_id']);
		if($row) {
			$rows[] = $row;
		}
	}
	/* assign variables */
	$smarty->assign('rows', $rows);
	$smarty->assign('total', $params['total_items']);
	$smarty->assign('pager', $pager->getPager());
	$smarty->assign('view', $_GET['view']);

	// get ads
	$ads = $user->ads('offers');
	/* assign variables */
	$smarty->assign('ads', $ads);
	
} catch (Exception $e) {
	_error(__("Error"), $e->getMessage());
}

// page footer
page_footer("offers");

?>