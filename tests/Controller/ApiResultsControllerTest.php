<?php

namespace App\Tests\Controller;

use App\Entity\Message;
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
 * @coversDefaultClass \App\Controller\ApiResultsController
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


    public function testGetUserResultsAction()
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
            User::ROLES_ATTR => [self::$faker->word],
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
            Result::RESULT_ATTR => self::$faker->randomDigitNotNull,
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
     * Test POST /users 400 Bad Request
     *
     * @return  void
     */
    public function testPostResultAction400BadRequest(): void
    {
        $headers = $this->getTokenHeaders();
        $p_data = [
            Result::RESULT_ATTR => self::$faker->randomDigitNotNull,
            User::EMAIL_ATTR => self::$faker->email // Email de un usuario que no existe
        ];

        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            $headers,
            json_encode($p_data)
        );

        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $r_body = $response->getContent();
        self::assertJson($r_body);
        self::assertStringContainsString(Message::CODE_ATTR, $r_body);
        self::assertStringContainsString(Message::MESSAGE_ATTR, $r_body);
        $r_data = json_decode($r_body, true);
        self::assertSame(Response::HTTP_BAD_REQUEST, $r_data[Message::CODE_ATTR]);
        self::assertSame(Response::$statusTexts[400], $r_data[Message::MESSAGE_ATTR]);
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
     * @param array $resultEnt result returned by testPostResultAction201Created()
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

    /**
     * Test GET /results/{resultId} 200 Ok
     *
     * @param array $resultEnt result returned by testPostResultAction201Created()
     * @return  void
     * @depends testPostResultAction201Created
     */
    public function testGetResultAction200Ok(array $resultEnt): void
    {
        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API . '/' . $resultEnt['id'],
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotNull($response->getEtag());
        self::assertJson((string)$response->getContent());
        $getResultEnt = json_decode((string)$response->getContent(), true);
        self::assertSame($resultEnt['id'], $getResultEnt['resultEnt']['id']);
    }

    /**
     * Test PUT /results/{resultId} 209 Content Returned
     *
     * @param array $resultEnt result returned by testPostResultAction201Created()
     * @return  array modified result data
     * @depends testPostResultAction201Created
     */
    public function testPutResultAction209ContentReturned(array $resultEnt): array
    {
        $updatedResult = self::$faker->randomDigitNotNull;
        $headers = $this->getTokenHeaders();
        $p_data = [
            Result::RESULT_ATTR => $updatedResult
        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $resultEnt['id'],
            [],
            [],
            $headers,
            json_encode($p_data)
        );
        $response = self::$client->getResponse();
        self::assertSame(209, $response->getStatusCode());
        self::assertJson($response->getContent());
        $updatedResultEnt = json_decode($response->getContent(), true);
        self::assertSame($resultEnt['id'], $updatedResultEnt['resultEnt']['id']);
        self::assertSame($p_data[Result::RESULT_ATTR], $updatedResultEnt['resultEnt'][Result::RESULT_ATTR]);
        self::assertArrayHasKey(Result::TIME_ATTR, $updatedResultEnt['resultEnt']);

        return $updatedResultEnt['resultEnt'];
    }

    /**
     * Test PUT /results/{resultId} 404 Bad Request
     *
     * @return  void
     */
    public function testPutResultAction404BadRequest(): void
    {
        $updatedResult = self::$faker->randomDigitNotNull;
        $headers = $this->getTokenHeaders();
        $p_data = [
            Result::RESULT_ATTR => $updatedResult
        ];

        $noId = self::$faker->numberBetween(20,30);

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $noId,
            [],
            [],
            $headers,
            json_encode($p_data)
        );
        $response = self::$client->getResponse();
        self::assertSame(404, $response->getStatusCode());

        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        self::assertStringContainsString(Message::CODE_ATTR, $r_body);
        self::assertStringContainsString(Message::MESSAGE_ATTR, $r_body);
        $r_data = json_decode($r_body, true);
        self::assertSame(
            Response::HTTP_NOT_FOUND,
            $r_data[Message::CODE_ATTR]
        );
        self::assertSame(
            Response::$statusTexts[404],
            $r_data[Message::MESSAGE_ATTR]
        );
    }
}
