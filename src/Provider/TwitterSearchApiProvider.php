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
use Lns\SocialFeed\Model\Pagination\Token;
use Lns\SocialFeed\Model\ResultSet;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * TwitterSearchApiProvider.
 */
class TwitterSearchApiProvider extends AbstractProvider {

  private $twitterApiClient;

  /**
   * __construct.
   *
   * @param ClientInterface      $twitterApiClient
   * @param PostFactoryInterface $postFactory
   */
  public function __construct(ClientInterface $twitterApiClient, PostFactoryInterface $postFactory) {
    $this->twitterApiClient = $twitterApiClient;
    $this->postFactory = $postFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function get(array $parameters = array()) {
    $parameters = $this->resolveParameters($parameters);

    $response = $this->twitterApiClient->get('/search/tweets.json', array(
      'query' => array(
        'q' => $parameters['query'],
        'result_type' => $parameters['result_type'],
        'include_entities' => $parameters['include_entities'],
        'tweet_mode' => $parameters['tweet_mode'],
        'max_id' => $parameters['max_id'],
        'count' => $parameters['count']
      ),
    ));

    return new ResultSet(
        $this->getFeed($response), $parameters, $this->getNextPaginationToken($response)
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function configureOptionResolver(OptionsResolver &$resolver) {
    $resolver->setRequired('query');

    $resolver->setDefaults(array(
      'result_type' => '',
      'include_entities' => true,
      'tweet_mode' => '',
      'max_id' => '',
      'count' => ''
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'twitter_search_api';
  }

  protected function getNextPaginationToken($response) {
    if (!isset($result['search_metadata'])) {
      return;
    }

    $parameters = $this->extractUrlParameters($response['search_metadata']['next_results']);

    return new Token(array(
      'max_id' => $parameters['max_id'],
    ));
  }

  protected function getFeed($response) {
    $feed = new Feed();

    foreach ($response['statuses'] as $status) {
      $feed->addPost($this->postFactory->create($status));
    }

    return $feed;
  }

}
