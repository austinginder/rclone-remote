<?php 

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://austinginder.com
 * @since             1.0.0
 * @package           Rclone_Remote
 *
 * @wordpress-plugin
 * Plugin Name:       Rclone Remote
 * Plugin URI:        http://github.com/austinginder/rclone-remote/
 * Description:       Turns WordPress into a Rclone HTTP remote. This was created as an experiment. Not intended for production use.
 * Version:           1.0.0
 * Author:            Austin Ginder
 * Author URI:        https://austinginder.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rclone-remote
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'RCLONE_REMOTE_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-rclone-remote-activator.php
 */
function activate_rclone_remote() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rclone-remote-activator.php';
	Rclone_Remote_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-rclone-remote-deactivator.php
 */
function deactivate_rclone_remote() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rclone-remote-deactivator.php';
	Rclone_Remote_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_rclone_remote' );
register_deactivation_hook( __FILE__, 'deactivate_rclone_remote' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-rclone-remote.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_rclone_remote() {

	$plugin = new Rclone_Remote();
	$plugin->run();

}
run_rclone_remote();


// Process ajax events
add_action( 'wp_ajax_rclone_remote_ajax', 'rclone_remote_ajax' );

function rclone_remote_ajax() {
	global $wpdb; // this is how you get access to the database

    // Only proceed if logged in user is an administrator
	if ( ! current_user_can('administrator') ) {
        return true;
        wp_die();
    }

    $cmd = $_POST['command'];
    $value = $_POST['value'];

    if ( $cmd == 'toggle-rclone-remote' ) {
        if ( $value == "true" ) {
            $response = update_option("rclone_remote_enabled", true );
        } else {
            delete_option("rclone_remote_enabled");
            $response = false;
        }
        echo json_encode( $response );
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}

add_action( 'admin_menu', 'rclone_remote_add_admin_menu' );


function rclone_remote_add_admin_menu() { 

	add_options_page( 'Rclone Remote', 'Rclone Remote', 'manage_options', 'rclone_remote', 'rclone_remote_options_page' );

}

function rclone_remote_options_page() {
    
    $site_name = parse_url( home_url() )["host"]; 
    $site_name = str_replace(".","-", $site_name );
    $remote_url = get_home_url() . '/rclone_remote/' . get_option("rclone_remote_token") . "/";

    ?>
<script src="https://unpkg.com/qs@6.5.2/dist/qs.js"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vue@2.5.22/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@1.4.3/dist/vuetify.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/vuetify@1.4.3/dist/vuetify.min.css" rel="stylesheet">

<form action='options.php' method='post'>

    <h2>Rclone Remote</h2>
    
    <p>If you have access to <code>sftp</code> or <code>ftp</code> please use that with Rclone instead of this PHP script.</p>

<div id="app">
    <h3>Configurations</h3>
    <v-switch v-model="enabled" label="Enable Rclone Remote (via PHP)" color="blue darken-3" @change="toggleRcloneRemote()"></v-switch>
    <v-progress-circular v-show="enabled_loading" :indeterminate="enabled_loading" :value="0" size="24" class="ml-2"></v-progress-circular>
    <div v-show="enabled && enabled_loading == false">
    <code>{{ remote_url }}</code> (Warning this is now public)
    <p>&nbsp;</p>
    <h3>Usage Instructions</h3>
    <p>Step 1: Add to Rclone <br /><code>rclone config create {{ site_name }} http url {{ remote_url }}</code></p>
    <p>Step 2: Sync locally <br /><code>rclone sync {{ site_name }}: ~/Downloads/{{ site_name }}/ --no-check-certificate --progress</code></p>
    <p>Step 3: Clean up fake .html file extensions <br /><code>find ~/Downloads/{{ site_name }} -type f -iname '*.rclone-serve.html' -print0 | xargs -0 -n1 bash -c 'mv "$0" "${0/.rclone-serve.html/}"'</code></p>
    <p>&nbsp;</p>
    <h3>Known issues</h3>
    <p>1. Rclone HTTP remote doesn't fully support basic auth so the remote file listings are publicly exposed. Please disable when sync is done other wise others could download a copy of your site with the random token.</p>
    <p>2. Rclone HTTP remote doesn't support streaming files from a script url like <code>/rclone/?file=wp-config.php</code>. Most web server will serve various file extentions directly which would bypass this PHP script. As a workaround the PHP script will serve all files as .rclone-serve.html which allows files to be delivered via Rclone. This requires some hacky bash cleanup as noted above.</p>
    <p>3. Rclone attempts to use many concurrent checkers and transfers. This is likely to result in <code>HTTP Error 429: 429 Too Many Requests</code> with most web hosts. As a workaround try using <code>--transfers 2 --checkers 2</code>.
    </div>
    
</div>

</form>
<script>
var app = new Vue({
  el: '#app',
  data: {
    enabled: <?php if (get_option("rclone_remote_enabled")) { echo "true"; } else { echo "false"; } ?>,
    enabled_loading: false,
    remote_url: "<?php echo $remote_url; ?>",
    site_name: "<?php echo $site_name; ?>"
  },
  methods: {
    toggleRcloneRemote() {
        this.enabled_loading = true


        // Prep AJAX request
        var data = {
            'action': 'rclone_remote_ajax',
            'command': "toggle-rclone-remote",
            'value': this.enabled,
        };

        self = this;

        // update server
        axios.post( '/wp-admin/admin-ajax.php', Qs.stringify( data ) )
            .then( response => {
                self.enabled_loading = false
            })
            .catch(error => {
                console.log(error.response)
            });
    }
  }
})
</script>
<?php

}

// Function to check the string is ends 
function rclone_remote_endsWith($string, $endString) { 
	$len = strlen($endString); 
	if ($len == 0) { 
		return true; 
	} 
	return (substr($string, -$len) === $endString); 
}

function rclone_remote_getFileList($dir) {

  if(function_exists('mime_content_type')) {
    $finfo = FALSE;
  } else {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
  }

  // array to hold return value
  $retval = [];

  // add trailing slash if missing
  if(substr($dir, -1) != "/") {
    $dir .= "/";
  }

  // open pointer to directory and read list of files
  $d = @dir($dir);
  if (!$d) {
      return false;
  }
  while(FALSE !== ($entry = $d->read())) {
    // skip hidden files
    if($entry{0} == ".") continue;
    if(is_dir("{$dir}{$entry}")) {
      $retval[] = [
        'name' => "{$entry}/",
        'type' => filetype("{$dir}{$entry}"),
        'size' => 0,
        'lastmod' => filemtime("{$dir}{$entry}")
      ];
    } elseif(is_readable("{$dir}{$entry}")) {
      $retval[] = [
        'name' => "{$entry}",
        'type' => ($finfo) ? finfo_file($finfo, "{$dir}{$entry}") : mime_content_type("{$dir}{$entry}"),
        'size' => filesize("{$dir}{$entry}"),
        'lastmod' => filemtime("{$dir}{$entry}")
      ];
    }
  }
  $d->close();

  // Rename all files to .html as workaround for modern host providers
  foreach($retval as $key => $file) {
      if ( ! rclone_remote_endsWith($file["name"], "/") ) {
        $retval[$key]["name"] = $retval[$key]["name"] . ".rclone-serve.html";
      }
  }

  return $retval;
}

function rclone_remote_serve_files() {
    global $wp;

    // Only proceed if there is a request
    if ( ! $wp || ! $wp->request ) {
       return true;
    }

    $token = get_option("rclone_remote_token");
    $rclone_remote = "rclone_remote/${token}";

    // Only proceed if there is an $rclone_remote token in the url
    if ( $wp && $wp->request && strpos($wp->request, $rclone_remote) === false ) { 
        return true;
    }

    // Only proceed if Rclone Remote is enabled `/wp-admin/options-general.php?page=rclone_remote`
    if ( get_option("rclone_remote_enabled") != true ) { 
        return true;
    }

//    // Basic Auth - Currently works with Rclone for directory listings however not for files.
//    $user = $_SERVER['PHP_AUTH_USER'];
//    $pass = $_SERVER['PHP_AUTH_PW'];
//
//    $validated = false;
//
//    if ( $user == RCLONE_BASIC_AUTH_USER && $pass == RCLONE_BASIC_AUTH_PASS ) {
//        $validated = true;
//    }
//
//    if (!$validated) {
//        header('WWW-Authenticate: Basic realm="My Realm"');
//        header('HTTP/1.0 401 Unauthorized');
//        die ("Not authorized");
//    }

    status_header(200);

    // Load admin function as it's too early for WordPress to include it
    function get_home_path() {
        $home    = set_url_scheme( get_option( 'home' ), 'http' );
        $siteurl = set_url_scheme( get_option( 'siteurl' ), 'http' );
        if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
            $wp_path_rel_to_home = str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
            $pos = strripos( str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ), trailingslashit( $wp_path_rel_to_home ) );
            $home_path = substr( $_SERVER['SCRIPT_FILENAME'], 0, $pos );
            $home_path = trailingslashit( $home_path );
        } else {
            $home_path = ABSPATH;
        }
    
        return str_replace( '\\', '/', $home_path );
    }

    $current_directory = str_replace($rclone_remote, "", $wp->request);
    $basedir = get_home_path() . $current_directory;
    
    // single directory
    $dirlist = rclone_remote_getFileList( $basedir );

    // return directory listing
    if ( is_array( $dirlist ) ) {

        ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
<head>
    <title>Index of <?php echo $current_directory; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.2/css/bulma.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
        }
        table {
            margin: auto;
        }
        table td {
            padding: 0 10px;
        }
    </style>
</head>
<body>
<h1>Index of <?php echo $current_directory; ?></h1>
<table>
<tr>
    <td>Name</td>
    <td>Last modified</td>
    <td>Size</td>
</tr>
<tr>
    <td colspan="4"><hr><a href="../">Parent Directory</a><br /><br /></td>
</tr>

<?php foreach ($dirlist as $file) { ?>
<tr> 
    <td><a href="<?php echo $file['name']; ?>"><?php echo $file['name']; ?></a></td>
    <td><?php echo date('d-M-Y H:i', $file['lastmod']); ?></td>
    <td><?php echo $file['size'] . "\n"; ?></td>
</tr>
<?php } ?>
<hr>
</body></html><?php  
        die();

    } 

    // return file
    if ( ! $dirlist ) {

        $file = get_home_path() . str_replace($rclone_remote . "/", "", $wp->request);
        if ( rclone_remote_endsWith($file, ".rclone-serve.html") ) {
            $file = str_replace(".rclone-serve.html", "", $file);
        }

        if ( !is_file($file)) {
            status_header(404);
            die("404 &#8212; File not found. $file");
        }
        $mime = wp_check_filetype($file);
        if( false === $mime[ 'type' ] && function_exists( 'mime_content_type' ) )
            $mime[ 'type' ] = mime_content_type( $file );

        if( $mime[ 'type' ] )
            $mimetype = $mime[ 'type' ];
        else
            $mimetype = 'image/' . substr( $file, strrpos( $file, '.' ) + 1 );

        header( 'Content-Type: ' . $mimetype ); // always send this
        if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) )
            header( 'Content-Length: ' . filesize( $file ) );

        $last_modified = gmdate( 'D, d M Y H:i:s', filemtime( $file ) );
        $etag = '"' . md5( $last_modified ) . '"';
        header( "Last-Modified: $last_modified GMT" );
        header( 'ETag: ' . $etag );
        header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 100000000 ) . ' GMT' );

        // Support for Conditional GET
        $client_etag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) : false;

        if( ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) )
            $_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;

        $client_last_modified = trim( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
        // If string is empty, return 0. If not, attempt to parse into a timestamp
        $client_modified_timestamp = $client_last_modified ? strtotime( $client_last_modified ) : 0;

        // Make a timestamp for our most recent modification...
        $modified_timestamp = strtotime($last_modified);

        if ( ( $client_last_modified && $client_etag )
            ? ( ( $client_modified_timestamp >= $modified_timestamp) && ( $client_etag == $etag ) )
            : ( ( $client_modified_timestamp >= $modified_timestamp) || ( $client_etag == $etag ) )
            ) {
            status_header( 304 );
            exit;
        }

        header("Content-Disposition: attachment; filename=" . urlencode(basename($file)));

        flush();

        // If we made it this far, just serve the file
        readfile( $file );
        die();
    }
     
}
add_action( 'template_redirect', 'rclone_remote_serve_files' );