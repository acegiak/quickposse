<?php
require __DIR__.'/vendor/autoload.php';



$ig = new \InstagramAPI\Instagram();

$name = 'QuickPosseMastodon';
$instance = 'mastodon.social';
$oAuth = new Colorfield\Mastodon\MastodonOAuth($name, $instance);
$oAuth->config->setScopes(['read', 'write', 'follow']);
if(get_option('mastodon_auth_key')){
	$oAuth->config->setAuthorizationCode(get_option('mastodon_auth_key'));
$oAuth->config->setClientId(get_option('mastodon_client_id'));
$oAuth->config->setClientSecret(get_option('mastodon_client_secret'));
$oAuth->config->setBearer(get_option('mastodon_client_bearer'));
	$oAuth->authenticateUser(get_option('mastodon_username'), get_option('mastodon_password'));
	$mastodonAPI = new Colorfield\Mastodon\MastodonAPI($oAuth->config);



}
/*
    Plugin Name: QuickPosse
    Plugin URI: https://github.com/acegiak/quickposse
    Description: quick and dirty Posse to twitter and tumblr
    Version: 1.1.1
    Author: Ashton McAllan
    Author URI: http://www.acegiak.net
    License: GPLv2
*/

/*  Copyright 2011 Ashton McAllan (email : acegiak@machinespirit.net)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/



function tumble($postid,$permalink,$text="",$title="",$quoteurl="",$responsequote="",$responsetitle="",$like=false){

					error_log("tumblattempt");

				$kind = get_post_kind_slug($postid);
				$icons = array();
				$icons['repost'] = "http://media.tumblr.com/09c1847c552386ce03034de35b3739db/tumblr_nmm5qrDrZT1s0lj7bo1_75sq.png";
				$icons['like'] = "http://media.tumblr.com/21dea4d9d7da737ccd554a0212d58665/tumblr_nmm5qrDrZT1s0lj7bo2_75sq.png";
				$icons['reply'] = "http://media.tumblr.com/1fd75773af12f9cada4a96952d31c6ec/tumblr_nmm5qrDrZT1s0lj7bo3_75sq.png";
				$icons['note'] = "http://media.tumblr.com/40043e089d307f17faf96e951323ea6b/tumblr_nmm5qrDrZT1s0lj7bo4_75sq.png";

				$verbed = array("repost"=>"reposted","like"=>"liked","reply"=>"replied","note"=>"posted");
	//WEIRD SAFETY THING
	$title = preg_replace("`^@`","",$title);
	$text = attagout($text,'tumblr').'<span style="font-size:xx-small;"><a href="'.$permalink.'"> - '.$verbed[$kind].' by acegiak.net</a></span>';
	$tags = implode(",",array_map(create_function('$o', 'return $o->name;'), wp_get_post_tags($postid)));

	error_log("tumblrtags:".$tags);
			if(get_option('quickposse_tumblr_oauth_token') && strlen(get_option('quickposse_tumblr_oauth_token'))>0 && get_option('quickposse_tumblr_oauth_token_secret') && strlen(get_option('quickposse_tumblr_oauth_token_secret'))>0 && get_option('quickposse_tumblr_consumer_key') && strlen(get_option('quickposse_tumblr_consumer_key'))>0 && get_option('quickposse_tumblr_consumer_secret') && strlen(get_option('quickposse_tumblr_consumer_secret'))>0){


				error_log("tumblrok");
				$tmpToken = get_option('quickposse_tumblr_oauth_token');
				$tmpTokenSecret =  get_option('quickposse_tumblr_oauth_token_secret');
				$consumerKey = get_option('quickposse_tumblr_consumer_key');
				$consumerSecret = get_option('quickposse_tumblr_consumer_secret');
				$tumblr = new Tumblr\API\Client($consumerKey, $consumerSecret, $tmpToken, $tmpTokenSecret);
				$posted = null;



				if(strlen($quoteurl) > 0){

					error_log("quoteok:".$quoteurl);
					$array = preg_split("`[/\.]+`",preg_replace("`^https?://`i","",strtolower($quoteurl)));

					foreach($array as $key=>$val){
						if(empty($val)||$val=="http"){ unset($array[$key]);}
					}
					$quotebreak = array_values($array);
					error_log("tumblrbreak:".print_r($quotebreak,true));

					if(in_array("tumblr",$quotebreak) && intval(end($quotebreak))>1){
						$result = $tumblr->getBlogPosts($quotebreak[0],array("id" => end($quotebreak)),true);
						$key = $result->posts[0]->reblog_key;
						error_log("tumblrreblog:".print_r($key,true));
						$posted=$tumblr->reblogPost(get_option('quickposse_tumblr_blogname'),end($quotebreak),$key,array("comment" =>$text,"tags"=>$tags));
					}else{
						$text = '<div class="post_body"><p><a href="'.$quoteurl.'">'.$responsetitle.'</a>:</p><blockquote><p>'.$responsequote.'</p></blockquote></div>'.$text;
						$posted=$tumblr->createPost(get_option('quickposse_tumblr_blogname'),array("type"=>"text","tags"=>$tags,"title"=>$title,"body"=>$text));

					}
				}
				if($posted == null){
					$posted = $tumblr->createPost(get_option('quickposse_tumblr_blogname'),array("type"=>"text","tags"=>$tags,"title"=>$title,"body"=>$text));
				}
				error_log("tumblresult:".print_r($posted,true));
			}

}


function mediaToot($file){
	global $mastodonAPI;
$uri = $this->config->getBaseUrl() . '/api/';
        $uri .= ConfigurationVO::API_VERSION . "/media";

$res = $mastodonAPI->client->post( $uri, [
    'Authorization' => 'Bearer ' . $mastodonAPI->config->getBearer(),
    'multipart' => [
        [
            'name'     => 'FileContents',
            'contents' => file_get_contents($file),
            'filename' => preg_replace("`^.*/(.*?)$`","$1",$file)
        ],
        [
            'name'     => 'FileInfo',
            'contents' => json_encode($fileinfo)
        ]
    ],
]);
}


function toot($status,$permalink,$imagelist,$responseurl=""){
global $mastodonAPI;
$params = array();




        $images = array();
        if(count($imagelist)<0){
                foreach($imagelist as $image){

                        error_log("quickposse imageadd ".print_r($image,true));
                        if(preg_match('`.*?\.(mpg|flv|mp4)$`i',$image)){
                                continue;
                        }
                        $images[] = $image;
		}
	}
        while(count($images) > 4){
                $key = array_rand ($images);
                unset($images[$key]);
                $images = array_values($images);
        }

                foreach ($images as $file) {
                        // upload all media files
                        $count = 0;
                        while($count < 10){
                        $reply = mediaToot(smallify($file));

                                error_log("possepic: ".print_r($reply,true));
                                // and collect their IDs
                                $count +=($reply->httpstatus)+1;
                        }
                        if(isset($reply->media_id_string)){
                                $media_ids[] = $reply->media_id_string;
                        }
                }

                if(count($media_ids) > 0){
                $media_ids = implode(',', $media_ids);
                $params['media_ids'] = $media_ids;
                $mediacut = $numberpermedia;
                }








$status = wp_kses_decode_entities($status);

        $message = strip_tags($status);
        $message = preg_replace('`\s+`',' ',attagout($message,'twitter'));
        if (strlen($message) > 500)
        {
            $message = wordwrap($message, 450);
            $message = substr($message, 0, strpos($message, "\n"));
            $message = $message."…".$permalink;
        }
        $params['status'] = htmlspecialchars_decode($message);
        error_log("status: ".$params['status']." length: ".strlen($params['status']));

$mastodonAPI->post('/statuses',$params);
}

function tweet($status,$permalink,$imagelist=array(),$responseurl="",$responsetype="tweet"){
	error_log("TWEET TYPE:".$responsetype);
	error_log("TWEET TO:".$responseurl);

	$status = wp_kses_decode_entities($status);

	//error_log("INCOMING MEDIA TWITTER:".json_encode($imagelist));
	$images = array();
	if(count($imagelist)>0){
		foreach($imagelist as $image){

			error_log("quickposse imageadd ".print_r($image,true));
			$images[] = $image;
			if(preg_match('`.*?\.(gif|mpg|flv|mp4)$`i',$image)){
				$images = array($image);
				break;
			}
		}
	}

	while(count($images) > 4){
		$key = array_rand ($images);
		unset($images[$key]);
		$images = array_values($images);
	}
	$numberpermedia = 0; //was 24, trying 0
	$mediacut = 0;
	\Codebird\Codebird::setConsumerKey(get_option('quickposse_twitter_consumer_key'), get_option('quickposse_twitter_consumer_secret'));
	$cb = \Codebird\Codebird::getInstance();
	$cb->setToken(get_option('quickposse_twitter_authtoken'), get_option('quickposse_twitter_authtoken_key'));

	error_log("possepics: ".print_r($images,true));
	//error_log("quickposseimage:".print_r($image,true));

		$params = array(
		  'status' => $status
		);
	if(count($images)>0){
                if(preg_match('`.*?\.(mpg|flv|mp4)$`i',$images[0])){
			error_log("VIDEO UPLOAD BEGIN");
 		        $uploaddir = wp_upload_dir();

        		$file = preg_replace('`https://acegiak.net/wp-content/uploads`',$uploaddir['basedir'],$images[0]);
			error_log("VIDEO FILE:" .$file);
			$size_bytes = filesize($file);
			$fp         = fopen($file, 'r');

	$reply = $cb->media_upload([
	'command'     => 'INIT',
	'media_type'  => 'video/mp4',
	'total_bytes' => $size_bytes,
	'media_category' => 'tweet_video'
	]);

	$media_id = $reply->media_id_string;

	$segment_id = 0;

	while ($fp != false && ! feof($fp)) {
	$chunk = fread($fp, 1048576); // 1MB per chunk for this sample

	$reply = $cb->media_upload([
		'command'       => 'APPEND',
		'media_id'      => $media_id,
		'segment_index' => $segment_id,
		'media'         => $chunk
	]);
	error_log("upload chunk ".$segment_id."/".($size_bytes/1048576).":".json_encode($reply));
	$segment_id++;
		if ($reply->httpstatus < 200 || $reply->httpstatus > 299) {
		error_log("COULD NOT UPLOAD VIDEO");
		break;
		}

	}

	fclose($fp);

	// FINALIZE the upload

	$reply = $cb->media_upload([
		'command'       => 'FINALIZE',
		'media_id'      => $media_id
	]);

	if ($reply->httpstatus < 200 || $reply->httpstatus > 299) {
		error_log("COULD NOT UPLOAD VIDEO:".json_encode($reply));
	}else{


		do{
		error_log("Sleeping for 5");
		sleep(5); //from FINALIZE response processing_info
		$reply = $cb->media_upload(['command' => 'STATUS',
		'media_id'=> $media_id //media id from INIT
		]);
		error_log("UPLOAD STATUS:".json_encode($reply));

		if ($reply->httpstatus < 200 || $reply->httpstatus > 299) {
		error_log("COULD NOT UPLOAD VIDEO");
			break;
		}


		}while($reply->processing_info->state != 'failed' && $reply->processing_info->state != 'succeeded');
	}


		error_log(json_encode($reply));

		$media_ids = array($media_id);

		}else{

			foreach ($images as $file) {
				// upload all media files
				$count = 0;
				while($count < 10){
				$reply = $cb->media_upload(array(
					'media' => smallify($file)
					));

					error_log("possepic: ".print_r($reply,true));
					// and collect their IDs
					$count +=($reply->httpstatus)+1;
				}
				if(isset($reply->media_id_string)){
					$media_ids[] = $reply->media_id_string;
				}
			}
		}
		if(count($media_ids) > 0){
		$media_ids = implode(',', $media_ids);
		$params['media_ids'] = $media_ids;
		$mediacut = $numberpermedia;
		}
	}

	$message = strip_tags($status);
	$message = preg_replace('`\s+`',' ',attagout($message,'twitter'));

	if(strlen($responseurl)>0){
		error_log("twitterresponse input:".$responseurl);
		$array = preg_split("`[\./]+`",preg_replace("`^https?://`i","",strtolower($responseurl)));
		foreach($array as $key=>$val){
			if(empty($val)){ unset($array[$key]);}
		}
		$break = array_values($array);
		error_log("twitterresponse break:".print_r($break,true));
		if(in_array("twitter",$break) && intval(end($break))>1){
			if($responsetype == "like"){
				$params['id'] = end($break);
				error_log("liking tweet".json_encode($params));
				$reply = $cb->favorites_create($params);
				error_log("quickposse cb:".print_r($reply,true));

				return;
			}
			if($responsetype == "retweet" && strlen($status)<=0){
				$params['id'] = end($break);
				error_log("retweeting tweet".json_encode($params));
				$reply = $cb->statuses_retweet_ID($params);
				error_log("quickposse cb:".print_r($reply,true));
				return;
			}
			$message = "@".$break[2]."\n".$message;
			$params['in_reply_to_status_id'] = end($break);
			error_log("twitterresponse value ".end($break));
		}
	
	}
	

	if (strlen($message) > 235-$mediacut)
	{
	    $message = wordwrap($message, 235-$mediacut);
	    $message = substr($message, 0, strpos($message, "\n"));
            $message = $message."…".$permalink;
	}
	$params['status'] = htmlspecialchars_decode($message);
	error_log("status: ".json_encode($params));
	$reply = $cb->statuses_update($params);
	error_log("quickposse cb:".print_r($reply,true));

	//error_log("quickposse cb:".print_r($cb,true));
	//error_log("quickposse params:".print_r($params,true));
	//error_log("quickposse reply:".print_r($reply,true));
}

function insta($message,$suffix,$media){

        while(count($media) > 10){
                $key = array_rand ($images);
                unset($images[$key]);
                $images = array_values($images);
        }

	error_log("INSTA MESSAGE V1");
	error_log($message);
	$message = wp_kses_decode_entities($message);
	error_log("INSTA MESSAGE V2");
	error_log($message);
	$message = strip_tags($message);
	$message = preg_replace('`\s+`',' ',attagout($message,'instagram'));

	error_log("INSTA MESSAGE V3");
	error_log($message);

        if (strlen($message) > 2000)
        {
            $message = wordwrap($message, 2000);
	    error_log("INSTA MESSAGE V4");
	    error_log($message);
            $message = substr($message, 0, strpos($message, "\n"));
	    error_log("INSTA MESSAGE V5");
	    error_log($message);
            $message = $message."…".$suffix;
        }

	error_log("INSTA MESSAGE V6");
	error_log($message);

	ob_start();
	set_time_limit(0);
	date_default_timezone_set('UTC');
	/////// CONFIG ///////
	$username = get_option('instagram_username');
	$password = get_option('instagram_password');
	$debug = true;
	$truncatedDebug = false;

	$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);
	try {
	    $ig->setUser($username, $password);
	    $ig->login();
	} catch (\Exception $e) {
	    error_log( 'Something went wrong: '.$e->getMessage()."\n");
	    return;
	}
	error_log("INSTAGRAM:");
	error_log(print_r($ig,true));
	try {


		if(count($media) == 1 && in_array(strtolower(substr($media[0],-4)),array(".png",".jpg","jpeg"))){
			$ig->uploadTimelinePhoto(localify($media[0]), ['caption' => $message]);
		}
                if(count($media) == 1 && in_array(strtolower(substr($media[0],-4)),array(".flv",".mpg","mpeg",".mp4"))){
                        $ig->uploadTimelineVideo(localify($media[0]), ['caption' => $message]);
                }
                if(count($media) > 1){
			$files = array();
			foreach($media as $key => $file){
				if(in_array(strtolower(substr($file,-4)),array(".flv",".mpg","mpeg",".mp4"))){
					$files[] = array('type'=>'video','file'=>localify($file));
				}
                                if(in_array(strtolower(substr($file,-4)),array(".jpg",".png","jpeg"))){
                                        $files[] = array('type'=>'photo','file'=>localify($file));
                                }

			}
                        $ig->uploadTimelineAlbum($files, ['caption' => $message]);
                }

	} catch (\Exception $e) {
	    error_log( 'Something went wrong: '.$e->getMessage()."\n");
	    return;
	}
	error_log(ob_get_contents());
	ob_end_clean();
}

function localify($url){
	$uploaddir = wp_upload_dir();
	$file = preg_replace('`https?://acegiak.net/wp-content/uploads`',$uploaddir['basedir'],$url);
	if(in_array(strtolower(substr($file,-4)),array(".flv",".mpg","mpeg",".mp4"))){

	$file = preg_replace('`\.([a-zA-Z]+)$`','-insta.$1',$file);


	$ffprobe = FFMpeg\FFProbe::create();
	$dimension = $ffprobe
		->streams($file) // extracts file informations
		->videos()                      // filters video streams
		->first()                       // returns the first video stream
		->getDimensions();


		$ffmpeg = FFMpeg\FFMpeg::create();
	$video = $ffmpeg->open($file);
	$video
		->filters()
		->resize(new FFMpeg\Coordinate\Dimension(1080, 1080*($dimension->getHeight()/$dimension->getWidth())))
		->synchronize();
	$video
		->save(new FFMpeg\Format\Video\X264(), $file);
			return $file;
		}
	$im = wp_get_image_editor($file);
	if($im instanceof WP_error){
		error_log(json_encode($im));
		return $file;
	}
	$size = $im->get_size();

	$w = 1080;
	$h = 1350;
	$ratio = $size['width']/$size['height'];

	if($ratio >1.9){
		$im->resize($w,$w*(1/1.9),true);
	}else if($ratio < $w/$h){
		$im->resize($w,$h,true);
	}else if($ratio >1){
		$im->resize($h*(1/$ratio),$h,false);
	}else{
		$im->resize($w,$w*ratio,false);
	}

	$file = preg_replace('`\.([a-zA-Z]+)$`','-insta.$1',$file);
	$im->save($file);
	return $file;
}

function smallify($url){
        if(!preg_match('`^https?://acegiak.net/.*$`',$url)){
		return $url;
	}
	$uploaddir = wp_upload_dir();
        $file = preg_replace('`https?://acegiak.net/wp-content/uploads`',$uploaddir['basedir'],$url);
        $im = wp_get_image_editor($file);
        $im->resize(1080,1350);
        $file = preg_replace('`\.([a-zA-Z]+)$`','-insta.$1',$file);
        $im->save($file);
        return preg_replace('`\.([a-zA-Z]+)$`','-insta.$1',$url);
}





function attagout($message,$lookfor){
	global $lookf;
	$lookf=$lookfor;
	error_log("ATTAGOUT TEST:".$message." Lookfor ".$lookfor);
	return preg_replace_callback('`(^|\W)@(\w+)`i',
	function ($matches) {
		global $lookf;
			foreach (get_bookmarks() as $bookmark){
				error_log("checking `".$matches[2]."`i against ".preg_replace("`\W+`","",$bookmark->link_name));
				if(preg_match("`".$matches[2]."`i",preg_replace("`\W+`","",$bookmark->link_name))){
					error_log("linknotes:".$bookmark->link_notes);
					$notes = json_decode($bookmark->link_notes,true);
								if(!array_key_exists($lookf,$notes)){
									error_log("has no correct property(".$lookf."):".print_r($notes,true));
									break;
								}
					error_log("json extracted".print_r($notes[$lookf],true));
					return $matches[1].$notes[$lookf];
				}
			}
			return $matches[1].$matches[2];
			}
	,$message);
	$lookf = null;
}

function quick_posse($post_ID,$postdata){
	if(in_category(array('scrobbles'),$post_ID)){
		error_log("not posseing cause category");
		return;
	}

	$term_list = wp_get_post_terms($postdata->ID, 'kind', array("fields" => "names"));
	error_log("TERM LIST:".json_encode($term_list));

	$url = get_permalink( $post_ID );
	if(get_option('quickposse_google_api_key')){
		$key = get_option('quickposse_google_api_key');
		$googer = new Googl($key);
		error_log("GOOGGER:".print_r($googer,true));
		$burl = $googer->shorten($url);
		if($burl){
			$url = $burl;
		}
		error_log("SHORTURL:".print_r($url,true));
	}


	$meta = get_post_mf2meta($post_ID,'mf2');
	$meta = $meta[array_keys($meta)[0]]['properties'];
	
	$responseurl = "";
	$responsequote = "";
	$responsetitle = "";
	$responseauthor = "";

	error_log("GWGDATA: ".json_encode($meta));
	if(isset($meta)){
		
		error_log("META EXISTS");
		if(isset($meta['url'][0])){
			$responseurl = $meta['url'][0];
		}
		if(isset($meta['summary'][0])){
			$responsequote = $meta['summary'][0];
		}
		if(isset($meta['author'])&& isset($meta['author'])&& isset($meta['author']['name'])){
			$responseauthor = $meta['author']['name'];
		}
		if(isset($meta['name'][0])){
			$responsetitle = $meta['name'][0];
		}
	}
	error_log(json_encode($responseurl));
	error_log(json_encode($responsequote));
	error_log(json_encode($responseauthor));
	error_log(json_encode($responsetitle));




	$content = do_shortcode($postdata->post_content);
	if(!in_category(array('whispers'),$post_ID) || preg_match("`tumblr\.com`",$responseurl)){
		tumble($post_ID,$url,$content,$postdata->post_title,$responseurl,$responsequote,(strlen($responseauthor)>0?$responseauthor.": ":"").$responsetitle);
	}

	$tweet = " - ".$url;
	$message ="";
	$type = "tweet";
	if(stristr($responseurl,"twitter.com") === FALSE){
		if(strlen($responseurl) > 0){
			//			$shortresponse = $googer->shorten($responseurl);
						if(!$shortresponse){
							$shortresponse = $reponseurl;
						}
						error_log("TWEET QUOTE CONSTRUCTION1:".$responsetitle.":".$responseauthor.":".$responseurl);
						$message = "🔁".((strlen($responseauthor) >0)?$responseauthor.": ":"").$responsetitle;
						error_log("TWEET QUOTE CONSTRUCTION2:".$message);
						$message = trim(preg_replace("`[\r\n]+`"," ",$message));
						error_log("TWEET QUOTE CONSTRUCTION3:".$message);
						$message = wordwrap($message, 115)."\n";
						error_log("TWEET QUOTE CONSTRUCTION4:".$message);
						$message = substr($message, 0, strpos($message, "\n"));
						error_log("TWEET QUOTE CONSTRUCTION5:".$message);
						//$message .= "(". $shortresponse.") ";
						error_log("TWEET QUOTE CONSTRUCTION6:".$message);
					}else if(strlen($postdata->post_title)>0){
						$message .= $postdata->post_title.": ";
					}

	

	}else{

		// $ifwho = preg_match_all("`@[a-zA-Z0-9_]{1,15}`",$postdata->post_title." ".$responseauthor." ".$responsetitle,$who);
		// if($ifwho){
		// 	if(!(strlen(trim($postdata->post_content)) > 0)){
		// 		$message = "RT ".$who[0][0]." ".$responsequote;
		// 		$type = "retweet";
		// 	}else{
		// 		$message = $who[0][0]." ";
		// 	}
		// }
		error_log("THIS IS A TWITTERY POST".json_encode($term_list));
		if(in_array("like",$term_list)){
			
			error_log("THIS IS A TWITTERY LIKE");
			$type = "like";
		}
		if(in_array("repost",$term_list)){
			error_log("THIS IS A TWITTERY RETWEET");
			$type = "retweet";
		}

	}
	$message .= $content;
	
	$imageout = array();
	$imagecount = preg_match_all('/<(?:img|source)[^>]*?\s+src\s*=\s*"([^"]+(?:\.jpg|\.png|\.jpeg|\.gif|\.mpg|\.flv|\.mp4|))"[^>]*?>/i', $responsequote.$message, $imageout);
	error_log("quickposse matchcount ".print_r($imagecount,true));
	error_log("quickposse matches ".print_r($imageout,true));
	error_log("quickposse thumbnail ".print_r(get_post_thumbnail_id($post_ID),true));

	$images = $imageout[1];

	tweet($message,$tweet,$images,$responseurl,$type);
	//toot($message,$tweet,$images,$responseurl);
	$iallowed = true;
	$post_categories = wp_get_post_categories($post_ID);
	foreach($post_categories as $c){
		$cat = get_category( $c );
		if($cat->slug == "scrobble" || $cat->slug == "whispers"){
			$iallowed = false;
		}
	}
	if($iallowed){
		insta($message,$tweet,$images);
	}
}

//add_action('publish_post', 'quick_posse',10,2);

function quick_posse_xmlrpc($postid){
$postdata = get_post($postid);
quick_posse($postid,$postdata);
}
//add_action('xmlrpc_publish_post', 'quick_posse_xmlrpc',10,1);


function quickposse_transition($new, $old, $post) {
	if ($new == 'publish' && $old != 'publish') {
		quick_posse($post->ID,$post);
	}
}

add_action('transition_post_status','quickposse_transition',10,3);






if(isset($_GET['oauth_verifier'])){
$consumerKey = get_option('quickposse_tumblr_consumer_key');
$consumerSecret = get_option('quickposse_tumblr_consumer_secret');

			$tmpToken = null;
			$tmpTokenSecret = null;
			if(get_option('quickposse_tumblr_oauth_token') && strlen(get_option('quickposse_tumblr_oauth_token'))>0 && get_option('quickposse_tumblr_oauth_token_secret') && strlen(get_option('quickposse_tumblr_oauth_token_secret'))>0){
				$tmpToken = get_option('quickposse_tumblr_oauth_token');
				$tmpTokenSecret =  get_option('quickposse_tumblr_oauth_token_secret');
				//print_r($tmpToken);
				//print_r($tmpTokenSecret);
			}

$tumblr = new Tumblr\API\Client($consumerKey, $consumerSecret, $tmpToken, $tmpTokenSecret);
// Change the base url
$requestHandler = $tumblr->getRequestHandler();
$requestHandler->setBaseUrl('https://www.tumblr.com/');


    // exchange the verifier for the keys
    $verifier = trim($_GET['oauth_verifier']);
    $resp = $requestHandler->request('POST', 'oauth/access_token', array('oauth_verifier' => $verifier));
    $out = (string) $resp->body;
    $token = array();
    parse_str($out, $token);


				echo "<blockquote><p>".print_r(get_option('quickposse_tumblr_consumer_key'),true)."</p><p>".print_r(get_option('quickposse_tumblr_consumer_secret'),true)."</p><p>".print_r(get_option('quickposse_tumblr_oauth_token'),true)."</p><p>".print_r(get_option('quickposse_tumblr_oauth_token_secret'),true)."</p><p>".print_r($tumblr,true)."</p><p>".print_r($requestHandler,true)."</p><p>".print_r($oauth_verifier,true)."</p><p>".print_r($token,true)."</p><p>".print_r($resp,true)."</p><p>".print_r($out,true)."</p></blockquote>";
				// Set the session for the new access tokens, replacing the request tokens
				update_option("quickposse_tumblr_oauth_token",$token['oauth_token']);
				update_option("quickposse_tumblr_oauth_token_secret",$token['oauth_token_secret']);
}

function quickposse_options()
{
global $oAuth;

?>
    <div class="wrap">
        <h2>Quick Posse Options</h2>
        <form method="post" action="options.php">
            <?php wp_nonce_field('update-options') ?>
            <p><strong><a href="https://dev.twitter.com/oauth/overview">Twitter</a></strong><br />
                <label for="quickposse_twitter_consumer_key">Consumer Key:</label><input type="text" name="quickposse_twitter_consumer_key" size="45" value="<?php echo get_option('quickposse_twitter_consumer_key'); ?>"/><br>
                <label for="quickposse_twitter_consumer_secret">Consumer Secret:</label><input type="text" name="quickposse_twitter_consumer_secret" size="45" value="<?php echo get_option('quickposse_twitter_consumer_secret'); ?>"/><br>
                <label for="quickposse_twitter_authtoken">Consumer Auth Token:</label><input type="text" name="quickposse_twitter_authtoken" size="45" value="<?php echo get_option('quickposse_twitter_authtoken'); ?>"/><br>
                <label for="quickposse_twitter_authtoken_key">Consumer Auth Token Key:</label><input type="text" name="quickposse_twitter_authtoken_key" size="45" value="<?php echo get_option('quickposse_twitter_authtoken_key'); ?>"/><br>
            </p>
            <p><strong><a href="https://dev.twitter.com/oauth/overview">Tumblr</a></strong><br />

<label for="quickposse_tumblr_blogname">Blog Url:</label><input type="text" name="quickposse_tumblr_blogname" size="45" value="<?php echo get_option('quickposse_tumblr_blogname'); ?>"/><br>
                <label for="quickposse_tumblr_consumer_key">Consumer Key:</label><input type="text" name="quickposse_tumblr_consumer_key" size="45" value="<?php echo get_option('quickposse_tumblr_consumer_key'); ?>"/><br>
                <label for="quickposse_tumblr_consumer_secret">Consumer Secret:</label><input type="text" name="quickposse_tumblr_consumer_secret" size="45" value="<?php echo get_option('quickposse_tumblr_consumer_secret'); ?>"/><br>
		<?php

			if(isset($_GET['zhuli'])){
			$consumerKey = get_option('quickposse_tumblr_consumer_key');
			$consumerSecret = get_option('quickposse_tumblr_consumer_secret');
			$tmpToken = null;
			$tmpTokenSecret = null;		
			if(get_option('quickposse_tumblr_oauth_token') && strlen(get_option('quickposse_tumblr_oauth_token'))>0 && get_option('quickposse_tumblr_oauth_token_secret') && strlen(get_option('quickposse_tumblr_oauth_token_secret'))>0){
				$tmpToken = get_option('quickposse_tumblr_oauth_token');
				$tmpTokenSecret =  get_option('quickposse_tumblr_oauth_token_secret');
				//print_r($tmpToken);
				//print_r($tmpTokenSecret);
			}
			$tumblr = new Tumblr\API\Client($consumerKey, $consumerSecret, $tmpToken, $tmpTokenSecret);
			// Change the base url
			$requestHandler = $tumblr->getRequestHandler();
			$requestHandler->setBaseUrl('https://www.tumblr.com/');

			if(strlen(get_option('quickposse_tumblr_consumer_key'))>0 && strlen(get_option('quickposse_tumblr_consumer_secret'))>0){
					    $callbackUrl = 'http://acegiak.net';

						$resp = $requestHandler->request('POST', 'oauth/request_token', array());

					// Get the result
					$result = (string) $resp->body;
					parse_str($result, $keys);
				//print_r($result);

				update_option("quickposse_tumblr_oauth_token",$keys['oauth_token']);
				update_option("quickposse_tumblr_oauth_token_secret",$keys['oauth_token_secret']);

					$url = 'https://www.tumblr.com/oauth/authorize?oauth_token=' . $keys['oauth_token'];
					echo '<a href="'.$url.'">Dance Step 2</a><br>';
				}
			}else{
				echo '<a href="options-general.php?page=quickposse&zhuli=dothething">Dance Step 1</a>';
			}

		?>
                <label for="quickposse_tumblr_oauth_token">Consumer Auth Token:</label><input type="text" name="quickposse_tumblr_oauth_token" size="45" value="<?php echo get_option('quickposse_tumblr_oauth_token'); ?>"/><br>
                <label for="quickposse_tumblr_oauth_token_secret">Consumer Auth Token Key:</label><input type="text" name="quickposse_tumblr_oauth_token_secret" size="45" value="<?php echo get_option('quickposse_tumblr_oauth_token_secret'); ?>"/><br>
            </p>
            <p><strong>Instagram</strong><br />

		<?php
		settings_fields( 'quickposse-options' );
		do_settings_sections( 'quickposse-options' );

?>
<strong>Warning:</strong><em>This plugin stores your instagram login details in plain text. It is not secure.</em><br>
Username: <input type="text" name="instagram_username" value="<?php echo esc_attr( get_option('instagram_username') ); ?>" /><br>
Password: <input type="text" name="instagram_password" value="<?php echo esc_attr( get_option('instagram_password') ); ?>" />

</p><p>
<strong>Mastodon</strong><br>
Auth URL: <a href="<?php echo $oAuth->getAuthorizationUrl(); ?>" target="new">Authorise</a><br>
Mastodon Auth Key: <input type="text" name="mastodon_auth_key" value="<?php echo esc_attr( get_option('mastodon_auth_key') ); ?>" /><br>
Auth deets: <?php echo json_encode($oAuth->config); ?><br>
Username: <input type="text" name="mastodon_username" value="<?php echo esc_attr( get_option('mastodon_username') ); ?>" /><br>
Password: <input type="text" name="mastodon_password" value="<?php echo esc_attr( get_option('mastodon_password') ); ?>" /><br>
Client Id: <input type="text" name="mastodon_client_id" value="<?php echo esc_attr( get_option('mastodon_client_id') ); ?>" /><br>
Client Secret: <input type="text" name="mastodon_client_secret" value="<?php echo esc_attr( get_option('mastodon_client_secret') ); ?>" /><br>
Client Bearer: <input type="text" name="mastodon_client_bearer" value="<?php echo esc_attr( get_option('mastodon_client_bearer') ); ?>" /><br>
</p><p><?php submit_button(); ?>
            </p>
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="quickposse_twitter_consumer_key,quickposse_tumblr_blogname,quickposse_twitter_consumer_secret,quickposse_twitter_authtoken,quickposse_twitter_authtoken_key,quickposse_google_api_key,quickposse_tumblr_consumer_key,quickposse_tumblr_consumer_secret,quickposse_tumblr_oauth_token,quickposse_tumblr_oauth_token_secret" />
        </form>
    </div>
<?php

}

function add_quickposse_options_to_menu(){
	add_options_page( 'QuickPosse', 'QuickPosse', 'manage_options', 'quickposse', 'quickposse_options');
}

add_action('admin_menu', 'add_quickposse_options_to_menu');
add_action( 'admin_init', 'register_quickposse_settings' );

function register_quickposse_settings() { // whitelist options
  register_setting( 'quickposse-options', 'instagram_username' );
  register_setting( 'quickposse-options', 'instagram_password' );
  register_setting( 'quickposse-options', 'mastodon_auth_key' );
  register_setting( 'quickposse-options', 'mastodon_username' );
  register_setting( 'quickposse-options', 'mastodon_password' );
  register_setting( 'quickposse-options', 'mastodon_client_id' );
  register_setting( 'quickposse-options', 'mastodon_client_secret' );
  register_setting( 'quickposse-options', 'mastodon_client_bearer' );
}

