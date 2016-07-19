<?php

namespace PageGrabber;

use DOMDocument;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use PageGrabber\Exception\FailedFetchException;
use PageGrabber\Exception\InvalidUrlException;
use Psr\Http\Message\ResponseInterface;

class PageGrabber
{
    /**
     * @var string $url
     */
    private $url;

    /**
     * @var ClientInterface $client
     */
    private $client;

    /**
     * @var ResponseInterface $response
     */
    private $response;

    /**
     * @var \DOMDocument $document
     */
    private $document;

    /**
     * PageGrabber constructor.
     *
     * @param string $url
     */
    public function __construct(string $url, Client $client = null)
    {
        $this->setUrl($url);

        if ($client !== null) {
            $this->client = $client;
        }
    }

    public function getTitle(): string
    {
        $document = $this->getDocument();
        $title    = $document->getElementsByTagName('title')->item(0)->nodeValue;

        return $title;
    }

    /**
     * @param $url
     *
     * @return $this
     * @throws InvalidUrlException
     */
    protected function setUrl($url)
    {
        $parsedUrl = parse_url($url);
        if (!$url || !in_array($parsedUrl['scheme'] ?? '', ['http', 'https', 'file'])) {
            throw new InvalidUrlException('The URL provided is invalid, please provide an HTTP or HTTPS URL.');
        }

        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return ClientInterface
     */
    protected function getClient(): ClientInterface
    {
        if (!$this->client) {
            $this->client = new Client();
        }

        return $this->client;
    }

    /**
     * @param string $method
     *
     * @return string
     * @throws FailedFetchException
     */
    protected function getContents(string $method = 'GET', array $options = []): string
    {
        if (!$this->response) {
            try {
                $this->response = $this->getClient()->request($method, $this->getUrl(), $options);
            } catch (RequestException $e) {
                throw new FailedFetchException($e->getMessage(), $e->getCode());
            }

            if ($this->response->getStatusCode() != 200) {
                throw new FailedFetchException(
                    sprintf(
                        'Failed fetching "%s", returned code: %s',
                        $this->getUrl(),
                        $this->response->getStatusCode()
                    )
                );
            }
        }

        return $this->response->getBody()->getContents();
    }

    /**
     * @return DOMDocument
     */
    protected function getDocument(): DOMDocument
    {
        if (!$this->document) {
            $originalErrorState = libxml_use_internal_errors(true);
            $html               = $this->getContents();
            $this->document     = new DOMDocument();
            $this->document->loadHTML($html);
            libxml_use_internal_errors($originalErrorState);
        }

        return $this->document;
    }
}
