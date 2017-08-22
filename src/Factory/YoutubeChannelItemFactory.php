<?php

/*
 * This file is part of the Social Feed Util.
 *
 * (c) LaNetscouade <contact@lanetscouade.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Lns\SocialFeed\Factory;

use Lns\SocialFeed\Model\Author;
use Lns\SocialFeed\Model\Media;
use Lns\SocialFeed\Model\YoutubePlaylistItem;

/**
 * YoutubePlaylistItemFactory.
 */
class YoutubeChannelItemFactory implements PostFactoryInterface
{
    /**
     * create.
     *
     * @param array $data
     *
     * @return Tweet $post
     */
    public function create(array $data)
    {
        $playlistItem = new YoutubeChannelItem();

        $snippet = $data['snippet'];

        $author = new Author();
        $author->setIdentifier($snippet['channelId']);
        $author->setName($snippet['channelTitle']);
        $author->setUsername($snippet['channelTitle']);

        $playlistItem
            ->setIdentifier($data['id'])
            ->setMessage($snippet['title'])
            ->setCreatedAt(new \DateTime($snippet['publishedAt']))
            ->setAuthor($author);

        $media = $this->getBestThumbnailRes($snippet['thumbnails']);
        $media->setLink('https://www.youtube.com/watch?v=' . $snippet['resourceId']['videoId']);

        $playlistItem->addMedia($media);

        return $playlistItem;
    }

    public function getBestThumbnailRes($thumbnails) {

        $resolutions = array(
            'maxres',
            'standard',
            'high',
            'medium',
            'default',
        );

        foreach ($resolutions as $resolution) {
            if (isset($thumbnails[$resolution])) {
                $media = new Media();
                return $media
                    ->setUrl($thumbnails[$resolution]['url'])
                    ->setWidth($thumbnails[$resolution]['width'])
                    ->setHeight($thumbnails[$resolution]['height']);
            }
        }

        return false;
    }
}

