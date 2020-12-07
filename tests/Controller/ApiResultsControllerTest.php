<?php

namespace App\Tests\Controller;

use App\Entity\Result;
use App\Entity\User;
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
    private const RUTA_API_U = '/api/v1/users';


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

    public function testDeleteAction()
    {

    }

    /**
     * Test POST /results 201 Created
     *
     * @return array result data
     */
    public function testPostResultAction201Created(): array
    {
        // Hay que crear un usuario para asignarle un nuevo resultado
        $user = [
            User::EMAIL_ATTR => self::$faker->email,
            User::PASSWD_ATTR => self::$faker->password,
            User::ROLES_ATTR => [ self::$faker->word ],
        ];

        // Se crea el usuario
        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API_U,
            [],
            [],
            $headers,
            json_encode($user)
        );

        // Se crea el nuevo resultado
        $p_data = [
            Result::RESULT_ATTR=>self::$faker->randomDigitNotNull,
            User::EMAIL_ATTR => $user['email'],
            Result::TIME_ATTR => null
        ];

        // 201
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            $headers,
            json_encode($p_data)
        );

        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->headers->get('Location'));
        self::assertJson($response->getContent());
        $resultEnt = json_decode($response->getContent(), true);
        self::assertNotEmpty($resultEnt['resultEnt']['id']);
        self::assertSame($p_data[User::EMAIL_ATTR], $user[User::EMAIL_ATTR]);
        self::assertSame($p_data[Result::RESULT_ATTR], $resultEnt['resultEnt'][Result::RESULT_ATTR]);

        return $resultEnt['resultEnt'];
    }

    /**
     * Test GET /results 200 Ok
     *
     * @return void
     * @depends testPostResultAction201Created
     */
    public function testCGetResultsAction200Ok(): void
    {
        $headers = $this->getTokenHeaders();
        self::$client->request(Request::METHOD_GET, self::RUTA_API, [], [], $headers);
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->getEtag());
        self::assertJson($response->getContent());
        $results = json_decode($response->getContent(), true);
        self::assertArrayHasKey('results', $results);
    }

    /**
     * Test GET /results 200 Ok (XML)
     *
     * @param   array $resultEnt result returned by testPostResultAction201Created()
     * @return  void
     * @depends testPostResultAction201Created
     */
    public function testCGetAction200XmlOk(array $resultEnt): void
    {
        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API . '/' . $resultEnt['id'] . '.xml',
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->getEtag());
        self::assertTrue($response->headers->contains('content-type', 'application/xml'));
    }
}
