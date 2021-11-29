<?php 
if (!function_exists('build_passport_player')) {
function build_passport_player($video) {
  $passport_defaults = get_option('pbs_passport_authenticate');
  $call_letters = strtolower($passport_defaults['station_call_letters']);
  if (empty($video->tp_media_object_id)) {
    return;
  }
	global $coveWindow;
	$imgDir = get_bloginfo('template_directory');
	$m = json_decode($video->metadata);
	
	// video poster image. 
	if (empty($m->mezzanine)) {
    $large_thumb = $imgDir . "/libs/images/default.png";
  } else {
		if (function_exists( 'wnet_video_cove_thumb')) {
      $large_thumb = wnet_video_cove_thumb($m->mezzanine, 1200, 675);
    } else {
      $large_thumb = $m->mezzanine;
    }
	}
  if ( ($video->window != 'all_members') && ( $video->window != 'station_members') ) {
    // this video is public, just show it
    return '<div class="passportcoveplayer" data-window="public" data-media="' . $video->tp_media_object_id . '" data-callsign="' . $call_letters . '"><div class="embed-container video-wrap no-content nocontent"><iframe id="partnerPlayer" marginwidth="0" marginheight="0" scrolling="no" src="//player.pbs.org/widget/partnerplayer/' . $video->tp_media_object_id . '/?chapterbar=false&endscreen=false&callsign=' . $call_letters . '" allow="encrypted-media" allowfullscreen="" frameborder="0" loading="lazy"></iframe></div></div>';
  }
	// passport video overlay for gated videos.
	if (PASSPORT_ENABLED && (($video->window == 'all_members') ||( $video->window == 'station_members')) && $coveWindow == 'all') {
		
		$join_url = !empty($passport_defaults['join_url']) ? $passport_defaults['join_url'] : '#';
    $station_passport_logo_reverse = !empty($passport_defaults['station_passport_logo_reverse']) ? $passport_defaults['station_passport_logo_reverse'] : $passport_defaults['station_passport_logo'];
    $station_nice_name = !empty($passport_defaults['station_nice_name']) ? $passport_defaults['station_nice_name'] : "";
		$passportOverlay = "
			<div class='signup'><div class='signup-inner'>
				<div class='pp-intro'>
					<p>Access to this video is a<br/> benefit for members through</p>
					<img src='" . $station_passport_logo_reverse . "' alt='" . esc_attr($station_nice_name) . " Passport'/>
				</div>
				<div class='pp-button pbs_passport_authenticate cf'><button class='launch'><span>MEMBER SIGN IN</span></button></div>
				<div class='pp-button pbs_passport_authenticate'><a href='/passport/' class='learn-more'><button class='learn-more'>LEARN MORE <i class='fa fa-arrow-circle-o-right'></i></button></a></div>
			</div></div>";
			$passportGated = "gated";
		} else {
      $passportOverlay = ""; 
      $passportGated = "standard";
    }

		// if passport not enabled, and video expired.
		if (!PASSPORT_ENABLED && (($video->window == 'all_members') ||( $video->window == 'station_members'))) {
			$passportError = "<div class='passport-error'><span>This video is currently unavailable on $StationTheme</span></div>";
		} else {
      $passportError = "";
    }
    return '<div class="passportcoveplayer" data-window="'.$video->window.'" data-media="'.$video->tp_media_object_id.'" data-callsign="' . $call_letters . '"><div class="passport-'.$passportGated.'-video"><img src="'.$large_thumb.'" width="1200" height="675" loading="lazy" alt="">'.$passportOverlay . $passportError.'</div></div>';
   }
}

function cove_passport_shortcode($atts, $content = null) {
  
  extract(shortcode_atts(array(
                            "id" => '',
                            "window" => '',
                            "image" => '',
	  						"placeholder" => ''
                        ), $atts));

  $player = "<!-- video no longer available -->";
  if (function_exists('pbs_video_utils_get_video')) {
    $video = pbs_video_utils_get_video($id);
  } else {
    $video = array(
        array ( "tp_media_object_id" => $id,
          "window" => $window,
          "metadata" => json_encode(array ( "mezzanine" => $image ))
        )
    );
  }
	
	if (!empty($video) && !empty($placeholder)) {
		// placeholder version of player, requires a click to load the actual player.
		$video = json_decode(json_encode($video, JSON_UNESCAPED_UNICODE));
		if (!empty($video[0]->metadata)) {$m = json_decode($video[0]->metadata, true);}
		$hidden_player = build_passport_player($video[0]);
		$player = "
			
			<figure class='video-placeholder'>
			<a href='" . get_permalink($video[0]->post_id) . $video[0]->legacy_key . "/' data-media-player='PLAYER_" . $id . "'>
				<img src='". $m['mezzanine'] ."?crop=768x432&format=jpg' loading='lazy' width='768' height='432'>
				<div class='overlay'><i class='fa fa-play'></i></div>
			</a>
			</figure>
			<script>var PLAYER_" .$id . " = " . json_encode($hidden_player) . ";</script>
		";
	
	}
	else if (!empty($video)) {
    	$video = json_decode(json_encode($video, JSON_UNESCAPED_UNICODE));
    	$player = "<div class='shortcode-video cf'>". build_passport_player($video[0]) . "</div>";
	}
	
	return $player;

}
if (!shortcode_exists("cove-passport")) {
  add_shortcode("cove-passport", "cove_passport_shortcode");  
}
/* end of file */
