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
use Lns\SocialFeed\Model\YoutubeChannelItem;

/**
 * YoutubeChannelItemFactory.
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
        $channelItem = new YoutubeChannelItem();

        $snippet = $data['snippet'];

        $author = new Author();
        $author->setIdentifier($snippet['channelId']);
        $author->setName($data['channelTitle']);
        $author->setUsername($data['channelTitle']);

        $channelItem
            ->setIdentifier($data['id']['videoId'])
            ->setMessage($snippet['description'])
            ->setCreatedAt(new \DateTime($snippet['publishedAt']))
            ->setAuthor($author);

        $media = $this->getBestThumbnailRes($snippet['thumbnails']);
        $media->setLink('https://www.youtube.com/watch?v=' . $data['id']['videoId']);

        $channelItem->addMedia($media);

        return $channelItem;
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

