<?php
// Routes

/**
 *
 * Main List
 * A simple list of all the categories that can be displayed.
 *
 */
$app->get('/', function ($request, $response) {
    $this->logger->info('Categories List');

    // Get config and setup database
    $config = $this->config->getConfig();
    require_once(__DIR__ . '/../lib/database.php');
    $db = new Database($config->get('database'));

    // Get categories
    $categories = $db->getCategories();

    // Render index view
    return $this->renderer->render($response, 'index.phtml', ['_categories' => $categories]);
});

/**
 *
 * Big Clock
 * This view shows the clock in full screen.
 *
 */
$app->get('/clock', function ($request, $response) {
    $this->logger->info('Big Clock');

    // Render clock view
    return $this->renderer->render($response, 'clock.phtml');
});

/**
 *
 * Leaderboard
 * This view shows the leaderboard app.
 *
 */
$app->get('/celebrity-leader-board', function ($request, $response) {
    $this->logger->info('Leaderboard View');
    
    // Get config for credentials
    $config = $this->config->getConfig();

    // Make database connection
    require_once(__DIR__ . '/../lib/database.php');
    $db = new Database($config->get('database'));
    
    // View variables
    $viewVars = [
        '_leaderboard' => $db->getLeaderboard()
    ];
    
    // Render index view
    return $this->renderer->render($response, 'board.phtml', $viewVars);
});

/**
 *
 * Update Leaderboard
 * This should only be used by the cronjob, not manually.
 *
 */
$app->get('/updateLeaderboard', function ($request, $response) {
    $this->logger->info('Leaderboard Update');

    // Get config for credentials
    $config = $this->config->getConfig();

    // Make database connection
    require_once(__DIR__ . '/../lib/database.php');
    $db = new Database($config->get('database'));

    // Get Twitter connection
    $connection = new \Abraham\TwitterOAuth\TwitterOAuth(
        $config->get('twitter.consumer_key'),
        $config->get('twitter.consumer_secret'),
        $config->get('twitter.access_token'), 
        $config->get('twitter.access_secret')
    );

    // Get Catgeories
    $leaderboard = $db->getLeaderboard();

    if ($leaderboard) {

        // Update latest retweet counts
        print_r('<div id="rank_tweets">');
        foreach ($leaderboard as $key => $celeb) {
            $screenname = '@'.str_replace('@', '', $celeb['screenname']);

            $search = $connection->get('search/tweets', ['q' => $screenname.' -filter:retweet -from:'.$screenname, 'count' => 10, 'result_type' => 'recent', 'include_entities' => true]);

            if ($connection->getLastHttpCode() != 200) {
                $error = 'Connection error ('.$connection->getLastHttpCode().'): '.json_encode($connection->getLastBody());
                print_r($error.'<br />');
                continue;
            }

            $total_retweets = 0;

            // Go through the Tweets
            print_r('<table><tr rowspan="2"><td>'.$celeb['name'].'</td></tr><tr><td>Retweets</td><td>Tweet</td></tr>');
            if (isset($search->statuses)) {
                foreach ($search->statuses as $tweet) {
                    print_r('<tr><td>'.$tweet->retweet_count.'</td><td>'.$tweet->text.'</td></tr>');
                    $total_retweets += $tweet->retweet_count;
                }
            }
            print_r('</table>');

            // Update counts in local array
            $leaderboard[$key]['previous_retweets'] = $leaderboard[$key]['current_retweets'];
            $leaderboard[$key]['current_retweets'] = $total_retweets;
        }
        print_r('</div>');

        // Resort based on new retweet counts
        $retweets_count = array();
        foreach ($leaderboard as $key => $celeb) {
            $retweets_count[$key] = $celeb['current_retweets'];
        }
        array_multisort($retweets_count, SORT_DESC, $leaderboard);

        // Update latest ranking in database and print
        print_r('<div id="rank"><table><tr><td>Rank</td><td>Person</td><td>Popular Retweets</td></tr>');
        foreach ($leaderboard as $key => $celeb) {
            // Update database with new retweet count and rank
            $db->updateLeaderboard($celeb['id'], $leaderboard[$key]['current_retweets'], $key+1);
            print_r('<tr><td>'.($key+1).'</td><td>'.$celeb['name'].' (@'.$celeb['screenname'].')</td><td>'.$celeb['current_retweets'].'</tr>');
        }
        print_r('</table></div>');

        // Print rate limit
        $rate_limit_status = $connection->get('application/rate_limit_status');
        if ($connection->getLastHttpCode() == 200) {
            $search = $rate_limit_status->resources->search->{'/search/tweets'};
            print_r('<div id="rate_limits"><h1>RATE LIMITS</h1>');
            print_r('search/tweets: ' . $search->remaining . ' / ' . $search->limit . ' remaining.<br />');
            print_r('</div>');
        }
    }

    return $this->renderer->render($response, 'update.phtml');
});


/**
 *
 * Update Content
 * This should only be used by the cronjob, not manually.
 *
 */
$app->get('/update', function ($request, $response) {
    $this->logger->info('Tweets Update');

    // Start time for calculating execution time
    $start_time = time();

    // Get option for bad word matching
    $check_bad_words = array_key_exists('safe', $request->getQueryParams());

    // Message for log file and printing to screen
    $log_message = '';

    // Get config for credentials
    $config = $this->config->getConfig();

    // Make database connection
    require_once(__DIR__ . '/../lib/database.php');
    $db = new Database($config->get('database'));

    // Get Twitter connection
    $connection = new \Abraham\TwitterOAuth\TwitterOAuth(
        $config->get('twitter.consumer_key'),
        $config->get('twitter.consumer_secret'),
        $config->get('twitter.access_token'), 
        $config->get('twitter.access_secret')
    );

    // Require Util class and prepare bad words array for bad word list checking
    if ($check_bad_words) {
        require_once(__DIR__ . '/../lib/Util.php');
        $bad_words = json_decode(file_get_contents(__DIR__ . '/../config/badwords.json'), true);
        print_r('SAFE set in URL; using bad word list for further filtering.<br /><br />');
    }

    // Get Catgeories
    $categories = $db->getCategories();

    if ($categories) {
        $log_message .= sizeof($categories) . ' active categories found.';
        print_r(sizeof($categories) . ' active categories found.<br /><br />');

        foreach ($categories as $category) {
            print_r('- - - - - - - - - - - - - - -<br />');
            $log_message .= 'Searching for ' . $category['name1'];
            print_r('Searching for ' . $category['name1'] . '<br />');
            
            $search = $connection->get("search/tweets", ["q" => $category['query'], "count" => 100, "include_entities" => true]);

            if ($connection->getLastHttpCode() != 200) {
                $error = 'Connection error ('.$connection->getLastHttpCode().'): '.json_encode($connection->getLastBody());
                $log_message .= $error;
                print_r($error.'<br />');
                continue;
            }

            // Save the Tweets
            if (isset($search->statuses)) {
                $log_message .= 'Found '. sizeof($search->statuses) . ' Tweets.';
                print_r('Found '. sizeof($search->statuses) . ' Tweets.<br /><br />');

                // As of 5/31/2016, Tweets returned via Search do NOT include the extended entities object
                // and therefore video is missing.  This checks to see if we need the video and will reach
                // out again if needed to get the assets.
                foreach ($search->statuses as $key => $tweet) {

                    // Check for bad words, delete Tweet if bad
                    if ($check_bad_words) {
                        if ( ($word = Util::contains($tweet->text, $bad_words)) !== false) {
                            print_r("Tweet removed. Matched on '".$word."'<br />");
                            unset($search->statuses[$key]);
                            continue;
                        }
                    }

                    // Look for media
                    if (isset($tweet->entities->media[0]) && strpos($tweet->entities->media[0]->expanded_url, 'video/1') !== false && !$tweet->possibly_sensitive) {
                        print_r('Grabbing video...<br />');

                        $tweet_search = $connection->get("statuses/show", ["id" => $tweet->id_str]);
                        if ($connection->getLastHttpCode() != 200) {
                            $error = 'Connection error ('.$connection->getLastHttpCode().'): '.json_encode($connection->getLastBody());
                            $log_message .= $error;
                            print_r($error.'<br />');
                        } else {
                            //print_r($tweet_search->extended_entities->media[0]->video_info);
                            $search->statuses[$key]->extended_entities = $tweet_search->extended_entities;
                        }
                    }
                }

                $db->saveTweets($category['id'], $search->statuses);
            }
        }

        // Print execution time
        print_r('Total run time: '.(time()-$start_time).'s.');

        // Print rate limit
        $rate_limit_status = $connection->get('application/rate_limit_status');
        if ($connection->getLastHttpCode() == 200) {
            $show   = $rate_limit_status->resources->statuses->{'/statuses/show/:id'};
            $search = $rate_limit_status->resources->search->{'/search/tweets'};
            print_r('<div id="rate_limits"><h1>RATE LIMITS</h1>');
            print_r('statuses/show: ' . $show->remaining . ' / ' . $show->limit . ' remaining.<br />');
            print_r('search/tweets: ' . $search->remaining . ' / ' . $search->limit . ' remaining.<br />');
            print_r('</div>');

            // Add limits to log output
            $this->logger->info('statuses/show: ' . $show->remaining . ' / ' . $show->limit);
            $this->logger->info('search/tweets: ' . $search->remaining . ' / ' . $search->limit);
        }
    }

    // Get new Instagram images
    //use MetzWeb\Instagram\Instagram;
    //$instagram = new Instagram('key');
    //$media = $instagram->getTagMedia('#dog');
    //$log_message += '[Got Instagram]';

    // Couldn't figure out how to use the logger for this
    try {
        //file_put_contents('logs/cron.log', $message."\n", FILE_APPEND);
    } catch (Exception $e) {}

    return $this->renderer->render($response, 'update.phtml');
});

/**
 *
 * Category View
 * Displays the content for a specific category.
 *
 */
$app->get('/{url}', function ($request, $response) {
    $this->logger->info('Category View');
    $this->logger->info($request->getAttribute('url'));

    // Get config and setup database
    $config = $this->config->getConfig();
    require_once(__DIR__ . '/../lib/database.php');
    $db = new Database($config->get('database'));

    // Get category from database
    $category = $db->getCategory($request->getAttribute('url')); 
    
    // Get Tweets from database
    $tweets = $db->getTweets($category['id']);

    // Randomize order of Tweets so the display changes on each load
    require_once(__DIR__ . '/../lib/Util.php');
    Util::shuffleArray($tweets);

    // Add media to Tweets
    if ($tweets) {
        for ($i=0; $i < sizeof($tweets); $i++) { 
            $media = $db->getMedia($tweets[$i]['tweet_id']);
            if ($media) {
                $tweets[$i]['media'] = $media;
            }
        }
    }

    // Close the database
    $db->close();

    // View variables
    $viewVars = [
        '_category' => $category,
    	'_tweets' => $tweets,
        '_style' => 'fade-in-out',
        '_timestamps' => array()
    ];

    // Render index view
    return $this->renderer->render($response, 'display.phtml', $viewVars);
});