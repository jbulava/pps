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
 * Update Content
 * This should only be used by the cronjob, not manually.
 *
 */
$app->get('/update', function ($request, $response) {
    $this->logger->info('Update');

    // Message for log
    $log_message = null;

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
        //$category = $categories[4]; // temp for test
        foreach ($categories as $category) {
            
            $search = $connection->get("search/tweets", ["q" => $category['deck'], "count" => 50]);

            if ($connection->getLastHttpCode() != 200) {
                return $this->renderer->render($response, 'update.phtml', ['_message' => 'Connection error']);
            }

            // Save the Tweets
            if (isset($search->statuses)) {
                $db->saveTweets($category['id'], $search->statuses);
                $log_message += '[Got Tweets: '.$category['id'].']';
            }
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

    return $this->renderer->render($response, 'update.phtml', ['_message' => $log_message]);
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