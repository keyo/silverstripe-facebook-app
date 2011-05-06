Silverstripe Facebook App
=========================
Connect your silverstripe website to a facebook application. This module uses the graph api.

This is in alpha stage, and currently only posts to walls of pagebook fan pages. However I plan to add other features of the graph API later.

You will need to have signed up for facebook developers and created an app to use this module.

Installation
------------
* Setup the configuration file with your facebook applications keys. 

    //Facebook API
    //Enable the module, comment this out to leave the module disabled.
    FacebookModule::enable();
    //Set the application id
    FacebookModule::set_application_id('22xxxxxxxxxxxx');
    //Set the application secret key
    FacebookModule::set_app_secret('c7afxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
    //Set the api key
    FacebookModule::set_api_key('34xxxxxxxxxxxxxxxxxxxxxxxxxx');
    //Set the page_id. upDateStatus will post to this page.
    FacebookModule::set_page_id('12xxxxxxxxxxxx');

* Finally, log into the facebook account that owns the page. go to site configuration.Click the link to authorize your website with facebook. To do this the website must be live and on the same URL specified in your application settings.

* Facebook will ask for permission to post to your wall at any time. Click accept. You should be taken back to the administration page. The websites is now permitted to post to your facebook wall.


Usage
-----

### Post to page wall

    $params['description'] = $summary;
    $params['link'] = Director::absoluteBaseURL() . 'go/' . $tracked_link->Slug;
    $params['picture'] = $this->Image()->getAbsoluteURL();
    FacebookModule::updateStatus($params);
