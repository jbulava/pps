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

    public function getCategories() {
        if ($this->conn) {
    		try {
        		// Query the database and return an associative array of the results
            	$result = $this->conn->query("SELECT id, name1, url, deck FROM objects WHERE active = 1 ORDER BY name1");
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
            	$result = $this->conn->query("SELECT id, name1, url, deck FROM objects WHERE url = '$url'");

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
                $result = $this->conn->query("SELECT type, content_type, url FROM tweet_media WHERE tweet_id = $tweet_id");

                if ($result) {
                    return $result->fetch_all(MYSQLI_ASSOC);
                }
            } catch (Exception $e) {}
        }

        return null;
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

                foreach ($tweets as $tweet) {
                    // Don't save if possibly sensitive
                    if ($tweet->possibly_sensitive) {
                        continue;
                    }

                    // Save to database
                    $this->conn->query("INSERT INTO tweets (object_id, tweet_id, tweet_text, created_at, user_id, screen_name, name, profile_image_url) VALUES ($category_id, $tweet->id, '$tweet->text', '".date('Y-m-d H:i:s', strtotime($tweet->created_at))."', ".$tweet->user->id_str.", '".$tweet->user->screen_name."', '".$tweet->user->name."', '".$tweet->user->profile_image_url."')");

                    print_r('<pre>');
                    print_r($tweet);
                    print_r('</pre>');



                    print('[lvl0]');

                    // TODO: Save media if exists under extended_entities
                    if (isset($tweet->extended_entities) && isset($tweet->extended_entities->media)) {
                        print('[lvl1]');
                        foreach ($tweet->extended_entities->media as $media) {
                            print('[lvl2]');
                            // save a few variables
                            $content_type = (isset($media->content_type)?$media->content_type:'');
                            $media_url = $media->media_url;

                            // check for video
                            if ($media->type == 'video') {
                                print('[lvl3]');
                                // Look through each varient for mp4 of HLS
                                foreach ($media->video_info->varients as $varient) {
                                    print('[lvl4]');
                                    if ($varient->content_type == 'video/mp4') {
                                        print('[lvl5a]');
                                        $content_type = 'video/mp4';
                                        $media_url = $varient->url;
                                    } else if ($varient->content_type == 'application/x-mpegURL') {
                                        print('[lvl5b]');
                                        $content_type = 'application/x-mpegURL';
                                        $media_url = $varient->url;
                                    }
                                }
                            }

                            print_r("INSERT INTO tweet_media (tweet_id, type, content_type, url) VALUES ($tweet->id, '".$media->type."', '".$content_type."', '".$media_url."')");

                            $this->conn->query("INSERT INTO tweet_media (tweet_id, type, content_type, url) VALUES ($tweet->id, '".$media->type."', '".$content_type."', '".$media_url."')");
                        }
                    
                    // Else just get it from the 'media' array
                    } elseif (isset($tweet->entities->media)) {
                        print('[lvlZ]');
                        foreach ($tweet->entities->media as $media) {
                            print('[lvlA]');

                            print_r("INSERT INTO tweet_media (tweet_id, type, content_type, url) VALUES ($tweet->id, '".$media->type."', '', '".$media->media_url."')");

                            $this->conn->query("INSERT INTO tweet_media (tweet_id, type, content_type, url) VALUES ($tweet->id, '".$media->type."', '', '".$media->media_url."')");
                        }
                    }
                }
                
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