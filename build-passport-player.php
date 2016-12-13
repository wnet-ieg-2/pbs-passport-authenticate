<?php 
if (!function_exists('build_passport_player')) {
function build_passport_player($video) {

	global $coveWindow;
	$imgDir = get_bloginfo('template_directory');
	$m = json_decode($video->metadata);
	
	// video poster image. 
	if (empty($m->mezzanine)) {$large_thumb = $imgDir . "/libs/images/default.png";}
	else {
		if (function_exists( 'wnet_video_cove_thumb')) {$large_thumb = wnet_video_cove_thumb($m->mezzanine, 1200, 675);}
		else {$large_thumb = $m->mezzanine;}
	}
	
	// passport video overlay for gated videos.
	if (PASSPORT_ENABLED && (($video->window == 'all_members') ||( $video->window == 'station_members')) && $coveWindow == 'all') {
		
		$passport_defaults = get_option('pbs_passport_authenticate');
		$join_url = '#';
		if (!empty($passport_defaults['join_url'])) {$join_url = $passport_defaults['join_url'];}
			$passportOverlay = "
			<div class='signup'><div class='signup-inner'>
				<div class='pp-intro'>
					<p>Access to this video is a<br/> benefit for members through</p>
					<img src='$imgDir/libs/images/socal-passport-white.png' alt='PBS SoCal Passport'/>
				</div>
				<div class='pp-button pbs_passport_authenticate cf'><button class='launch'>
					MEMBER SIGN IN <img src='$imgDir/libs/images/passport_compass_white.svg'>
				</button></div>
				<div class='pp-button pbs_passport_authenticate'>
					<a href='/passport/' class='learn-more'><button class='learn-more'>LEARN MORE <i class='fa fa-arrow-circle-o-right'></i></button></a>
				</div>
			</div></div>";
			$passportGated = "gated";
		}
		else {$passportOverlay = ""; $passportGated = "standard";}

		// if passport not enabled, and video expired.
		if (!PASSPORT_ENABLED && (($video->window == 'all_members') ||( $video->window == 'station_members'))) {
			$passportError = "<div class='passport-error'><span>This video is currently unavailable on $StationTheme</span></div>";
		}
		else {$passportError = "";}
		
        return '<div class="passportcoveplayer" data-window="'.$video->window.'" data-media="'.$video->tp_media_object_id.'"><div class="passport-'.$passportGated.'-video"><img src="'.$large_thumb.'">'.$passportOverlay . $passportError.'</div></div>';
    }
}

/* end of file */
