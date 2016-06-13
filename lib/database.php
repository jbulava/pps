<?php
class Database {

    public $conn = null;

	public function __construct($db_config = null) {
		// Return nothing if we got nothing
		if (!$db_config) {
			return null;
		}

		try {
			// Make database connection
			$this->conn = new mysqli(
				$db_config['server'],
				$db_config['user'],
				$db_config['pass'],
				$db_config['database']
			);

		} catch (Exception $e) {}

		if ($this->conn->connect_errno) {
            $this->conn = null;
			// TODO: remove or put this elsewhere
		    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		}
    }

    public function getLeaderboard() {
        if ($this->conn) {
            try {
                // Query the database and return an associative array of the results
                $result = $this->conn->query("SELECT * FROM leaderboard");
                return $result->fetch_all(MYSQLI_ASSOC);
            } catch (Exception $e) {}
        }
    }

    public function getCategories() {
        if ($this->conn) {
    		try {
        		// Query the database and return an associative array of the results
            	$result = $this->conn->query("SELECT id, name1, url, duration, query FROM objects WHERE active = 1 ORDER BY name1");
    			return $result->fetch_all(MYSQLI_ASSOC);
        	} catch (Exception $e) {}
        }

    	// TODO: load backup categories from a file, use if on null value
        return null;
    }

    public function getCategory($url) {

        if ($this->conn) {

            // Escape the url for the database query
            $url = $this->conn->real_escape_string($url);

    		try {
        		// Query the database and return an associative array of the results
            	$result = $this->conn->query("SELECT id, name1, url, duration, query FROM objects WHERE url = '$url'");

                if ($result) {
                    return $result->fetch_array(MYSQLI_ASSOC);
                }
        	} catch (Exception $e) {}
        }

    	// TODO: load backup category from a file, use if on null value
        return null;
    }

    // Get Tweets for a specific category
    public function getTweets($category_id = 1) {

        if ($this->conn) {

            // Escape the category ID for the database query
            $category_id = $this->conn->real_escape_string($category_id);

        	try {
        		// Query the database and return an associative array of the results
            	$result = $this->conn->query("SELECT tweet_id, tweet_text, screen_name, name, profile_image_url FROM tweets WHERE object_id = $category_id");

                if ($result) {
                    return $result->fetch_all(MYSQLI_ASSOC);
                }
        	} catch (Exception $e) {}
        }

    	// TODO: load backup Tweets from a file, use if on null value
        return null;
    }

    // Get all media for a specific Tweet
    public function getMedia($tweet_id) {

        if ($this->conn) {

            // Escape the id for the database query
            $tweet_id = $this->conn->real_escape_string($tweet_id);

            try {
                // Query the database and return an associative array of the results
                $result = $this->conn->query("SELECT media_id, type, content_type, url FROM tweet_media WHERE tweet_id = $tweet_id");

                if ($result) {
                    return $result->fetch_all(MYSQLI_ASSOC);
                }
            } catch (Exception $e) {}
        }

        return null;
    }

    // Update a leaderboard entry
    public function updateLeaderboard($celeb_id, $total_retweets, $new_rank) {

        if (!isset($celeb_id) || !isset($total_retweets)) {
            return false;
        }

        if ($this->conn) {

            // Escape for the database query
            $celeb_id = $this->conn->real_escape_string($celeb_id);
            $total_retweets = $this->conn->real_escape_string($total_retweets);
            $new_rank = $this->conn->real_escape_string($new_rank);

            try {
                // Save current_retweets to previous_retweets
                $update = $this->conn->query("UPDATE leaderboard SET previous_retweets = current_retweets, previous_rank = current_rank WHERE id = $celeb_id");

                // Save new current_retweets
                if ($update) {
                    $update = $this->conn->query("UPDATE leaderboard SET current_retweets = $total_retweets, current_rank = $new_rank WHERE id = $celeb_id");
                }
            } catch (Exception $e) {
                print_r($e);
            }
        }
    }

    // Save new Tweets to the database
    public function saveTweets($category_id, $tweets) {

        // Return if there are no Tweets
        if (sizeof($tweets) == 0) {
            return null;
        }

        if ($this->conn) {

            // Escape the category ID for the database query
            $category_id = $this->conn->real_escape_string($category_id);

            try {
                // Delete existing Tweets for this category
                $this->conn->query("DELETE FROM tweets WHERE object_id = $category_id");

                print('<table>');
                foreach ($tweets as $key => $tweet) {
                    print(($key%4==1)?'<tr><td>':'<td>');

                    // Open bracket for Tweet
                    print_r('[<a href="https://twitter.com/pps/status/'.$tweet->id_str.'" target="_blank">Tweet</a>: ');

                    // Don't save if possibly sensitive
                    if ($tweet->possibly_sensitive) {
                        print_r('<a href="' . $tweet->entities->media[0]->expanded_url . '" target="_blank">sensitive material removed</a>] ');
                        print(($key%4==0)?'</td></tr>':'</td>');
                        continue;
                    }

                    // Save to database
                    $this->conn->query("INSERT INTO tweets (object_id, tweet_id, tweet_text, created_at, user_id, screen_name, name, profile_image_url) VALUES ($category_id, $tweet->id, '$tweet->text', '".date('Y-m-d H:i:s', strtotime($tweet->created_at))."', ".$tweet->user->id_str.", '".$tweet->user->screen_name."', '".$tweet->user->name."', '".$tweet->user->profile_image_url."')");

                    // NOTE: At the current time (5/31/2016), this foreach would never happen
                    // out of the box since extended entities are NOT returned in Tweets via
                    // the search endpoint.  Instead, extended_entities are manually being added
                    // in the update route.
                    if (isset($tweet->extended_entities) && isset($tweet->extended_entities->media)) {

                        foreach ($tweet->extended_entities->media as $media) {
                            // save a few variables
                            $content_type = (isset($media->content_type)?$media->content_type:'');
                            $media_url = $media->media_url;
                            $bitrate = 0;

                            // check for video
                            if ($media->type == 'video') {
                                print('video ( ');
                                // Look through each variant for mp4 of HLS
                                foreach ($media->video_info->variants as $variant) {
                                    if ($variant->content_type == 'video/mp4') {
                                        if ($variant->bitrate > $bitrate) {
                                            $content_type = 'video/mp4';
                                            $media_url = $variant->url;
                                            $bitrate = $variant->bitrate;
                                        }
                                    } else if ($variant->content_type == 'application/x-mpegURL') {
                                        $content_type = 'application/x-mpegURL';
                                        $media_url = $variant->url;
                                    }
                                }
                                print('<a href="'.$media->media_url.'" target="_blank">'.$content_type.'</a> )] ');
                            }

                            $this->conn->query("INSERT INTO tweet_media (tweet_id, type, content_type, url) VALUES ($tweet->id, '".$media->type."', '".$content_type."', '".$media_url."')");
                        }
                    
                    // Else just get it from the 'media' array
                    } elseif (isset($tweet->entities->media)) {
                        print('media ( ');

                        foreach ($tweet->entities->media as $media) {
                            print('<a href="'.$media->media_url.'" target="_blank">link</a> ');

                            $this->conn->query("INSERT INTO tweet_media (tweet_id, type, content_type, url) VALUES ($tweet->id, '".$media->type."', '', '".$media->media_url."')");
                        }
                        print(')] ');
                    } else {
                        print_r('non-playable media] ');
                    }

                    print(($key%4==0)?'</td></tr>':'</td>');
                }
                print('</table>');
                
            } catch (Exception $e) {
                print_r($e);
            }
        }

        return null;
    }

    // Close the database connection
    public function close() {
    	$this->conn->close();
    }
}