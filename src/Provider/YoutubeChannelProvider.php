<?php

/*
 * This file is part of the Social Feed Util.
 *
 * (c) LaNetscouade <contact@lanetscouade.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Lns\SocialFeed\Provider;

use Lns\SocialFeed\Client\ClientInterface;
use Lns\SocialFeed\Factory\PostFactoryInterface;
use Lns\SocialFeed\Model\Feed;
use Lns\SocialFeed\Model\ResultSet;
use Lns\SocialFeed\Model\Pagination\Token;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * YoutubeChannelProvider.
 */
class YoutubeChannelProvider extends AbstractProvider {

    private $youtubeApiClient;

    /**
     * __construct.
     *
     * @param ClientInterface      $youtubeApiClient
     * @param PostFactoryInterface $postFactory
     */
    public function __construct(ClientInterface $youtubeApiClient, PostFactoryInterface $postFactory) {
        $this->youtubeApiClient = $youtubeApiClient;
        $this->postFactory = $postFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $parameters = array()) {
        
        $fixed_video = array();
        $parameters = $this->resolveParameters($parameters);

        //get videos of channel
        $channel_videos = $this->youtubeApiClient
                ->get('/search', array(
            'query' => array(
                'order' => $parameters['order'],
                'maxResults' => $parameters['maxResults'],
                'part' => 'snippet',
                'channelId' => $parameters['channelId']
            ),
        ));

        //retrieve youtube videoID description
        if (!empty($parameters['videoID'])) {
            $video = $this->youtubeApiClient
                    ->get('/videos', array(
                'query' => array(
                    'part' => 'snippet',
                    'id' => $parameters['videoID'],
                ),
            ));
            $fixed_video['items'][0]['id']['videoID'] = $video['items'][0]['id'];
            $fixed_video['kind'] = 'youtube#searchListResponse';
            $fixed_video['etag'] = $video['etag'];
            $fixed_video['pageInfo']['totalResults'] = 1;
            $fixed_video['pageInfo']['resultsPerPage'] = 1;
            $fixed_video['nextPageToken'] = $channel_videos['nextPageToken'];
            foreach ($video['items'] as $val) {
                $fixed_video['items'][0]['kind'] = 'youtube#searchResult';
                $fixed_video['items'][0]['etag'] = $val['etag'];
                $fixed_video['items'][0]['id']['kind'] = 'youtube#video';
                $fixed_video['items'][0]['id']['videoId'] = $val['id'];
                $fixed_video['items'][0]['snippet'] = $val['snippet'];
            }
            $response = array_merge_recursive($fixed_video, $channel_videos);
        }

        $feed = new Feed();

        foreach ($response['items'] as $item) {
            $feed->addPost($this->postFactory->create($item));
        }
        return new ResultSet(
                $feed, $parameters, $this->getNextPaginationToken($response)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'youtube_channel';
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptionResolver(OptionsResolver &$resolver) {
        $resolver->setRequired('channelId');
        $resolver->setRequired('order');
        $resolver->setRequired('maxResults');
        $resolver->setRequired('videoID');
        $resolver->setDefaults(array(
            'pageToken' => null,
        ));
    }

    protected function getNextPaginationToken($response) {
        if (!isset($response['nextPageToken'])) {
            return;
        }

        return new Token(array(
            'pageToken' => $response['nextPageToken'],
        ));
    }

}
