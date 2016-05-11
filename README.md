# Public, Private, Secret

## Project creation
This PHP project was created using the Slim Framework 3 Skeleton Application. Refer to http://www.slimframework.com/ for more information.

## Configuration
Before the application can run, you will need to set up your configuration file. Copy 'config.sample.json' and rename it to 'config.json'.  You can get your Twitter credentials by going to apps.twitter.com and creating an application if you haven't done so already.

## Getting an Instagram access key
Source: http://dmolsen.com/2013/04/05/generating-access-tokens-for-instagram/

Go here in your browser (replacing client_id)
    https://api.instagram.com/oauth/authorize/?client_id=[client_id]&redirect_uri=http%3A%2F%2Flocalhost%3A8080&response_type=code

Run this in the command line (replacing client_id, client_secret, and the code from the last step)
    curl -F 'client_id=[client_id]' -F 'client_secret=[client_secret]' -F 'grant_type=authorization_code' -F 'redirect_uri=http://localhost:8080' -F 'code=[code]' https://api.instagram.com/oauth/access_token

## Install dependencies
Library dependencies can easily be installed using Composer. If you are not familiar with Composer, [start here](https://getcomposer.org/doc/00-intro.md).

You will need to install dependencies with either `php composer.phar update` or `composer update` from the project directory depending on how you set it up above.

## Run locally
You can use PHP's built in web server to view this application locally. Go to the project's directory within the command line and run the following:

    php -S 0.0.0.0:8080 -t public public/index.php

## Run on a server
* Point your virtual host document root to the `public/` directory.
* Ensure `logs/` is web writeable.

## Automatically update content
The `/update` route is used to update the content from social media sources. In order to run this automatically, set up a cronjob.  To begin editing, type:

    crontab -e

Then enter a cronjob like one of the lines below (and only one line).

Every hour on the hour:
    00 * * * * curl localhost:8080/update

Every minute:
    */1 * * * * * curl localhost:8080/update

That's it!
