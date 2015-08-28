<?php

namespace Composer\Satis\Provider;

use Github\Client;
use Github\ResultPager;

class GithubProvider implements ProviderInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     * @param null   $token
     */
    public function __construct(Client $client, $token = null)
    {
        $this->client = $client;

        if ($token !== null) {
            $this->client->authenticate($token, null, Client::AUTH_HTTP_TOKEN);
        }
    }

    /**
     * @param string $organisation
     *
     * @return Repository[]
     */
    public function getRepositories($organisation)
    {
        $composerRepositories = array();
        $paginator  = new ResultPager($this->client);
        $githubRepositories = $paginator->fetchAll(
            $this->client->organizations(),
            'repositories',
            array($organisation, 'private')
        );

        foreach ($githubRepositories as $repository) {
            if (!$this->isComposerAware($repository)) {
                continue;
            }

            $composerRepositories[] = new Repository($repository['full_name'], $repository['git_url']);
        }

        return $composerRepositories;
    }

    /**
     * @param array $repository
     *
     * @return boolean
     */
    private function isComposerAware(array $repository)
    {
        return $this->client->repositories()->contents()->exists(
            $repository['owner']['login'],
            $repository['name'],
            'composer.json'
        );
    }
}
