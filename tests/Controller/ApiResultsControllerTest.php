<?php

namespace App\Tests\Controller;

use App\Entity\Message;
use App\Entity\Result;
use App\Entity\User;
use Faker\Factory as FakerFactoryAlias;
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

    /**
     * Test GET /results 404 Not Found
     *
     * @return void
     */
    public function testCGetResultAction404(): void
    {
        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API,
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();

        self::assertEquals(
            Response::HTTP_NOT_FOUND,
            $response->getStatusCode()
        );
        $r_body = (string) $response->getContent();
        self::assertContains(Message::CODE_ATTR, $r_body);
        self::assertContains(Message::MESSAGE_ATTR, $r_body);
        $r_data = json_decode($r_body, true);
        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        self::assertStringContainsString(Message::CODE_ATTR, $r_body);
        self::assertStringContainsString(Message::MESSAGE_ATTR, $r_body);

        self::assertSame(Response::HTTP_NOT_FOUND, $r_data[Message::CODE_ATTR]);
        self::assertSame(Response::$statusTexts[404], $r_data[Message::MESSAGE_ATTR]);
    }


    /**
     * Test POST /results 201 Created
     * @depends testCGetResultAction404
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
     * Test POST /results 422 Unprocessable Entity
     *
     * @param int|null $result
     * @param null|string $email
     * @return void
     * @dataProvider resultProvider422
     */
    public function testPostResultAction422UnprocessableEntity(?int $result, ?string $email): void
    {
        $headers = $this->getTokenHeaders();
        $p_data = [
            Result::RESULT_ATTR => $result,
            User::EMAIL_ATTR => $email,
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
        self::assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $response->getStatusCode()
        );
        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        self::assertStringContainsString(Message::CODE_ATTR, $r_body);
        self::assertStringContainsString(Message::MESSAGE_ATTR, $r_body);
        $r_data = json_decode($r_body, true);
        self::assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $r_data[Message::CODE_ATTR]
        );
        self::assertSame(
            Response::$statusTexts[422],
            $r_data[Message::MESSAGE_ATTR]
        );
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
     * Test PUT /results/{resultId} 404 Not found
     *
     * @return  void
     */
    public function testPutResultAction404Notfound(): void
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

    /**
     * Test PUT /results/{resultId} 422 Unprocessable Entity
     *
     * @param   array $resultEnt result returned by testPutResultAction209ContentReturned()
     * @return  void
     * @depends testPutResultAction209ContentReturned
     */
    public function testPutResultAction422UnprocessableEntity(array $resultEnt): void
    {
        $headers = $this->getTokenHeaders();
        $p_data = [
            // En los datos se envía el id en lugar del resultado
            Result::ID_ATTR => $resultEnt[Result::ID_ATTR]
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
        self::assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $response->getStatusCode()
        );

        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        self::assertStringContainsString(Message::CODE_ATTR, $r_body);
        self::assertStringContainsString(Message::MESSAGE_ATTR, $r_body);
        $r_data = json_decode($r_body, true);
        self::assertSame(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $r_data[Message::CODE_ATTR]
        );
        self::assertSame(
            Response::$statusTexts[422],
            $r_data[Message::MESSAGE_ATTR]
        );
    }

    /**
     * Test DELETE /results/{resultId} 204 No Content
     *
     * @param   array $resultEnt result returned by testPostResultAction201Created()
     * @return  int resultId
     * @depends testPostResultAction201Created
     * @depends testGetResultAction200Ok
     * @depends testCGetResultsAction200Ok
     * @depends testPutResultAction422UnprocessableEntity
     */
    public function testDeleteResultAction204NoContent(array $resultEnt): int
    {
        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_DELETE,
            self::RUTA_API . '/' . $resultEnt['id'],
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();
        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertEmpty((string)$response->getContent());

        return $resultEnt['id'];
    }

    /**
     * Test GET    /results 401 UNAUTHORIZED
     * Test POST   /results 401 UNAUTHORIZED
     * Test GET    /results/{resultId} 401 UNAUTHORIZED
     * Test PUT    /results/{resultId} 401 UNAUTHORIZED
     * Test DELETE /results/{resultId} 401 UNAUTHORIZED
     *
     * @param string $method
     * @param string $uri
     * @dataProvider routeProvider401()
     * @return void
     * @uses \App\EventListener\ExceptionListener
     */
    public function testResultStatus401Unauthorized(string $method, string $uri): void
    {
        self::$client->request(
            $method,
            $uri,
            [],
            [],
            [ 'HTTP_ACCEPT' => 'application/json' ]
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertJson((string) $response->getContent());
        $r_body = (string) $response->getContent();
        self::assertStringContainsString(Message::CODE_ATTR, $r_body);
        self::assertStringContainsString(Message::MESSAGE_ATTR, $r_body);
        $r_data = json_decode($r_body, true);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $r_data[Message::CODE_ATTR]);
        self::assertContains(
            Response::$statusTexts[401],
            $r_data[Message::MESSAGE_ATTR]
        );
    }

    /**
     * Test GET    /results/{resultId} 404 NOT FOUND
     * Test PUT    /results/{resultId} 404 NOT FOUND
     * Test DELETE /results/{resultId} 404 NOT FOUND
     *
     * @param string $method
     * @param int $resultId result id. returned by testDeleteResultAction204NoContent()
     * @dataProvider routeProvider404
     * @return void
     * @depends testDeleteResultAction204NoContent
     */
    public function testResultStatus404NotFound(string $method, int $resultId): void
    {
        $headers = $this->getTokenHeaders();
        self::$client->request(
            $method,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $r_body = (string) $response->getContent();
        self::assertStringContainsString(Message::CODE_ATTR, $r_body);
        self::assertStringContainsString(Message::MESSAGE_ATTR, $r_body);
        $r_data = json_decode($r_body, true);
        self::assertSame(Response::HTTP_NOT_FOUND, $r_data[Message::CODE_ATTR]);
        self::assertSame(Response::$statusTexts[404], $r_data[Message::MESSAGE_ATTR]);
    }

    /**
     * Test POST   /results 403 FORBIDDEN
     * Test PUT    /results/{resultId} 403 FORBIDDEN
     * Test DELETE /results/{resultId} 403 FORBIDDEN
     *
     * @param string $method
     * @param string $uri
     * @dataProvider routeProvider403()
     * @depends testResultStatus404NotFound
     * @return void
     * @uses \App\EventListener\ExceptionListener
     */
    public function testUserStatus403Forbidden(string $method, string $uri): void
    {
        $headers = $this->getTokenHeaders(
            self::$role_user[User::EMAIL_ATTR],
            self::$role_user[User::PASSWD_ATTR]
        );
        self::$client->request($method, $uri, [], [], $headers);
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertJson((string) $response->getContent());
        $r_body = (string) $response->getContent();
        self::assertStringContainsString(Message::CODE_ATTR, $r_body);
        self::assertStringContainsString(Message::MESSAGE_ATTR, $r_body);
        $r_data = json_decode($r_body, true);
        self::assertSame(Response::HTTP_FORBIDDEN, $r_data[Message::CODE_ATTR]);
        self::assertSame(
            '`Forbidden`: you don\'t have permission to access',
            $r_data[Message::MESSAGE_ATTR]
        );
    }

    /**
     * Route provider (expected status: 401 UNAUTHORIZED)
     *
     * @return array [ method, url ]
     */
    public function routeProvider401(): array
    {
        return [
            'cgetAction401'   => [ Request::METHOD_GET,    self::RUTA_API ],
            'getAction401'    => [ Request::METHOD_GET,    self::RUTA_API . '/1' ],
            'postAction401'   => [ Request::METHOD_POST,   self::RUTA_API ],
            'putAction401'    => [ Request::METHOD_PUT,    self::RUTA_API . '/1' ],
            'deleteAction401' => [ Request::METHOD_DELETE, self::RUTA_API . '/1' ],
        ];
    }

    /**
     * Route provider (expected status: 403 FORBIDDEN)
     *
     * @return array [ method, url ]
     */
    public function routeProvider403(): array
    {
        return [
            'postAction403'   => [ Request::METHOD_POST,   self::RUTA_API ],
            'putAction403'    => [ Request::METHOD_PUT,    self::RUTA_API . '/1' ],
            'deleteAction403' => [ Request::METHOD_DELETE, self::RUTA_API . '/1' ],
        ];
    }

    /**
     * Route provider (expected status 404 NOT FOUND)
     *
     * @return array [ method ]
     */
    public function routeProvider404(): array
    {
        return [
            'getAction404'    => [ Request::METHOD_GET ],
            'putAction404'    => [ Request::METHOD_PUT ],
            'deleteAction404' => [ Request::METHOD_DELETE ],
        ];
    }

    /**
     * Result provider -> 422 status code
     *
     * @return array result data
     */
    public function resultProvider422(): array
    {
        $faker = FakerFactoryAlias::create('es_ES');
        $email = $faker->email;
        $result = $faker->randomDigitNotNull;

        return [
            'no_result' => [ null,    $email ],
            'no_email'  => [ $result, null   ],
            'nothing'   => [ null,    null   ],
        ];
    }
}
