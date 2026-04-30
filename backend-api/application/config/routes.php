<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Route definitions for the alumni backend API.

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes with
| underscores in the controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Registration API routes
$route['api/auth/register'] = 'api/auth/register';
$route['api/auth/verify-email'] = 'api/auth/verify_email';
$route['api/auth/login'] = 'api/auth/login';
$route['api/auth/logout'] = 'api/auth/logout';
$route['api/auth/forgot-password'] = 'api/auth/forgot_password';
$route['api/auth/reset-password'] = 'api/auth/reset_password';
$route['api/auth/me'] = 'api/auth/me';

// Profile management routes
$route['api/profile']['get'] = 'api/profile/index';
$route['api/profile']['post'] = 'api/profile/save';

$route['api/profile/degrees']['post'] = 'api/profile/add_degree';
$route['api/profile/degrees/(:num)']['put'] = 'api/profile/update_degree/$1';
$route['api/profile/degrees/(:num)']['delete'] = 'api/profile/delete_degree/$1';

$route['api/profile/certifications']['post'] = 'api/profile/add_certification';
$route['api/profile/certifications/(:num)']['put'] = 'api/profile/update_certification/$1';
$route['api/profile/certifications/(:num)']['delete'] = 'api/profile/delete_certification/$1';

$route['api/profile/licenses']['post'] = 'api/profile/add_license';
$route['api/profile/licenses/(:num)']['put'] = 'api/profile/update_license/$1';
$route['api/profile/licenses/(:num)']['delete'] = 'api/profile/delete_license/$1';

$route['api/profile/short-courses']['post'] = 'api/profile/add_short_course';
$route['api/profile/short-courses/(:num)']['put'] = 'api/profile/update_short_course/$1';
$route['api/profile/short-courses/(:num)']['delete'] = 'api/profile/delete_short_course/$1';

$route['api/profile/employment-history']['post'] = 'api/profile/add_employment';
$route['api/profile/employment-history/(:num)']['put'] = 'api/profile/update_employment/$1';
$route['api/profile/employment-history/(:num)']['delete'] = 'api/profile/delete_employment/$1';

$route['api/profile/upload-image']['post'] = 'api/profile/upload_image';

// Bidding system routes
$route['api/bids']['post'] = 'api/bids/place_bid';
$route['api/bids/(:num)']['put'] = 'api/bids/update_bid/$1';
$route['api/bids/status']['get'] = 'api/bids/my_bid_status';
$route['api/bids/remaining-slots']['get'] = 'api/bids/remaining_slots';
$route['api/bids/select-winner']['post'] = 'api/bids/select_winner';

$route['api/public/featured-today']['get'] = 'api/public_api/featured_today';
$route['api/public/featured-by-date']['get'] = 'api/public_api/featured_by_date';

// API key management routes
$route['api/keys']['get'] = 'api/api_keys/index';
$route['api/keys']['post'] = 'api/api_keys/create';
$route['api/keys/(:num)/revoke']['post'] = 'api/api_keys/revoke/$1';
$route['api/keys/usage-logs']['get'] = 'api/api_keys/usage_logs';

// CW2 alumni viewing routes
$route['api/alumni']['get'] = 'api/alumni/index';
$route['api/alumni']['options'] = 'api/alumni/index';

$route['api/alumni/(:num)']['get'] = 'api/alumni/show/$1';
$route['api/alumni/(:num)']['options'] = 'api/alumni/show/$1';

// CW2 analytics routes
$route['api/analytics/summary']['get'] = 'api/analytics/summary';
$route['api/analytics/summary']['options'] = 'api/analytics/summary';

$route['api/analytics/alumni-by-programme']['get'] = 'api/analytics/alumni_by_programme';
$route['api/analytics/alumni-by-programme']['options'] = 'api/analytics/alumni_by_programme';

$route['api/analytics/employment-by-sector']['get'] = 'api/analytics/employment_by_sector';
$route['api/analytics/employment-by-sector']['options'] = 'api/analytics/employment_by_sector';

$route['api/analytics/top-job-titles']['get'] = 'api/analytics/top_job_titles';
$route['api/analytics/top-job-titles']['options'] = 'api/analytics/top_job_titles';

$route['api/analytics/top-employers']['get'] = 'api/analytics/top_employers';
$route['api/analytics/top-employers']['options'] = 'api/analytics/top_employers';

$route['api/analytics/certification-growth']['get'] = 'api/analytics/certification_growth';
$route['api/analytics/certification-growth']['options'] = 'api/analytics/certification_growth';

$route['api/analytics/geographic-distribution']['get'] = 'api/analytics/geographic_distribution';
$route['api/analytics/geographic-distribution']['options'] = 'api/analytics/geographic_distribution';