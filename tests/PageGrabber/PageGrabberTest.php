<?php

namespace PageGrabber;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class PageGrabberTest extends TestCase
{
    protected $client;

    /**
     * @return PageGrabber
     */
    public function testConstruction()
    {
        $url         = 'file://'.TESTS_DIR.'/fixtures/blazemeter.html';
        $pageGrabber = new PageGrabber($url, $this->client);
        $this->assertInstanceOf(PageGrabber::class, $pageGrabber);
        $this->assertEquals($url, $pageGrabber->getUrl());

        return $pageGrabber;
    }

    /**
     * @depends testConstruction
     */
    public function testTitle(PageGrabber $pageGrabber)
    {
        $expectedTitle = 'JMeter and Performance Testing for DevOps I BlazeMeter';
        $grabbedTitle  = $pageGrabber->getTitle();
        $this->assertEquals($expectedTitle, $grabbedTitle);
    }

    /**
     * @dataProvider unsupportedSchemes
     * @expectedException \PageGrabber\Exception\InvalidUrlException
     */
    public function testUnsupportedSchemes(string $url)
    {
        new PageGrabber($url);
    }

    /**
     * @dataProvider missingHtmlFiles
     * @expectedException \PageGrabber\Exception\FailedFetchException
     */
    public function testMissingFiles(string $url)
    {
        $pageGrabber = new PageGrabber($url, $this->client);
        $pageGrabber->getTitle();
    }

    public function setUp()
    {
        $mockHandler  = new MockHandler(
            [
                function (Request $request) {
                    if (!is_file($request->getUri())) {
                        throw new BadResponseException('File not found', $request);
                    }
                    $body = file_get_contents($request->getUri());

                    return new Response(200, [], $body);
                },
            ]
        );
        $handlerStack = HandlerStack::create($mockHandler);
        $this->client = new Client(['handler' => $handlerStack]);
    }

    /**
     * @return array
     */
    public function unsupportedSchemes()
    {
        return [
            ['ftp://www.blazemeter.com/'],
            ['ftps://www.blazemeter.com/'],
        ];
    }

    /**
     * @return array
     */
    public function missingHtmlFiles()
    {
        return [
            ['file://'.TESTS_DIR.'/fixtures/missing.html'],
        ];
    }
}
