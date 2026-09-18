<?php
/**
 * Plugin Name: Emerge Mono Maintenance
 * Plugin URI: https://github.com/daisukedesign0924/emerge-mono-maintenance
 * Description: サイト改修中に、管理者以外へ軽量な工事中画面と正しい503応答を表示します。
 * Version: 1.0.5
 * Author: DAISUKE DESIGN
 * Text Domain: emerge-mono-maintenance
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI: https://github.com/daisukedesign0924/emerge-mono-maintenance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EMM_VERSION', '1.0.5' );
define( 'EMM_FILE', __FILE__ );
define( 'EMM_PATH', plugin_dir_path( __FILE__ ) );
define( 'EMM_OPTION', 'emerge_mono_maintenance_settings' );

require_once EMM_PATH . 'includes/class-emerge-mono-github-updater.php';
Emerge_Mono_GitHub_Updater::register( array(
	'name' => 'Emerge Mono Maintenance', 'slug' => 'emerge-mono-maintenance', 'repo' => 'emerge-mono-maintenance',
	'version' => EMM_VERSION, 'file' => EMM_FILE,
) );

function emm_defaults() {
	return array(
		'enabled'       => 0,
		'site_name'     => '',
		'title'         => 'ただいま、サイトを整えています。',
		'message'       => 'より良いサイトへ更新するため、一時的に閲覧を停止しています。しばらくしてから、もう一度お越しください。',
		'end_at'        => '',
		'contact_label' => '',
		'contact_url'   => '',
	);
}

function emm_display_site_name( $settings = null ) {
	$settings = is_array( $settings ) ? $settings : emm_settings();
	$name     = isset( $settings['site_name'] ) ? trim( (string) $settings['site_name'] ) : '';

	if ( '' !== $name ) {
		return $name;
	}

	$name = trim( wp_strip_all_tags( (string) get_bloginfo( 'name' ) ) );
	return '' !== $name ? $name : 'SITE';
}

function emm_settings() {
	$value = get_option( EMM_OPTION, array() );
	return wp_parse_args( is_array( $value ) ? $value : array(), emm_defaults() );
}

function emm_is_expired( $settings ) {
	if ( empty( $settings['end_at'] ) ) {
		return false;
	}
	$end = strtotime( $settings['end_at'] . ' ' . wp_timezone_string() );
	return $end && time() >= $end;
}

function emm_is_exempt_request() {
	if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
		return true;
	}
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return true;
	}
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
	if ( '/robots.txt' === $path || '/favicon.ico' === $path || 0 === strpos( $path, '/wp-login.php' ) ) {
		return true;
	}
	return false;
}

function emm_should_render() {
	$settings = emm_settings();
	$preview  = isset( $_GET['em_maintenance_preview'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['em_maintenance_preview'] ) );
	if ( $preview && is_user_logged_in() && current_user_can( 'manage_options' ) ) {
		return true;
	}
	if ( empty( $settings['enabled'] ) || emm_is_exempt_request() ) {
		return false;
	}
	if ( emm_is_expired( $settings ) ) {
		$settings['enabled'] = 0;
		update_option( EMM_OPTION, $settings, false );
		return false;
	}
	return true;
}

function emm_retry_after_seconds( $settings ) {
	if ( ! empty( $settings['end_at'] ) ) {
		$end = strtotime( $settings['end_at'] . ' ' . wp_timezone_string() );
		if ( $end && $end > time() ) {
			return max( 300, min( DAY_IN_SECONDS, $end - time() ) );
		}
	}
	return HOUR_IN_SECONDS;
}

function emm_render_maintenance() {
	if ( ! emm_should_render() ) {
		return;
	}
	$settings = emm_settings();
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	if ( ! defined( 'LSCACHE_NO_CACHE' ) ) {
		define( 'LSCACHE_NO_CACHE', true );
	}
	status_header( 503 );
	nocache_headers();
	header( 'Retry-After: ' . emm_retry_after_seconds( $settings ) );
	header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset', 'UTF-8' ) );

	$site_name = emm_display_site_name( $settings );
	$title     = $settings['title'];
	$message   = $settings['message'];
	$end_text  = '';
	if ( ! empty( $settings['end_at'] ) ) {
		$timestamp = strtotime( $settings['end_at'] . ' ' . wp_timezone_string() );
		if ( $timestamp ) {
			$end_text = wp_date( 'Y.m.d H:i', $timestamp ) . ' 再開予定';
		}
	}
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $title . ' — ' . $site_name ); ?></title>
	<style>
		:root{color-scheme:dark;--bg:#090a0a;--panel:#0e0f0f;--line:#292b2b;--text:#f4f4f1;--muted:#969a98;--green:#58ef9b}*{box-sizing:border-box}html,body{min-height:100%;margin:0}body{display:grid;place-items:center;background-color:var(--bg);background-image:linear-gradient(rgba(255,255,255,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:32px 32px;color:var(--text);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","Noto Sans JP",sans-serif;padding:24px}.emm-shell{width:min(920px,100%);border:1px solid var(--line);background:rgba(9,10,10,.94)}.emm-bar,.emm-foot{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:18px 22px;border-bottom:1px solid var(--line);font:700 11px/1.3 ui-monospace,SFMono-Regular,Menlo,monospace;letter-spacing:.18em;text-transform:uppercase}.emm-live{display:flex;align-items:center;gap:9px;color:var(--muted)}.emm-dot{width:7px;height:7px;border-radius:50%;background:var(--green);box-shadow:0 0 14px var(--green)}main{padding:clamp(42px,8vw,96px) clamp(24px,8vw,86px)}.emm-index{margin:0 0 28px;color:var(--muted);font:700 11px/1.4 ui-monospace,SFMono-Regular,Menlo,monospace;letter-spacing:.18em}.emm-index:before{content:"01 / ";color:var(--text)}h1{max-width:760px;margin:0;font-size:clamp(38px,7vw,82px);line-height:1.04;letter-spacing:-.055em}p{max-width:620px;margin:32px 0 0;color:#b9bcba;font-size:clamp(15px,2vw,18px);line-height:1.95}.emm-meta{display:flex;flex-wrap:wrap;gap:12px 28px;margin-top:48px;padding-top:22px;border-top:1px solid var(--line);color:var(--muted);font:700 11px/1.5 ui-monospace,SFMono-Regular,Menlo,monospace;letter-spacing:.12em;text-transform:uppercase}.emm-link{color:var(--text);text-decoration:none;border-bottom:1px solid var(--text);padding-bottom:3px}.emm-foot{border-top:1px solid var(--line);border-bottom:0;color:#666a68}@media(max-width:560px){body{padding:12px}.emm-bar,.emm-foot{padding:15px}.emm-bar span:first-child{max-width:65%}main{padding:48px 22px}.emm-foot{align-items:flex-start;flex-direction:column}}
	</style>
</head>
<body>
	<section class="emm-shell" aria-labelledby="emm-title">
		<header class="emm-bar"><span><?php echo esc_html( $site_name ); ?></span><span class="emm-live"><i class="emm-dot"></i>Maintenance</span></header>
		<main>
			<div class="emm-index">SITE UPDATE</div>
			<h1 id="emm-title"><?php echo esc_html( $title ); ?></h1>
			<p><?php echo nl2br( esc_html( $message ) ); ?></p>
			<div class="emm-meta">
				<?php if ( $end_text ) : ?><span><?php echo esc_html( $end_text ); ?></span><?php endif; ?>
				<?php if ( ! empty( $settings['contact_url'] ) && ! empty( $settings['contact_label'] ) ) : ?>
					<a class="emm-link" href="<?php echo esc_url( $settings['contact_url'] ); ?>"><?php echo esc_html( $settings['contact_label'] ); ?> ↗</a>
				<?php endif; ?>
			</div>
		</main>
		<footer class="emm-foot"><span>HTTP 503 / TEMPORARY</span><span>PLEASE TRY AGAIN LATER.</span></footer>
	</section>
</body>
</html>
	<?php
	exit;
}
add_action( 'template_redirect', 'emm_render_maintenance', -9999 );

function emm_admin_menu() {
	add_menu_page(
		'Emerge Mono Maintenance',
		'Maintenance',
		'manage_options',
		'emerge-mono-maintenance',
		'emm_admin_page',
		'dashicons-hammer',
		59
	);
}
add_action( 'admin_menu', 'emm_admin_menu' );

function emm_sanitize_settings( $input ) {
	$output                  = emm_defaults();
	$output['enabled']       = empty( $input['enabled'] ) ? 0 : 1;
	$output['site_name']     = sanitize_text_field( isset( $input['site_name'] ) ? $input['site_name'] : '' );
	$output['title']         = sanitize_text_field( isset( $input['title'] ) ? $input['title'] : '' );
	$output['message']       = sanitize_textarea_field( isset( $input['message'] ) ? $input['message'] : '' );
	$output['end_at']        = sanitize_text_field( isset( $input['end_at'] ) ? $input['end_at'] : '' );
	$output['contact_label'] = sanitize_text_field( isset( $input['contact_label'] ) ? $input['contact_label'] : '' );
	$output['contact_url']   = esc_url_raw( isset( $input['contact_url'] ) ? $input['contact_url'] : '' );
	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
	do_action( 'litespeed_purge_all' );
	return $output;
}

function emm_register_settings() {
	register_setting( 'emm_settings_group', EMM_OPTION, array( 'sanitize_callback' => 'emm_sanitize_settings' ) );
}
add_action( 'admin_init', 'emm_register_settings' );

function emm_admin_assets( $hook ) {
	if ( 'toplevel_page_emerge-mono-maintenance' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'emm-admin', plugin_dir_url( __FILE__ ) . 'assets/admin.css', array(), EMM_VERSION );
}
add_action( 'admin_enqueue_scripts', 'emm_admin_assets' );

function emm_admin_page() {
	$settings    = emm_settings();
	$preview_url = add_query_arg( 'em_maintenance_preview', '1', home_url( '/' ) );
	?>
	<div class="wrap emm-admin">
		<header class="emm-admin-head">
			<div><span>EMERGE MONO / SYSTEM</span><h1>Maintenance</h1></div>
			<div class="emm-status <?php echo $settings['enabled'] ? 'is-live' : ''; ?>"><i></i><?php echo $settings['enabled'] ? 'ACTIVE' : 'OFF'; ?></div>
		</header>

		<section class="emm-panel emm-summary">
			<div><span class="emm-label">CURRENT STATUS</span><strong><?php echo $settings['enabled'] ? '工事中表示を公開中' : '通常サイトを公開中'; ?></strong></div>
			<p>管理者としてログイン中のブラウザには通常サイトが表示されます。シークレットウィンドウで一般訪問者の表示を確認できます。</p>
		</section>

		<form method="post" action="options.php" class="emm-panel emm-form">
			<?php settings_fields( 'emm_settings_group' ); ?>
			<div class="emm-switch-row">
				<div><span class="emm-label">MAINTENANCE MODE</span><h2>工事中表示</h2><p>有効化すると、一般訪問者へ503の工事中画面を表示します。</p></div>
				<label class="emm-switch"><input type="checkbox" name="<?php echo esc_attr( EMM_OPTION ); ?>[enabled]" value="1" <?php checked( $settings['enabled'], 1 ); ?>><span></span></label>
			</div>
			<div class="emm-grid">
				<label><span>表示サイト名</span><input type="text" name="<?php echo esc_attr( EMM_OPTION ); ?>[site_name]" value="<?php echo esc_attr( $settings['site_name'] ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"><small>工事中画面の左上に表示します。未入力時はWordPressの「サイトのタイトル」を使用します。</small></label>
				<label><span>見出し</span><input type="text" name="<?php echo esc_attr( EMM_OPTION ); ?>[title]" value="<?php echo esc_attr( $settings['title'] ); ?>" required></label>
				<label class="is-wide"><span>案内文</span><textarea name="<?php echo esc_attr( EMM_OPTION ); ?>[message]" rows="4" required><?php echo esc_textarea( $settings['message'] ); ?></textarea></label>
				<label><span>自動解除日時</span><input type="datetime-local" name="<?php echo esc_attr( EMM_OPTION ); ?>[end_at]" value="<?php echo esc_attr( $settings['end_at'] ); ?>"><small>未入力の場合は手動で解除します。</small></label>
				<label><span>問い合わせリンク名</span><input type="text" name="<?php echo esc_attr( EMM_OPTION ); ?>[contact_label]" value="<?php echo esc_attr( $settings['contact_label'] ); ?>" placeholder="お問い合わせ"></label>
				<label class="is-wide"><span>問い合わせURL</span><input type="url" name="<?php echo esc_attr( EMM_OPTION ); ?>[contact_url]" value="<?php echo esc_attr( $settings['contact_url'] ); ?>" placeholder="https://"></label>
			</div>
			<footer class="emm-actions">
				<?php submit_button( '設定を保存', 'primary', 'submit', false ); ?>
				<a class="button" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener">プレビュー ↗</a>
			</footer>
		</form>

		<section class="emm-panel emm-notes">
			<span class="emm-label">SEO SAFETY</span><h2>検索評価を守るための動作</h2>
			<ul><li>元URLを別ページへリダイレクトしません。</li><li>一般アクセスには503 Service Unavailableを返します。</li><li>Retry-Afterで再訪問の目安を伝えます。</li><li>robots.txt、ログイン画面、管理画面は通常どおり利用できます。</li><li>長期間の連続利用は避け、改修完了後すぐ解除してください。</li></ul>
		</section>
	</div>
	<?php
}

register_activation_hook(
	__FILE__,
	function () {
		if ( false === get_option( EMM_OPTION, false ) ) {
			add_option( EMM_OPTION, emm_defaults(), '', false );
		}
	}
);
