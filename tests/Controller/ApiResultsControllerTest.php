<?php

namespace App\Tests\Controller;

use App\Controller\ApiResultsController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiResultsControllerTest
 *
 * @package App\Tests\Controller
 * @group   controllers
 *
 * @coversDefaultClass \App\Controller\ApiResultsControllerTest
 */
class ApiResultsControllerTest extends BaseTestCase
{

    private const RUTA_API = '/api/v1/results';

    public function testPutAction()
    {

    }

    public function test__construct()
    {

    }

    public function testGetAction()
    {

    }

    public function testGetUserResultsAction()
    {

    }

    /**
     * Test OPTIONS /results[/resultId] 204 No Content
     *
     * @covers ::__construct
     * @covers ::optionsAction
     * @return void
     */
    public function testOptionsResultAction204NoContent(): void
    {
        // OPTIONS /api/v1/results
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API
        );
        $response = self::$client->getResponse();
        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));

        // OPTIONS /api/v1/results/{id}
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API . '/' . self::$faker->numberBetween(1, 100)
        );
        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));

    }

    public function testDeleteAction()
    {

    }

    public function testPostAction()
    {

    }

    public function testCgetAction()
    {

    }
}
