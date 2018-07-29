<?php

// Script based in examples given for learning usage of Instagram Private API created by @mgp25

set_time_limit(0);
date_default_timezone_set('UTC');

require __DIR__.'/../vendor/autoload.php';

/////// CONFIG ///////
$username = 'your username here';
$password = 'your plain password here';
$debug = true;
$truncatedDebug = false;
//////////////////////

$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);

try {
    $ig->login($username, $password);
} catch (\Exception $e) {
    echo 'Could not log in: '.$e->getMessage()."\n";
    exit(0);
}

function downloadFileURL($filepath, $urlResource) {
    echo "Getting resource from URL: $urlResource\n";
    echo "Writing to file in path: $filepath\n\n";

    $connectionURL = curl_init($urlResource);
    $file = fopen($filepath, 'wb+');
    curl_setopt($connectionURL, CURLOPT_FILE, $file);
    curl_setopt($connectionURL, CURLOPT_HEADER, 0);
    curl_exec($connectionURL);
    curl_close($connectionURL);
    fclose($file);
}

$isMoreAvailable = true;
$nextMaxId = null;
$baseFilename = "../yournamefolderhere/";
$isLikedFeedReceived = false;
$isResourceReceived = false;

while ($isMoreAvailable) {
    while(!$isLikedFeedReceived) {
        try {
            $likedFeed = $ig->media->getLikedFeed($nextMaxId);
            $isLikedFeedReceived = true;
        } catch (\Exception $e) {
            echo 'Could not get liked feed: '.$e->getMessage()."\n";
        }
    }
    $isLikedFeedReceived = false;

    $isMoreAvailable = $likedFeed->getMoreAvailable();
    $nextMaxId = $likedFeed->getNextMaxId();

    $numResultsPage = $likedFeed->getNumResults();
    $itemsPage = $likedFeed->getItems();

    for ($itemPageIndex = 0; $itemPageIndex < $numResultsPage; $itemPageIndex++) {
        $itemPage = $itemsPage[$itemPageIndex];
        if ($itemPage->isVideoVersions()) { // Video
            $usernameCreator = $itemPage->getUser()->getUsername();
            $idResource = $itemPage->getId();

            $urlResource = $itemPage->getVideoVersions()[0]->getUrl();
            $filename = "$usernameCreator"."_$idResource.mp4";

            while(!$isResourceReceived) {
                try {
                    downloadFileURL($baseFilename.$filename, $urlResource);
                    $isResourceReceived = true;
                } catch (\Exception $e) {
                    echo "Tried to write file $filename";
                    echo "Failed to get resource in url $urlResource";
                    echo "Error: ".$e->getMessage()."\n";
                }
            }
            $isResourceReceived = false;
        }
        else if ($itemPage->isCarouselMedia()) { // Image carousel
            $carouselImages = $itemPage->getCarouselMedia();
            $usernameCreator = $itemPage->getUser()->getUsername();
            for ($carouselImageIndex = 0; $carouselImageIndex < count($carouselImages); $carouselImageIndex++) {
                $idResource = $carouselImages[$carouselImageIndex]->getId();
                $urlResource = $carouselImages[$carouselImageIndex]->getImageVersions2()->getCandidates()[0]->getUrl();
                
                $filename = "$usernameCreator"."_$idResource.jpg";

                while(!$isResourceReceived) {
                    try {
                        downloadFileURL($baseFilename.$filename, $urlResource);
                        $isResourceReceived = true;
                    } catch (\Exception $e) {
                        echo "Tried to write file $filename";
                        echo "Failed to get resource in url $urlResource";
                        echo "Error: ".$e->getMessage()."\n";
                    }
                }
                $isResourceReceived = false;
            }
        }
        else if ($itemPage->isImageVersions2()) { // Image
            $usernameCreator = $itemPage->getUser()->getUsername();
            $idResource = $itemPage->getId();

            $urlResource = $itemPage->getImageVersions2()->getCandidates()[0]->getUrl();
            $filename = "$usernameCreator"."_$idResource.jpg";

            while(!$isResourceReceived) {
                try {
                    downloadFileURL($baseFilename.$filename, $urlResource);
                    $isResourceReceived = true;
                } catch (\Exception $e) {
                    echo "Tried to write file $filename";
                    echo "Failed to get resource in url $urlResource";
                    echo "Error: ".$e->getMessage()."\n";
                }
            }
            $isResourceReceived = false;
        }
    }
}

?>