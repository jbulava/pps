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
    $this->logger->info($request->getAttribute('url'));
    
    // Render index view
    return $this->renderer->render($response, 'board.phtml');
});

/**
 *
 * Update Content
 * This should only be used by the cronjob, not manually.
 *
 */
$app->get('/update', function ($request, $response) {
    $this->logger->info('Update');

    // Message for log file and printing to screen
    $log_message = '';

    // Get config for credentials
    $config = $this->config->getConfig();

    // Make database connection
    require_once(__DIR__ . '/../lib/database.php');
    $db = new Database($config->get('database'));

    // Get new Tweets
    $connection = new \Abraham\TwitterOAuth\TwitterOAuth(
        $config->get('twitter.consumer_key'),
        $config->get('twitter.consumer_secret'),
        $config->get('twitter.access_token'), 
        $config->get('twitter.access_secret')
    );

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