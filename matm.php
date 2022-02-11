<?php
require('vendor/autoload.php');
	class matm_toolbox {
		/**
		 * Plugin Name: MATM Tools
		 * Version: 2.2.6
		 */

		//register options

		public $webps;
		public $sizes;
		public $svgs;
		public $css;
		public $criticalcss;
		public $cssOutput;
		public $cssFooterOutput;
		public $cssFooterOutputDone;
		public $cssFooter;

		public $criticalCSSGenerated;
		public $criticalCSSNotGenerated;

		public $js;
		public $jsOutput;
		public $jsFooterOutput;
		public $jsFooterOutputDone;
		public $jsExclude;
		public $jsFooter;

		public function __construct() {
			$this->register_updater();
			register_activation_hook( __FILE__, [$this, 'register_matm_options'] );
			//update_option( 'matm_webps', [] );
			//update_option( 'matm_css_files', [] );
			//update_option( 'matm_js_files', [] );

			$this->webps = $this->webps();
			$this->svgs = $this->svgs();

			$this->cssFooterOutput = false;
			$this->cssFooterOutputDone = false;
			$this->cssOutput = "";
			$this->cssExclude = explode(',', get_option('matm_css_files_exclude'));
			$this->cssFooter = explode(',', get_option('matm_css_footer'));
			
			
			
			$this->cssAsyncFiles = [];
			$asyncExclude = get_option('matm_css_aync_files_exclude');
			if(is_array($asyncExclude)) {
				$this->cssAsynExclude = $asyncExclude;
			} else {
				$this->cssAsynExclude = explode(',', $asyncExclude);
			}
			
			$this->jsAsyncFiles = [];
			$jsAsyncExclude = get_option('matm_js_aync_files_exclude');
			if(is_array($jsAsyncExclude)) {
				$this->jsAsynExclude = $jsAsyncExclude;
			} else {
				$this->jsAsynExclude = explode(',', $jsAsyncExclude);
			}
			
			
			$this->criticalCSSGenerated = get_option('matm_critical_css_done');
			$this->criticalCSSNotGenerated = get_option('matm_critical_css_not_done');
			
			
			$this->jsFooterOutput = false;
			$this->jsFooterOutputDone = false;
			$this->jsOutput = "";
			$this->jsExclude = explode(',', get_option('matm_js_files_exclude'));
			$this->jsFooter = explode(',', get_option('matm_js_footer'));
			$this->add_actions();
		}
		
		public function register_updater() {

			$repo               = 'matmltd/matm-toolbox';                 // name of your repository. This is either "<user>/<repo>" or "<team>/<repo>".
			$bitbucket_username = 'MatmWeb';   // your personal BitBucket username
			$bitbucket_app_pass = '9k2au3nC2PzSmEmNtQvP';   // the generated app password with read access

			new \Maneuver\BitbucketWpUpdater\PluginUpdater( __FILE__, $repo, $bitbucket_username, $bitbucket_app_pass );
		}

		public function webps() {
			if($this->webps == null) {
				$this->webps = get_option( 'matm_webps' );
			}
			return $this->webps;
		}
		public function svgs() {
			if($this->svgs == null) {
				$this->svgs = get_option( 'matm_svgs' );
			}
			return $this->svgs;
		}

		public function css() {
			if($this->css == null) {
				$this->css = get_option( 'matm_css_files' );
			}
			return $this->css;
		}

		public function sizes() {
			if($this->sizes == null) {
				$this->sizes = get_option( 'matm_images_sizes' );
			}
			return $this->sizes;
		}

		public function criticalcss() {
			if($this->criticalcss == null) {
				$this->criticalcss = get_option( 'matm_criticalcss_files' );
			}
			return $this->criticalcss;
		}



		public function js() {
			if($this->js == null) {
				$this->js = get_option( 'matm_js_files' );
			}
			return $this->js;
		}
		
		public function is_wplogin(){
    		$ABSPATH_MY = str_replace(array('\\','/'), DIRECTORY_SEPARATOR, ABSPATH);
	    	return ((in_array($ABSPATH_MY.'wp-login.php', get_included_files()) || in_array($ABSPATH_MY.'wp-register.php', get_included_files()) ) || (isset($_GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php') || $_SERVER['PHP_SELF']== '/wp-login.php');
		}

		public function add_actions(  ) {
			add_action( 'wp_head', [ $this, 'critical_css' ] );
			add_action( 'wp_head', [ $this, 'loadCriticalCSS' ] );

			add_action( 'wp_footer', [ $this, 'matm_scripts_footer' ] );
			add_action( 'wp_footer', [ $this, 'matm_styles_footer' ], 999 );

			//add_action( 'wp_footer', [$this, 'outputcrondebug'] );

			add_filter( 'widget_text', [ $this, 'webpify' ] );
			add_filter( 'webpify', [ $this, 'webpify' ], 1 );
			add_filter( 'bj_lazy_load_html', function ( $input ) {
				return apply_filters( 'webpify', $input );
			} );
			add_filter( 'the_content', [ $this, 'webpify' ], 999 );
			add_filter( 'post_thumbnail_html', [ $this, 'webpify' ], 1 );
			add_filter( 'wp_get_attachment_link', [ $this, 'webpify' ], 1 );
			add_filter( 'output_html', [ $this, 'webpify' ], 1 );
			add_filter( 'get_custom_logo', [ $this, 'webpify' ], 1 );


			add_filter( 'webpurl', [ $this, 'webpurl' ], 1 );
			if ( ! is_admin() && !$this->is_wplogin()) {
				add_filter( 'style_loader_tag', [ $this, 'inlineCSS' ] );
				add_filter( 'script_loader_tag', [ $this, 'inlineJS' ] );
			}



			add_filter('matm_css_async_files', function ($i) { return $i;});


			add_action( 'admin_init', [ $this, 'register_settings' ] );
			add_action( 'admin_menu', [ $this, "register_options_page" ] );


			add_action( 'admin_post_mt_clear_cache_css', [ $this, 'clear_cache_css' ] );
			add_action( 'admin_post_mt_clear_cache_js', [ $this, 'clear_cache_js' ] );
			add_action( 'admin_post_mt_clear_cache_webp', [ $this, 'clear_cache_webp' ] );
			add_action( 'admin_post_mt_clear_cache_svg', [ $this, 'clear_cache_svg' ] );

			add_action( 'delete_attachment', [ $this, 'delete_webp_with_attachment' ], 999, 2 );

			add_filter( 'wp_nav_menu', [ $this, 'webpify' ], 10, 2 );
			add_action( 'wp_enqueue_scripts', [ $this, 'move_style_to_footer' ], 101 );


			add_action( 'generate_webp_schedule', [ $this, 'runschedulesendfile' ], 10, 3 );
			add_action( 'generate_css_schedule', [ $this, 'runschedulegeneratecss' ], 10, 3 );

			add_filter( 'template_include', [ $this, 'var_template_include' ], 1000 );



			// SVG uploads
			add_filter( 'upload_mimes', [ $this, 'add_svg_mime_types' ] );
			add_action( 'admin_head', [ $this, 'fix_svg_thumb_display' ] );

			//Cache Control Header
			if ( get_option( "matm_cache_control_enabled", false ) ) {
				add_action( 'send_headers', [ $this, 'addCacheControlHeader' ] );
			}

		}

		public function outputcrondebug() {
			$cron_jobs = get_option( 'cron' );
			echo "<pre>";
			var_dump($cron_jobs);
			echo "</pre>";
		}

		public function critical_css() {
			//$template = get_current_template();
			$template = $GLOBALS['current_theme_template'] ?? [];
			if(file_exists(get_stylesheet_directory() . "/css/critical/" . $template . ".css")) {
				$cssURL = get_stylesheet_directory_uri() . "/css/critical/" . $template . ".css";
				print '<link rel="preload" href="' . $cssURL . '" type="text/css" />';
				print '<link rel="stylesheet" href="' . $cssURL . '" type="text/css" />';
			}
		}

		function move_style_to_footer() {

			if ( ! is_user_logged_in() ) {
				wp_deregister_style( 'dashicons' );
			}

			foreach($this->cssFooter as $name) {
				wp_dequeue_style( $name );
			}
			foreach($this->jsFooter as $name) {
				wp_dequeue_script( $name );
			}
		}

		public function clear_cache_css() {
			update_option( 'matm_css_files', [] );
			update_option( 'matm_criticalcss_files', [] );
			foreach(get_option('matm_critical_css_done', []) as $file) {
				update_option( '_matm_styles_' . $file, "" );
            }
			update_option( 'matm_critical_css_done', [] );
			update_option( 'matm_critical_css_not_done', [] );
			print wp_redirect(site_url() . "/wp-admin/options-general.php?page=matmtoolbox");
			exit;
		}

		public function clear_cache_js() {
			update_option( 'matm_js_files', [] );
			print wp_redirect(site_url() . "/wp-admin/options-general.php?page=matmtoolbox");
			exit;
		}

		public function clear_cache_webp() {
			update_option( 'matm_webps', [] );
			print wp_redirect(site_url() . "/wp-admin/options-general.php?page=matmtoolbox");
			exit;
		}

		public function clear_cache_svg() {
			update_option( 'matm_svgs', [] );
			print wp_redirect(site_url() . "/wp-admin/options-general.php?page=matmtoolbox");
			exit;
		}

		public function register_settings() {
			$this->register_matm_options();
			register_setting( "mt_settings", "matm_css_files_enabled", "matm_css_files_enabled_callback" );
			register_setting( "mt_settings", "matm_css_files_exclude", "matm_css_files_exclude_callback" );
			
			register_setting( "mt_settings", "matm_css_async_loading", "matm_css_async_loading_callback" );
			register_setting( "mt_settings", "matm_css_aync_files_exclude", "matm_css_aync_files_exclude_callback" );
			register_setting( "mt_settings", "matm_js_async_loading", "matm_js_async_loading_callback" );
			register_setting( "mt_settings", "matm_js_aync_files_exclude", "matm_js_aync_files_exclude_callback" );
			
			register_setting( "mt_settings", "matm_css_footer", "matm_css_footer_callback" );

			register_setting( "mt_settings", "matm_js_files_enabled", "matm_js_files_enabled_callback" );
			register_setting( "mt_settings", "matm_js_files_exclude", "matm_js_files_exclude_callback" );
			register_setting( "mt_settings", "matm_js_footer", "matm_js_footer_callback" );

			register_setting( "mt_settings", "matm_multisite_dif_domain", "matm_multisite_dif_domain_callback" );

			register_setting( "mt_settings", "matm_cache_control_length", "matm_cache_control_length_callback" );
			register_setting( "mt_settings", "matm_cache_control_enabled", "matm_cache_control_enabled_callback" );

			register_setting( "mt_settings", "matm_image_domain_enabled", "matm_image_domain_enabled_callback" );
			register_setting( "mt_settings", "matm_image_domain", "matm_image_domain_callback" );
		}

		public function register_matm_options() {
			if ( ! get_option( 'matm_css_files' ) ) {
				add_option( 'matm_css_files', [] );
			}
			if ( ! get_option( 'matm_criticalcss_files' ) ) {
				add_option( 'matm_criticalcss_files', [] );
			}
			if ( ! get_option( 'matm_css_files_enabled' ) ) {
				add_option( 'matm_css_files_enabled', [] );
			}
			
			
			
			
			if ( ! get_option( 'matm_css_async_loading' ) ) {
				add_option( 'matm_css_async_loading', 0 );
			}
			
			if ( ! get_option( 'matm_css_aync_files_exclude' ) ) {
				add_option( 'matm_css_aync_files_exclude', '');
			}
			
			if ( ! get_option( 'matm_js_async_loading' ) ) {
				add_option( 'matm_js_async_loading', 0 );
			}
			
			if ( ! get_option( 'matm_js_aync_files_exclude' ) ) {
				add_option( 'matm_js_aync_files_exclude', '');
			}
			
			
			
			if ( ! get_option( 'matm_critical_css_done' ) ) {
				add_option( 'matm_critical_css_done', []);
			}
			
			if ( ! get_option( 'matm_critical_css_not_done' ) ) {
				add_option( 'matm_critical_css_not_done', []);
			}
			
			
			
			
			
			if ( ! get_option( 'matm_js_files' ) ) {
				add_option( 'matm_js_files', [] );
			}
			if ( ! get_option( 'matm_multisite_dif_domain' ) ) {
				add_option( 'matm_multisite_dif_domain', false );
			}
			if ( ! get_option( 'matm_image_domain_enabled' ) ) {
				add_option( 'matm_image_domain_enabled', [] );
			}
			if ( ! get_option( 'matm_image_domain' ) ) {
				add_option( 'matm_image_domain', [] );
			}
			if ( ! get_option( 'matm_js_files_enabled' ) ) {
				add_option( 'matm_js_files_enabled', [] );
			}
			if ( ! get_option( 'matm_js_files_exclude' ) ) {
				add_option( 'matm_js_files_exclude', '' );
			}
			if ( ! get_option( 'matm_webps' ) ) {
				add_option( 'matm_webps', [] );
			}
			if ( ! get_option( 'matm_svgs' ) ) {
				add_option( 'matm_svgs', [] );
			}
			if ( ! get_option( 'matm_css_footer' ) ) {
				add_option( 'matm_css_footer', "" );
			}
			if ( ! get_option( 'matm_js_footer' ) ) {
				add_option( 'matm_js_footer', "" );
			}
			if ( ! get_option( 'matm_cache_control_enabled' ) ) {
				add_option( 'matm_cache_control_enabled', false );
			}
			if ( ! get_option( 'matm_cache_control_length' ) ) {
				add_option( 'matm_cache_control_length', 0 );
			}
		}

		public function schedulecheckwebp() {

		}

		public function schedulesendfile($src, $abs, $wepbPath){
			wp_schedule_single_event(time()-5, 'generate_webp_schedule', [$src, $abs, $wepbPath]);
			spawn_cron();
		}


		public function scheduleGenerateCSS($url, $template){
			$this->criticalCSSNotGenerated = get_option('matm_critical_css_not_done');
			if(!isset($this->criticalCSSNotGenerated[basename($template)])) {
				$this->criticalCSSNotGenerated[basename($template)] = $url;
				update_option('matm_critical_css_not_done', $this->criticalCSSNotGenerated);
				
				wp_schedule_single_event(time()-5, 'generate_css_schedule', [$url, $template]);
				spawn_cron();
			}
		}

		public function runschedulesendfile($src, $abs, $webpPath){
			//		file_put_contents(__DIR__ . '/debug.txt', json_encode([$src, $abs, $webpPath]), FILE_APPEND);
			$this->webps();
			if($this->sendfile($abs, $webpPath)) {
				$sizes = $this->getImageSizes($src);
				$this->webps[ $src ] = [ 'checked' => time(), 'webp' => true, 'sizes' => $sizes];
			}
			update_option('matm_webps', $this->webps);
		}

		public function sendfile($input, $output) {
			$target_url = "http://webp.jaketc.org.uk/convert.php";
			if (function_exists('curl_file_create')) { // php 5.5+
				$cFile = curl_file_create($input);
			} else { //
				$cFile = '@' . realpath($input);
			}
			$post = array('extra_info' => '123456','file_contents'=> $cFile);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$target_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_POST,1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			$result=curl_exec ($ch);
			curl_close ($ch);
			//		file_put_contents(__DIR__ . '/debugfile.webp', $result);
			if(strlen($result) > 1) {
///				file_put_contents(__DIR__ . '/debug.txt', "\r\n\r\nPreppring to save", FILE_APPEND);
				if(file_put_contents($output, $result)) {
					//file_put_contents(__DIR__ . '/debug.txt', "\r\nSaved", FILE_APPEND);
					return true;
				} else {
//					file_put_contents(__DIR__ . '/debug.txt', "\r\nSaving failed", FILE_APPEND);
					return false;
				}
			} else {
				return false;
			}
		}

		/**
		 * @param $src
		 *
		 * @return string
		 */
		public function webpurl($src) {
			$this->webps();
			if($this->is_multisite()) {
				$parsedSite = parse_url( network_site_url());
				$parsedFile = parse_url( str_replace( rtrim(site_url(), '/'),  rtrim(network_site_url(), '/'), $src ));
			} else {
				$parsedSite = parse_url( site_url() );
				$parsedFile = parse_url( $src );
			}
			
			if(isset($parsedSite['path'])) {
				$parsedFile['path'] = str_replace($parsedSite['path'], "", $parsedFile['path']);
            }
			
			if (isset( $parsedFile['host'] ) && $parsedFile['host'] == $parsedSite['host'] ) {
				$abs      = preg_replace( '/http(s|):\/\/' . $parsedSite['host'] . '(.*?)$/', MATM_BASE_DIR . $parsedFile['path'], $src );
				if( ! isset( $this->webps[ $src ] ) || ( $this->webps[ $src ]['checked'] + 172800 ) <= time() ) {
					$wepbPath = $abs . ".webp";
					if ( file_exists( $wepbPath ) ) {
						$this->webps[ $src ] = [ 'checked' => time(), 'webp' => true];
						update_option('matm_webps', $this->webps);
						return $src . ".webp";
					} else {
						if ( ! isset( $this->webps[ $src ] ) ) {
							// if ( $this->sendfile( $abs, $wepbPath ) ) {
// 								if ( file_exists( $wepbPath ) ) {
// 									$this->webps[ $src ] = [ 'checked' => time(), 'webp' => true ];
//
// 									return $wepbPath;
// 								}
// 							}
							$this->schedulesendfile($src, $abs, $wepbPath);
							$this->webps[ $src ] = [ 'checked' => time(), 'webp' => false ];
							update_option('matm_webps', $this->webps);
							return $src;
						}
					}
					$this->webps[ $src ] = [ 'checked' => time(), 'webp' => false];
					update_option('matm_webps', $this->webps);
				} elseif ( $this->webps[ $src ]['webp'] ) {
					return $src . ".webp";
				}
			}
			return $src;
		}

		public function is_multisite() {
			if ( is_multisite() && !get_option( 'matm_multisite_dif_domain', false )) {
			    return true;
		    }
			return false;
		}
		
		public function loadCriticalCSS() {
			if(get_option("matm_css_async_loading") && !($_GET['noasynccss'] ?? false)) {
				global $template;
				$this->criticalCSSGenerated = get_option('matm_critical_css_done');
				if(in_array(basename($template), $this->criticalCSSGenerated)) {
				    $style = get_option("_matm_styles_" . $template, false);
					if($style) {
					    print "<style id='criticalCSS'>";
					    print $style;
					    print "</style>";
				    } else {
					    global $wp;
					    $url = home_url( $wp->request );
					    $this->scheduleGenerateCSS($url, $template);
                    }
				} else {		
					global $wp;
					$url = home_url( $wp->request );
					$this->scheduleGenerateCSS($url, $template);
				}
			}
		}

		public function webpify( $input) {
			$input = preg_replace_callback( '/<img(.*?)src=(\'|")(.*?)(\'|")(.*?)>/', function ($res) {
				preg_match('/class=(\'|")(.*?)(\'|")/', $res[0], $classes);
				if(isset($classes[2])) {
					$classes = $classes[2];
				} else {
					$classes = "";
				}
				$src = $res[3];

				preg_match('/alt=(\'|")(.*?)(\'|")/', $res[0], $alt);
				if(!isset($alt[2]) || $alt[2] == "") {
					$thumbid = attachment_url_to_postid($src);
					$alt = get_post_meta($thumbid, '_wp_attachment_image_alt', TRUE);
				} else {
				    $alt = $alt[2];
                }
				$res[1] = preg_replace('/alt=(\'|")(.*?)(\'|")/', '', $res[1]);
				$res[5] = preg_replace('/alt=(\'|")(.*?)(\'|")/', '', $res[5]);


				if($this->is_multisite()) {
					$parsedSite = parse_url( network_site_url());
					$parsedFile = parse_url( str_replace( rtrim(site_url(), '/'),  rtrim(network_site_url(), '/'), $src ));
				} else {
					$parsedSite = parse_url( site_url() );
					$parsedFile = parse_url( $src );
				}
				if(isset($parsedSite['path'])) {
					$parsedFile['path'] = str_replace($parsedSite['path'], "", $parsedFile['path']);
                }
				$res[1] = $this->removeSrcset($res[1]);
				$res[5] = $this->removeSrcset($res[5]);
				$ext = pathinfo($parsedFile['path'], PATHINFO_EXTENSION);
				if (isset( $parsedFile['host'] ) && $parsedFile['host'] == $parsedSite['host'] ) {
					$abs      = preg_replace( '/http(s|):\/\/' . $parsedSite['host'] . '(.*?)$/', MATM_BASE_DIR . $parsedFile['path'], $src );
					if($ext == "svg" && strpos($classes, "noinlinesvg") === false) {
						if ( ! isset( $this->svgs[ $src ] ) || ( $this->svgs[ $src ]['checked'] + 172800 ) <= time() || TRUE) {
							if(file_exists($abs)) {
								preg_match('/width=(\'|")(.*?)(\'|")/', $res[0], $width);
								if(isset($width[2])) {
									$width = $width[2];
								} else {
									$width = "";
								}

								preg_match('/height=(\'|")(.*?)(\'|")/', $res[0], $height);
								if(isset($height[2])) {
									$height = $height[2];
								} else {
									$height = "";
								}

								$content = file_get_contents($abs);

								$content = "<div class='$classes inlinesvg-holder' style='width: {$width}px;height: {$height}px;'>" . $content . "</div>";
								$this->svgs[ $src ] = [ 'checked' => time(), 'svg' => true, 'content' => $content ];
								return $content;
							} else {
								$this->svgs[ $src ] = [ 'checked' => time(), 'svg' => false, 'content' => '' ];
								return '';
							}
						} elseif($this->svgs[ $src ]['svg']) {
							return $this->svgs[ $src ]['content'];
						}
					}

					if(strpos($res[0], "webpchecked") === false) {
						if ( ! isset( $this->webps[ $src ] ) || ( $this->webps[ $src ]['checked'] + 172800 ) <= time() ) {

							$wepbPath = $abs . ".webp";
							if ( file_exists( $wepbPath ) ) {
								$sizes = $this->getImageSizes($src);
								$thumbid = attachment_url_to_postid($src);
								if(!$thumbid) {
									$mimeType = "";
								} else {
									$mimeType = get_post_mime_type( $thumbid );
								}
								$this->webps[ $src ] = [ 'checked' => time(), 'webp' => true, 'sizes' => $sizes ];
								update_option('matm_webps', $this->webps);
								ob_start();
								foreach ($sizes as $thumb_size) {
									?>
                                    <source type="image/webp" data-srcset="<?=$thumb_size['webpURL']?>" media="(max-width: <?=$thumb_size['width']?>px)"/>
                                    <source type="<?=$mimeType?>" data-srcset="<?=$thumb_size['src']?>" media="(max-width: <?=$thumb_size['width']?>px)"/>
									<?php
								}
								$sizeshtml = ob_get_contents();
								ob_end_clean();

								if(get_option("matm_image_domain_enabled")) {
									$host = parse_url(site_url())['host'];
									$image_host = get_option("matm_image_domain");
									$res[3] = str_replace($host, $image_host, $res[3]);
								}

								return "<picture> " . $sizeshtml . " 
											<source" . $res[1] . " data-here='jere' data-srcset=" . $res[2] . $res[3] . ".webp" . $res[4] . $res[5] . " type='image/webp'>
											<source" . $res[1] . " data-srcset=" . $res[2] . $res[3] . $res[4] . $res[5] . " type='".$mimeType."'>
											<img" . $res[1] . "src='data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==' alt=\"$alt\" data-src=" . $res[2] . $res[3] . $res[4] . $res[5] . " />
										</picture>";
							} else {
								if ( ! isset( $this->webps[ $src ] ) ) {
									$this->schedulesendfile( $src, $abs, $wepbPath );
									$this->webps[ $src ] = [ 'checked' => time(), 'webp' => false ];
									update_option('matm_webps', $this->webps);
								}
							}
						} elseif ( $this->webps[ $src ]['webp'] ) {
							ob_start();
							$thumbid = attachment_url_to_postid($res[3]);
							if(!$thumbid) {
								$mimeType = "";
							} else {
								$mimeType = get_post_mime_type( $thumbid );
							}
							foreach ($this->webps[ $src ]['sizes'] ?? [] as $thumb_size) {
								?>
                                <source type="image/webp" data-srcset="<?=$thumb_size['webpURL']?>" media="(max-width: <?=$thumb_size['width']?>px)"/>
                                <source type="<?=$mimeType?>" data-srcset="<?=$thumb_size['src']?>" media="(max-width: <?=$thumb_size['width']?>px)"/>
								<?php
							}
							$sizeshtml = ob_get_contents();
							ob_end_clean();
							if(get_option("matm_image_domain_enabled")) {
								$host = parse_url(site_url())['host'];
								$image_host = get_option("matm_image_domain");
								$res[3] = str_replace($host, $image_host, $res[3]);
							}
							return "<picture> " . $sizeshtml . " 
											<source" . $res[1] . " data-srcset=" . $res[2] . $res[3] . ".webp" . $res[4] . $res[5] . " type='image/webp'>
											<source" . $res[1] . " data-srcset=" . $res[2] . $res[3] . $res[4] . $res[5] . " type='".$mimeType."'>
											<img" . $res[1] . "src='data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==' alt=\"$alt\" data-src=" . $res[2] . $res[3] . $res[4] . $res[5] . ">
										</picture>";
						}
					}
				}
				return "<img" . $res[1] . " alt=\"$alt\" src=" . $res[2] . $res[3] . $res[4] . $res[5] . ">";
			}, $input );

			$input = preg_replace_callback( '/<img(.*?)>/', function ($res) {
				preg_match_all('/class=("|\')(.*?)("|\')/', $res[0], $classes);
				if(count($classes[2]) > 0) {
					$class = $classes[2][0];
					$res[1] = str_replace($classes[0][0], '', $res[1]);
				} else {
					$class = "";
				}
				return "<img" . $res[1] . " class='" . $class . " webpchecked lazyload'>";
			}, $input);

			update_option('matm_webps', $this->webps());
			return $input;
		}
		public function removeSrcset($input) {
			$input = preg_replace('/srcset=(\'|")(.*?)(\'|")/', '', $input);
			$input = preg_replace('/sizes=(\'|")(.*?)(\'|")/', '', $input);
			return $input;
		}

		public function getImageSizes($src) {
			$sizes = get_intermediate_image_sizes();
			$thumbid = attachment_url_to_postid($src);
			if(!$thumbid) {
				return [];
			}
			$thumbSizes = [];
			$host = parse_url(site_url())['host'];
			$image_host = get_option("matm_image_domain");
			foreach($sizes as $size) {
				$thumbnail = wp_get_attachment_image_src($thumbid, $size);
				if($thumbnail[1] >= 300) {
					if ( get_option( "matm_image_domain_enabled" ) ) {
						$thumbnail[0] = str_replace( $host, $image_host, $thumbnail[0] );
					}
					$thumbSizes[] = [ "width"   => $thumbnail[1],
					                  "src"     => $thumbnail[0],
					                  "webpURL" => apply_filters( 'webpurl', $thumbnail[0] )
					];
				}
			}
			return $thumbSizes;
		}

		function delete_webp_with_attachment($post_id, $post) {
			$src = $post->guid;
			if($this->is_multisite()) {
				$parsedSite = parse_url( network_site_url());
				$parsedFile = parse_url( str_replace( rtrim(site_url(), '/'),  rtrim(network_site_url(), '/'), $src ));
			} else {
				$parsedSite = parse_url( site_url() );
				$parsedFile = parse_url( $src );
			}
			if(isset($parsedSite['path'])) {
				$parsedFile['path'] = str_replace($parsedSite['path'], "", $parsedFile['path']);
            }
			$abs      = preg_replace( '/http(s|):\/\/' . $parsedSite['host'] . '(.*?)$/', MATM_BASE_DIR . $parsedFile['path'], $src );
			if(file_exists($abs . ".webp")) {
				unlink($abs . ".webp");
			}
			$this->webps();
			unset($this->webps[$src]);
			update_option('matm_webps', $this->webps());
		}

		public function inlineCSS( $html ) {
				global $template;
			if(get_option("matm_css_files_enabled")) {
				$this->css();
				$str = "";
				preg_match( '/href=(\'|")(.*?)(\'|")/mi', $html, $output_array );
				$file       = $output_array[2];
				if($this->is_multisite()) {
					$parsedSite = parse_url( network_site_url());
					$parsed = parse_url( str_replace( rtrim(site_url(), '/'),  rtrim(network_site_url(), '/'), $file ));
				} else {
					$parsedSite = parse_url( site_url() );
					$parsed = parse_url( $file );
				}
				$siteHost   = $parsedSite['host'];

				$filename   = basename( $parsed['path'] );
				if (!in_array($filename, $this->cssExclude) &&  isset( $parsed['host'] ) && $parsed['host'] == $siteHost ) {
					if(!isset($this->css[$file]) || ($this->css[$file]['time']+172800) < time()) {
						//print "refreshing";
						$start = microtime();
						$parsedPath = $parsed['path'] ?? "";
						$parsedSitePath = $parsedSite['path'] ?? "";
						$rep = MATM_BASE_DIR . "/" . ((substr($parsedPath, 0, strlen($parsedSitePath)) == $parsedSitePath) ? substr($parsedPath, strlen($parsedSitePath)) : $parsedPath);
						/*print $rep;
						print "<br>";*/
						$abs   = preg_replace( '/http(s|):\/\/' . $parsedSite['host'] . '(.*?)$/', $rep, $file );
						$css   = file_get_contents( $abs );
						$css   = preg_replace( '/url\((\'|")(?!http|data)(.*?)(\'|")\)/', 'url($1' . $parsed['scheme'] . "://" . $parsed['host'] . str_replace( $filename, '', $parsed['path'] ) . '$2$3)', $css );
						$css   = $this->remove_utf8_bom($this->minify_css( $css ));
						$this->css[$file] = ['time' => time(), 'text' => $css];
						/*$return = "<style id='$filename'>" . $css . "</style>";*/
						$return = "<style id='$file'>" . $css . "</style>";
					} else {
						/*$return = "<style id='$filename'>" . $this->css[$file]['text'] . "</style>";*/
						$return = "<style id='$file'>" . $this->css[$file]['text'] . "</style>";
					}
				} else {
					$return = $html;
				}
				update_option('matm_css_files', $this->css);
				if($this->cssFooterOutput && !$this->cssFooterOutputDone) {
					$this->cssOutput .= $return;
					return "";
				} else {
					return $return;
				}
			} elseif(get_option("matm_css_async_loading") && !($_GET['noasynccss'] ?? false) && in_array(basename($template), $this->criticalCSSGenerated)) {
				preg_match( '/href=(\'|")(.*?)(\'|")/mi', $html, $output_array );
				$file       = $output_array[2];
				
				if($this->is_multisite()) {
					$parsed = parse_url( str_replace( rtrim(site_url(), '/'),  rtrim(network_site_url(), '/'), $file ));
				} else {
					$parsed = parse_url( $file );
				}
				$filename   = basename( $parsed['path'] );
				if($filename == "admin-bar.min.css") return $html;

				if(!in_array($filename, $this->cssExclude)) {
					$this->cssAsyncFiles[] = $file;
				}
				return "";
			} else {
				return $html;
			}
		}
		
		public function matm_styles_footer() {
			global $template;
			$this->criticalCSSGenerated = get_option('matm_critical_css_done');
			if(get_option("matm_css_async_loading") && !($_GET['noasynccss'] ?? false) && in_array(basename($template), $this->criticalCSSGenerated) && !($_GET['onlycriticalCSS'] ?? false)) {
				$this->cssAsyncFiles = apply_filters('matm_css_async_files', $this->cssAsyncFiles);
			?>
			<script>
				cssFiles = JSON.parse('<?=json_encode($this->cssAsyncFiles)?>');
				jQuery(window).on('load', function () {
					cssFiles.forEach((item, index) => {
						var link = document.createElement( "link" );
						link.href = item;
						link.type = "text/css";
						link.rel = "stylesheet";
						link.media = "screen,print";

						document.getElementsByTagName( "head" )[0].appendChild( link );
					});
					setTimeout(() => {jQuery("#criticalCSS").remove();}, 1000);
				});
			</script>
			<?php
			}
		}
		
		public function runschedulegeneratecss($url, $template) {
			global $wp;
			$this->criticalCSSGenerated = get_option('matm_critical_css_done');
			$this->criticalCSSNotGenerated = get_option('matm_critical_css_not_done');
			$css = file_get_contents("https://gitdeploy.matm.co.uk/criticalCSS/index.php?url=" . ($url . "?noasynccss=1"));
			if(!in_array(basename($template), $this->criticalCSSGenerated)) {
				$this->criticalCSSGenerated[] = basename( $template );
			}
            update_option('_matm_styles_' . $template, $css);
			update_option('matm_critical_css_done', $this->criticalCSSGenerated);
			unset($this->criticalCSSNotGenerated[basename($template)]);
			update_option('matm_critical_css_not_done', $this->criticalCSSNotGenerated);
			file_put_contents(__DIR__ . "/criticalCSS/" . basename($template) . ".css", $css);
		}


		public function inlineJS( $html ) {
			if(get_option("matm_js_files_enabled")) {
				$this->js();
				$str = "";
				preg_match( '/<script(.*?)src=(\'|")(.*?)(\'|")(.*?)>/mi', $html, $output_array );
				$file       = $output_array[3];
				if($this->is_multisite()) {
					$parsedSite = parse_url( network_site_url());
					$parsed = parse_url( str_replace( rtrim(site_url(), '/'),  rtrim(network_site_url(), '/'), $file ));
				} else {
					$parsedSite = parse_url( site_url() );
					$parsed = parse_url( $file );
				}
				$siteHost   = $parsedSite['host'];
				$filename   = basename( $parsed['path'] );
				if (!in_array($filename, $this->jsExclude) && isset( $parsed['host'] ) && $parsed['host'] == $siteHost ) {
					if(!isset($this->js[$file]) || ($this->js[$file]['time']+172800) < time()) {
						$start = microtime();
						$abs   = preg_replace( '/http(s|):\/\/' . $parsedSite['host'] . '(.*?)$/', MATM_BASE_DIR . $parsed['path'], $file );
						$js   = file_get_contents( $abs );
						$js   = preg_replace( '/url\((\'|")(?!http)(.*?)(\'|")\)/', 'url($1' . $parsed['scheme'] . "://" . $parsed['host'] . str_replace( $filename, '', $parsed['path'] ) . '$2$3)', $js );

						$this->js[$file] = ['time' => time(), 'text' => $js];
						$return = "<script" . $output_array[1] . " async id='$filename' " . $output_array[5] . ">" . $js . "</script>";
					} else {
						$return = "<script" . $output_array[1] . " async id='$filename' " . $output_array[5] . ">" . $this->js[$file]['text'] . "</script>";
					}
				} else {
					$return = $html;
				}
				update_option('matm_js_files', $this->js);
				if($this->jsFooterOutput && !$this->jsFooterOutputDone) {
					$this->jsOutput .= $return;
					return "";
				} else {
					return $return;
				}
			} elseif(get_option("matm_js_async_loading")) {
				
				preg_match( '/<script(.*?)src=(\'|")(.*?)(\'|")(.*?)>/mi', $html, $output_array );
				$file       = $output_array[3];
				
				if($this->is_multisite()) {
					$parsed = parse_url( str_replace( rtrim(site_url(), '/'),  rtrim(network_site_url(), '/'), $file ));
				} else {
					$parsed = parse_url( $file );
				}
				$filename   = basename( $parsed['path'] );
 
				if(!in_array($filename, $this->jsAsynExclude)) {
					$this->jsAsyncFiles[] = $file;				
					return "";
				} else {
					return $html;
				}
			} else {
				return $html;
			}
		}
		
		

		public function matm_scripts_footer() {
			//wp_enqueue_script( 'matm_script', plugin_dir_url( __FILE__ ) . 'assets/scripts.js', array( 'jquery' ), '1.0.0' );
			wp_enqueue_script( 'matm_modernizr_webp', plugin_dir_url( __FILE__ ) . 'assets/modernizr.webp.js',[], '1.0.0' );
			wp_enqueue_script( 'lazyload_script', plugin_dir_url( __FILE__ ) . 'assets/lazysizes.min.js', '1.0.0' );

			foreach($this->cssFooter as $name) {
				wp_enqueue_style( $name );
			}
			foreach($this->jsFooter as $name) {
				wp_enqueue_script( $name );
			}

			if($this->cssFooterOutput) {
				print $this->cssOutput;
				$this->cssFooterOutputDone = true;
			}
			
			
			if(get_option("matm_js_async_loading")) {
			print "Loading them here";
			?>
			<script>
				jsFiles = JSON.parse('<?=json_encode($this->jsAsyncFiles)?>');
				jQuery(window).on('load', function () {
					console.log("loading scripts");
					jsFiles.forEach((item, index) => {
						var link = document.createElement( "script" );

						var script = document.createElement('script');
						script.src = item;
						document.getElementsByTagName('head')[0].appendChild(script);
					});
				});
			</script>
			<?php
			}
			
			
		}
		

		public function register_options_page() {
			add_options_page('Options', 'MATM toolbox Options', 'manage_options', 'matmtoolbox', [$this, 'options_page']);
		}

		function options_page()
		{
			?>
            <div>
                <h2>MATM Toolbox Options</h2>
                <form method="post" action="options.php">
					<?php settings_fields( "mt_settings" ); ?>
                    <table>
                        <tr valign="top">
							<?php $enabled = get_option("matm_css_files_enabled"); ?>
                            <th scope="row" style="text-align: left"><label for="matm_css_files_enabled">Enable Inline CSS</label></th>
                            <td><input type="checkbox" id="matm_css_files_enabled" name="matm_css_files_enabled" value="1" <?=$enabled ? "checked" : "" ?>/></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" style="text-align: left"><label for="matm_css_files_exclude">CSS inline file exclusions</label></th>
                            <td><input type="text" id="matm_css_files_exclude" name="matm_css_files_exclude" value="<?php echo get_option("matm_css_files_exclude"); ?>" /></td>
                        </tr>
                        
                        <tr valign="top">
							<?php $enabled = get_option("matm_css_async_loading"); ?>
                            <th scope="row" style="text-align: left"><label for="matm_css_async_loading">Enable Async CSS loading</label></th>
                            <td><input type="checkbox" id="matm_css_async_loading" name="matm_css_async_loading" value="1" <?=$enabled ? "checked" : "" ?>/></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" style="text-align: left"><label for="matm_css_aync_files_exclude">CSS Async file exclusions</label></th>
                            <td><input type="text" id="matm_css_aync_files_exclude" name="matm_css_aync_files_exclude" value="<?php echo get_option("matm_css_aync_files_exclude"); ?>" /></td>
                        </tr>
                        
                        <tr valign="top">
							<?php $enabled = get_option("matm_js_async_loading"); ?>
                            <th scope="row" style="text-align: left"><label for="matm_js_async_loading">Enable Async JS loading</label></th>
                            <td><input type="checkbox" id="matm_js_async_loading" name="matm_js_async_loading" value="1" <?=$enabled ? "checked" : "" ?>/></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" style="text-align: left"><label for="matm_js_aync_files_exclude">JS Async file exclusions</label></th>
                            <td><input type="text" id="matm_js_aync_files_exclude" name="matm_js_aync_files_exclude" value="<?php echo get_option("matm_js_aync_files_exclude"); ?>" /></td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row" style="text-align: left"><label for="matm_css_footer">CSS move to footer</label></th>
                            <td><input type="text" id="matm_css_footer" name="matm_css_footer" value="<?php echo get_option("matm_css_footer"); ?>" /></td>
                        </tr>
                        <tr valign="top">
							<?php $enabled = get_option("matm_js_files_enabled"); ?>
                            <th scope="row" style="text-align: left"><label for="matm_js_files_enabled">Enable Inline JS</label></th>
                            <td><input type="checkbox" id="matm_js_files_enabled" name="matm_js_files_enabled" value="1" <?=$enabled ? "checked" : "" ?>/></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" style="text-align: left"><label for="matm_js_files_exclude">JS inline file exclusions</label></th>
                            <td><input type="text" id="matm_js_files_exclude" name="matm_js_files_exclude" value="<?php echo get_option("matm_js_files_exclude"); ?>" /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" style="text-align: left"><label for="matm_js_footer">JS move to footer</label></th>
                            <td><input type="text" id="matm_js_footer" name="matm_js_footer" value="<?php echo get_option("matm_js_footer"); ?>" /></td>
                        </tr>
                        <tr valign="top">
		                    <?php $enabled = get_option("matm_image_domain_enabled"); ?>
                            <th scope="row" style="text-align: left"><label for="matm_image_domain_enabled">Enable image domain</label></th>
                            <td><input type="checkbox" id="matm_image_domain_enabled" name="matm_image_domain_enabled" value="1" <?=$enabled ? "checked" : "" ?>/></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" style="text-align: left"><label for="matm_image_domain">Image Domain</label></th>
                            <td><input type="text" id="matm_image_domain" name="matm_image_domain" value="<?php echo get_option("matm_image_domain"); ?>" /></td>
                        </tr>
                        <tr><th>&nbsp;</th><td>&nbsp;</td></tr>
                        <?php if(is_multisite()) { ?>
                        <tr valign="top">
		                    <?php $enabled = get_option("matm_multisite_dif_domain", false); ?>
                            <th scope="row" style="text-align: left"><label for="matm_multisite_dif_domain">Multisite using seperate domains?</label></th>
                            <td><input type="checkbox" id="matm_multisite_dif_domain" name="matm_multisite_dif_domain" value="1" <?=$enabled ? "checked" : "" ?>/></td>
                        </tr>
                    <?php } ?>
                        <tr><th>&nbsp;</th><td>&nbsp;</td></tr>
                        <tr valign="top">
		                    <?php $enabled = get_option("matm_cache_control_enabled", false); ?>
                            <th scope="row" style="text-align: left"><label for="matm_cache_control_enabled">Cache Control Header Enabled</label></th>
                            <td><input type="checkbox" id="matm_cache_control_enabled" name="matm_cache_control_enabled" value="1" <?=$enabled ? "checked" : "" ?>/></td>
                        </tr>
                        <tr valign="top">
		                    <?php $value = get_option("matm_cache_control_length", 0); ?>
                            <th scope="row" style="text-align: left"><label for="matm_cache_control_length">Cache Control Header - Max age</label></th>
                            <td><input type="number" id="matm_cache_control_length" name="matm_cache_control_length" value="<?=$value?>"/></td>
                        </tr>
                    </table>
					<?php  submit_button(); ?>
                </form>
                <h2>Cache</h2>
                <form action="admin-post.php" method="post">
                    <input type="hidden" name="action" value="mt_clear_cache_css">
					<?php  submit_button('Clear CSS Cache'); ?>
                </form>
                <form action="admin-post.php" method="post">
                    <input type="hidden" name="action" value="mt_clear_cache_js">
					<?php  submit_button('Clear JS Cache'); ?>
                </form>
                <form action="admin-post.php" method="post">
                    <input type="hidden" name="action" value="mt_clear_cache_webp">
					<?php  submit_button('Clear webp Cache'); ?>
                </form>
                <form action="admin-post.php" method="post">
                    <input type="hidden" name="action" value="mt_clear_cache_svg">
					<?php  submit_button('Clear SVG Cache'); ?>
                </form>
                
                <h2>Critical CSS Generated</h2>
                <ul>
                	<?php foreach($this->criticalCSSGenerated as $template) {
                		?>
                		<li><?=$template?></li>
                		<?php
                	}
                	?>
                </ul>
                
                <h2>Critical CSS Waiting to be Generated</h2>
                <ul>
                	<?php foreach($this->criticalCSSNotGenerated as $key => $link) {
                		?>
                		<li><?=$key?> - from: <?=$link?></li>
                		<?php
                	}
                	?>
                </ul>
            </div>
			<?php
		}

		function var_template_include( $t ){
			$GLOBALS['current_theme_template'] = basename($t);
			return $t;
		}

		public function addCacheControlHeader(  ) {
            $value = get_option("matm_cache_control_length", 0);
			header( 'Cache-Control: max-age='.$value );
		}


		public function minify_css( $string = '' ) {
			$comments = <<<'EOS'
(?sx)
    # don't change anything inside of quotes
    ( "(?:[^"\\]++|\\.)*+" | '(?:[^'\\]++|\\.)*+' )
|
    # comments
    /\* (?> .*? \*/ )
EOS;

			$everything_else = <<<'EOS'
(?six)
    # don't change anything inside of quotes
    ( "(?:[^"\\]++|\\.)*+" | '(?:[^'\\]++|\\.)*+' )
|
    # spaces before and after ; and }
    \s*+ ; \s*+ ( } ) \s*+
|
    # all spaces around meta chars/operators (excluding + and -)
    \s*+ ( [*$~^|]?+= | [{};,>~] | !important\b ) \s*+
|
    # all spaces around + and - (in selectors only!)
    \s*([+-])\s*(?=[^}]*{)
|
    # spaces right of ( [ :
    ( [[(:] ) \s++
|
    # spaces left of ) ]
    \s++ ( [])] )
|
    # spaces left (and right) of : (but not in selectors)!
    \s+(:)(?![^\}]*\{)
|
    # spaces at beginning/end of string
    ^ \s++ | \s++ \z
|
    # double spaces to single
    (\s)\s+
EOS;

			$search_patterns  = array( "%{$comments}%", "%{$everything_else}%" );
			$replace_patterns = array( '$1', '$1$2$3$4$5$6$7$8' );

			return preg_replace( $search_patterns, $replace_patterns, $string );
		}



		// Allow SVGs to be uploaded
		public function add_svg_mime_types($mimes) {
			$mimes['svg'] = 'image/svg';
			return $mimes;
		}

		public function fix_svg_thumb_display() {
			echo '<style>td.media-icon img[src$=".svg"], img[src$=".svg"].attachment-post-thumbnail { 
                    width: 100% !important; 
                    height: auto !important; 
                 }</style>';
		}

		public function remove_utf8_bom($text)
		{
			$bom = pack('H*','EFBBBF');
			$text = preg_replace("/^$bom/", '', $text);
			return $text;
		}

	}
	$matmtoolbox = new matm_toolbox();
