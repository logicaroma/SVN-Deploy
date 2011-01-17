<?php

require_once( 'core/bootstrap.inc.php' );

$operation = 'default';		

foreach ( $_GET as $key => $value ) {
	$operation = $key;
}

if ( svndeploy_install_check() && !svndeploy_logged() ) {
	header( 'Location: ./' );
	exit;
}

if ( isset( $_POST[ 'svn_bin' ] ) ) {
	// save config
	svndeploy_config_temp( 'SVNDEPLOY_BIN', $_POST[ 'svn_bin' ] );
	svndeploy_config_temp( 'SVNDEPLOY_BASE', $_POST[ 'svn_base' ] );
	svndeploy_config_temp( 'SVNDEPLOY_USR', $_POST[ 'svn_user' ] );
	svndeploy_config_temp( 'SVNDEPLOY_PWD', $_POST[ 'svn_pass' ] );
	svndeploy_config_temp( 'SVNDEPLOY_USE_SUDO', ( isset( $_POST[ 'svn_sudo' ] ) ? 1 : 0 ) );
	svndeploy_config_temp( 'SVNDEPLOY_SVNDEPLOY_USR', $_POST[ 'svndeploy_user' ] );
	svndeploy_config_temp( 'SVNDEPLOY_SVNDEPLOY_PWD', $_POST[ 'svndeploy_pass' ] );
	svndeploy_config_temp_save( $_POST[ 'svn_rand_key' ] );
}

$fatal = 0;
$warn = 0;
$forceback = FALSE;

if ( svndeploy_install_check_temp() ) {
	svndeploy_config_shortcuts_temp();
} else {
	svndeploy_config_shortcuts();
}
			
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="Pragma" content="no-cache" />
		
		<title>SVN Deploy | Installation</title>
		
		<link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="http://www.mutado.com/mobile/feed/" />
		
		<link href="assets/favicon.png" rel="shortcut icon"/>
		<link href="assets/favicon.png" rel="icon"/>
		
		<link type="text/css" rel="stylesheet" href="https://api.mutado.com/mobile/shared/style.css" />
		<link type="text/css" rel="stylesheet" href="https://api.mutado.com/mobile/shared/generic.css" />
		<link type="text/css" rel="stylesheet" href="assets/style.css" />
		
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js"></script>
		<script type="text/javascript" src="js/svndeploy.js"></script>
		
		<script type="text/javascript">
		
		function check_install_form() {
			var err = 0;
			var fields = [
				'svn_bin',
				'svn_user',
				'svn_pass',
				'svn_base',
				'svndeploy_user',
				'svndeploy_pass',
				'svndeploy_pass_check'
			];
			
			fields = fields.reverse();
			
			for ( var field in fields ) {
				var fid = '#' + fields[ field ];
				var yet = false;
				if ( $( fid ).val() == '' ) {
					$( fid ).addClass( 'missing' );
					if ( !yet ) {
						$( fid ).focus();
					}
					err++;
				} else {
					$( fid ).removeClass( 'missing' );
				}
			}
			
			// check pass
			if ( $( '#svndeploy_pass' ).val() == '' || $( '#svndeploy_pass' ).val() != $( '#svndeploy_pass_check' ).val() ) {
				$( '#svndeploy_pass' ).addClass( 'missing' );
				$( '#svndeploy_pass_check' ).addClass( 'missing' );
				err++;
			} else {
				$( '#svndeploy_pass' ).removeClass( 'missing' );
				$( '#svndeploy_pass_check' ).removeClass( 'missing' );
			}
			
			$( "#svn_rand_key" ).val( svndeploy.random_key() );
			return err == 0;
		}	
		
		function back_to_install() {
			history.back();
		}	
		
		function recheck() {
			location.href = 'setup.php?check';
		}
				
		function install_svndeploy() {
			location.href = 'setup.php?process';
		}
		
		function launch_svndeploy() {
			location.href = './';
		}
			
		</script>
		
	</head>
	
	<body class="blue">
		
		<h1><span style="padding: 0 20px 0 0;">SVN Deploy Installation</span><span class="right">ver <?php echo SVNDEPLOY_VER ?> <span class="version <?php echo SVNDEPLOY_STATUS ?>"><?php echo SVNDEPLOY_STATUS ?></span></span></h1>

		<?php switch ( $operation ) :
		
		case 'check' : ?>
		
		<?php if ( !isset( $_SESSION[ SVNDEPLOY_TEMP_CONFIG ] ) ) : ?>
		<script type="text/javascript">
			back_to_install();
		</script>
		<?php endif; ?>
		
		<div id="check">
			<h4>Installation Check...</h4>
			<ul class="list-big">
				
				<!-- Check write permission on /tmp folder -->
				<li>
					<div class="outer">
						<div class="inner">
							<?php
								$check = svndeploy_install_check_tmp();
								$status = $check ? 'release' : 'alpha';
								$msg = $check ? 'OK' : 'Failed';
								if ( !$check ) {
									$fatal++;
									$forceback = TRUE;
								}
							?>
							<label>Check write permission on /tmp folder <span class="version <?php echo $status; ?>"><?php echo $msg; ?></span></label>
							<?php if ( !$check ) : ?>
							<p>Please use chmod 777 to <i>/tmp</i> folder to allow SVN Deploy writing operations.</p>
							<?php else : ?>
							<p>Folders <i>/tmp</i> is writable!<br/>Thank you.</p>
							<?php endif; ?>
							
						</div>
					</div>
				</li>
				
				<!-- Check write permission on /misc folder -->
				<li>
					<div class="outer">
						<div class="inner">
							<?php
								$check = svndeploy_install_check_misc();
								$status = $check ? 'release' : 'alpha';
								$msg = $check ? 'OK' : 'Failed';
								if ( !$check ) {
									$fatal++;
								}
							?>
							<label>Check write permission on /misc folder <span class="version <?php echo $status; ?>"><?php echo $msg; ?></span></label>
							<?php if ( !$check ) : ?>
							<p>Please use chmod 777 to <i>/misc</i> folder to allow SVN Deploy writing operations.</p>
							<?php else : ?>
							<p>Folders <i>/misc/repos</i> and <i>/misc/config</i> successfully created!<br/>Thank you.</p>
							<?php endif; ?>
							
						</div>
					</div>
				</li>
				
				<!-- Check write permission on /.htaccess file -->
				<li>
					<div class="outer">
						<div class="inner">
							<?php
								$check = svndeploy_install_check_htaccess();
								$status = $check ? 'release' : 'beta';
								$msg = $check ? 'OK' : 'Warning';
								if ( !$check ) {
									$warn++;
								}
							?>
							<label>Check write permission on .htaccess <span class="version <?php echo $status; ?>"><?php echo $msg; ?></span></label>
							<?php if ( !$check ) : ?>
							<p>Please use chmod 777 to <i>.htaccess</i> file to allow SVN Deploy writing operations.<br/>Otherwise you can just copy-paste the code below into your <i>.htaccess</i> file.</p><pre class="command"><i><?php echo htmlspecialchars( svndeploy_install_get_htaccess() ); ?></i></pre><br/><p>Thank you.</p>
							<?php else : ?>
							<p>The file <i>.htaccess</i> was created:</p><pre class="command"><i><?php echo htmlspecialchars( svndeploy_core_rootfile_read( '.htaccess' ) ); ?></i></pre><br/><p>Thank you.</p>
							<?php endif; ?>
							
						</div>
					</div>
				</li>
				
				<!-- Check OpenSSL crypto -->
				<li>
					<div class="outer">
						<div class="inner">
							<?php
								$check = svndeploy_install_check_crypto();
								$status = $check ? 'release' : 'alpha';
								$msg = $check ? 'OK' : 'Failed';
								if ( !$check ) {
									$fatal++;
								}
							?>
							<label>Check OpenSSL crypto module <span class="version <?php echo $status; ?>"><?php echo $msg; ?></span></label>
							<?php if ( !$check ) : ?>
							<p>SVN Deploy needs OpenSSL functionality to work properly. Please upgrade your PHP version to <i>5.3+</i> or install OpenSSL command line tool.</p>
							<?php else : ?>
							<p>OpenSSL currently installed.<br/>Thank you.</p>
							<?php endif; ?>
							
						</div>
					</div>
				</li>
				
				<!-- Check php safe mode -->
				<li>
					<div class="outer">
						<div class="inner">
							<?php
								$check = svndeploy_install_check_safe_mode();
								$status = $check ? 'release' : 'alpha';
								$msg = $check ? 'OK' : 'Failed';
								if ( !$check ) {
									$fatal++;
								}
							?>
							<label>Check PHP safe_mode option <span class="version <?php echo $status; ?>"><?php echo $msg; ?></span></label>
							<?php if ( !$check ) : ?>
							<p>SVN Deploy needs PHP <i>safe_mode = off</i>. Please edit your <i>php.ini</i> file to change this setting.</p>
							<?php else : ?>
							<p>PHP <i>safe_mode = off</i><br/>Thank you.</p>
							<?php endif; ?>
							
						</div>
					</div>
				</li>
				
				<!-- Check svn binary -->
				<li>
					<div class="outer">
						<div class="inner">
							<?php
								$check = svndeploy_install_check_svn();
								$status = $check ? 'release' : 'alpha';
								$msg = $check ? 'OK' : 'Failed';
								if ( !$check ) {
									$fatal++;
								}
							?>
							<label>Check SVN client installation <span class="version <?php echo $status; ?>"><?php echo $msg; ?></span></label>
							<?php if ( !$check ) : ?>
							<p>Please check SVN installation.<br/>Exit with error > <i><?php echo svndeploy_svn_ver(); ?></i></p>
							<?php else : ?>
							<p>SVN client version: <i><?php echo svndeploy_svn_ver(); ?></i></p>
							<?php endif; ?>
							
						</div>
					</div>
				</li>
				
				<!-- Check deployment path -->
				<li>
					<div class="outer">
						<div class="inner">
							<?php
								$check = svndeploy_install_check_deployment_path();
								$status = $check ? 'release' : 'alpha';
								$msg = $check ? 'OK' : 'Failed';
								if ( !$check ) {
									$fatal++;
								}
							?>
							<label>Check deployment path <span class="version <?php echo $status; ?>"><?php echo $msg; ?></span></label>
							<?php if ( !$check ) : ?>
							<p>Please check if <i><?php echo SVNDEPLOY_BASE ?></i> exists on your server.</p>
							<?php else : ?>
							<p>Deployment path exists: <i><?php echo SVNDEPLOY_BASE ?></i></p>
							<?php endif; ?>
							
						</div>
					</div>
				</li>
			
				<!-- Check deployment path provileges -->
				<li>
					<div class="outer">
						<div class="inner">
							<?php
								$check = svndeploy_install_check_svn_privileges();
								$status = $check ? 'release' : ( SVNDEPLOY_USE_SUDO ? 'beta' : 'alpha' );
								$msg = $check ? 'OK' : ( SVNDEPLOY_USE_SUDO ? 'Warning' : 'Failed' );
								if ( !$check ) {
									if ( SVNDEPLOY_USE_SUDO ) {
										$warn++;
									} else {
										$fatal++;									
									}
								}
							?>
							<label>Check SVN read/write privileges on deployment path <span class="version <?php echo $status; ?>"><?php echo $msg; ?></span></label>
							<?php if ( !$check ) : ?>
							
								<?php if ( SVNDEPLOY_USE_SUDO ) : ?>
							
								<p>Deployment folder <i><?php echo SVNDEPLOY_BASE ?></i> should be readable/writable by the current apache user <i><?php echo svndeploy_install_whoami(); ?></i>.<br/>You just need to add SUDO privileges for <i><?php echo svndeploy_install_whoami(); ?></i> user to allow SVN Deploy to access your deployment folder.<br/>Please use visudo command to add the following line of code to your <i>sudoers</i> file.<br/><i><pre class="command"><?php echo svndeploy_install_whoami(); ?>	ALL=(ALL) NOPASSWD: <?php echo SVNDEPLOY_BIN ?>
								
<?php echo svndeploy_install_whoami(); ?>	ALL=(ALL) NOPASSWD: /bin/mkdir</pre></i></p>
							
								<?php else : ?>
								
								<p>Deployment folder <i><?php echo SVNDEPLOY_BASE ?></i> is not readable/writable by the current apache user <i><?php echo svndeploy_install_whoami(); ?></i>.<br/>Please change your installation config and check the optione <i>"<u>Run SVN command with SUDO privileges</u>"</i></p>
								
								<?php endif; ?>				
							<?php else : ?>
							<p>Deployment folder <i><?php echo SVNDEPLOY_BASE ?></i> is readable/writable by the current apache user <i><?php echo svndeploy_install_whoami(); ?></i>.<br/>Thank you.</p>
							<?php endif; ?>
							
						</div>
					</div>
				</li>
				
			</ul>
			
			<fieldset style="width: 600px">
				<input type="button" class="back" value="Reset" onclick="back_to_install();" />
				<?php if ( !$forceback ) : ?>
				<input type="button" class="submit" value="Re-Check..." onclick="recheck();" />
				<?php endif; ?>
				<?php if ( $fatal == 0 ) : ?>
					<?php if ( $warn == 0 ) : ?>
					<input type="button" class="pass" value="Passed! Install now..." onclick="install_svndeploy();" />
					<?php else : ?>
					<input type="button" class="pass-warn" value="Warning! Install anyway..." onclick="install_svndeploy();" />
					<?php endif; ?>
				<?php endif; ?>
			</fieldset>
				
		</div>
		
		<?php break; ?>
		
		<?php case 'process' : ?>
		
		<?php
		
		svndeploy_install_do();
		
		?>
		
		<h4>Installation Completed!</h4>
	
		<div id="setup">
			
			<div class="panel">
				<h2 style="padding: 3px 0 0 54px; line-height: 50px; background: url( assets/success.png ) 0 0 no-repeat;">Thank you!</h2>
				<p>SVN Deploy is currently installed and configured.</p>
			</div>
							
			<fieldset>
				<input type="submit" class="submit" value="Launch SVN Deploy" onclick="launch_svndeploy();" />
			</fieldset>
			
		</div>
		
		<?php break; ?>
		
		<?php case 'default' : ?>
		
		<h3>SVN Deploy requires</h3>

		<div class="box">
			<h5>Linux or *nix Operating System</h5>
			<h5>Apache 2.0</h5>
			<h5>PHP 5:</h5>
			<h5 style="padding-left: 15px">PHP 5.3+ <i>(safe_mode = off)</i></h5>
			<h5 style="padding-left: 15px">PHP 5.2+ <i>(safe_mode = off + OpenSSL command line tool)</i></h5>
			<h5>SVN version 1.4+</h5>
		</div>
		
		<h4>Installation Config</h4>
			
		<div id="setup">
			
			<div class="panel"><p>Please fill the form below to perform SVN Depoy installation on your server</p></div>
			
			<form action="setup.php?check" method="post" onsubmit="return check_install_form();">
				<ul>
				
					<li>
						<fieldset>
						
							<label>SVN command binary path:</label>
							<input tabindex="1" type="text" id="svn_bin" name="svn_bin" class="text" value="<?php echo defined( 'SVNDEPLOY_BIN' ) ? SVNDEPLOY_BIN : '/usr/bin/svn' ?>" />
							
							<label>SVN username:</label>
							<input tabindex="3" type="text" id="svn_user" name="svn_user" class="text" value="<?php echo defined( 'SVNDEPLOY_USR' ) ? SVNDEPLOY_USR : '' ?>"/>
							
							<label>SVN Deploy username:</label>
							<input tabindex="5" type="text" id="svndeploy_user" name="svndeploy_user" class="text" value="<?php echo defined( 'SVNDEPLOY_SVNDEPLOY_USR' ) ? SVNDEPLOY_SVNDEPLOY_USR : '' ?>"/>
							
							<input tabindex="8" type="checkbox" id="svn_sudo" name="svn_sudo" class="checkbox" <?php echo defined( 'SVNDEPLOY_USE_SUDO' ) && SVNDEPLOY_USE_SUDO == 1 ? 'checked="checked"' : '' ?>/>
							<label class="checkbox">Run SVN command with SUDO privileges</label>
							
							<span style="display: block; width: 100%; clear: both;"></span>
							
						</fieldset>
					</li>
					<li>
						<fieldset>
							
							<label>Working copies deployment path:</label>
							<input tabindex="2" type="text" id="svn_base" name="svn_base" class="text" value="<?php echo defined( 'SVNDEPLOY_BASE' ) ? SVNDEPLOY_BASE : SVNDEPLOY_REPOS ?>"/>
							
							<label>SVN password:</label>
							<input tabindex="4" type="password" id="svn_pass" name="svn_pass" class="text" />
							
							<label>SVN Deploy password:</label>
							<input tabindex="6" type="password" id="svndeploy_pass" name="svndeploy_pass" class="text" />
							
							<label>SVN Deploy password check:</label>
							<input tabindex="7" type="password" id="svndeploy_pass_check" name="svndeploy_pass_check" class="text" />
							
						</fieldset>
					</li>
	
				</ul>
		
				<span style="display: block; width: 100%; clear: both;"></span>
			
				<input type="hidden" id="svn_rand_key" name="svn_rand_key" value="" />
				
				<fieldset>
					<input type="submit" class="submit" value="Start Installation" />
				</fieldset>
				
			</form>
		</div>
		
		<?php endswitch; ?>
								
		<script type="text/javascript" src="https://api.mutado.com/mobile/shared/js/footer.js"></script>
		
	</body>
	
</html>