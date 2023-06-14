<?php

namespace KhanhArtisan\LaravelBackbone\Testing;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Testing\TestResponse;

class JsonApiTest
{
    protected string $jsonApiDataWrap = 'data';

    protected string $jsonApiIdField = 'id';

    protected bool $dumpErrorResponse = true;
    
    public function __construct(protected TestCase|\Orchestra\Testbench\TestCase $testCase)
    {
        
    }

    /**
     * Basic crud test script
     *
     * @param string $baseUri
     * @param JsonCrudTestData $data
     * @return void
     * @throws \Exception
     */
    public function testBasicCrud(string $baseUri, JsonCrudTestData $data): void
    {
        // Test store
        if (!$data->getStoreUri()) {
            $data->setStoreUri($baseUri);
        }
        $storeResponse = $this->testStore(
            $data->getStoreUri(),
            $data->getStoreData(),
            $data->getExpectedStoreResponseData(),
            $data->getExpectedStoreResponseCode()
        );

        $storeResponseData = json_decode($storeResponse->getContent(), true);
        $storedResourceData = $this->jsonApiDataWrap ? $storeResponseData[$this->jsonApiDataWrap] : $storeResponseData;
        if (!isset($storedResourceData[$this->jsonApiIdField])) {
            throw new \Exception($this->jsonApiIdField.' is not found in resource data.');
        }

        // Test update
        if (!$data->getUpdateUri()) {
            $data->setUpdateUri($baseUri.'/'.$storedResourceData[$this->jsonApiIdField]);
        }
        $updateResponse = $this->testUpdate(
            $data->getUpdateUri(),
            $data->getUpdateData(),
            $data->getExpectedUpdateResponseData(),
            $data->getExpectedUpdateResponseCode()
        );
        $updateResponseData = json_decode($updateResponse->getContent(), true);
        $updatedResourceData = $this->jsonApiDataWrap ? $updateResponseData[$this->jsonApiDataWrap] : $updateResponseData;

        // Test index
        if (!$data->getIndexUri()) {
            $data->setIndexUri($baseUri);
        }

        if (is_null($data->getExpectedIndexResponseData())) {
            $data->setExpectedIndexResponseData($this->jsonApiDataWrap ? [
                'data' => [
                    $updatedResourceData
                ]
            ] : [$updatedResourceData]);
        }

        $this->testIndex(
            $data->getIndexUri(),
            $data->getExpectedIndexResponseData(),
            $data->getExpectedIndexResponseCode()
        );

        // Test show
        if (!$data->getShowUri()) {
            $data->setShowUri($baseUri.'/'.$updatedResourceData[$this->jsonApiIdField]);
        }

        if (is_null($data->getExpectedShowResponseData())) {
            $data->setExpectedShowResponseData($this->jsonApiDataWrap ? [
                'data' => $updatedResourceData
            ] : $updatedResourceData);
        }

        $this->testShow(
            $data->getShowUri(),
            $data->getExpectedShowResponseData(),
            $data->getExpectedShowResponseCode()
        );

        // Test destroy
        if (!$data->getDestroyUri()) {
            $data->setDestroyUri($baseUri.'/'.$updatedResourceData[$this->jsonApiIdField]);
        }

        if (is_null($data->getExpectedDestroyResponseData())) {
            $data->setExpectedDestroyResponseData($this->jsonApiDataWrap ? [
                'data' => $updatedResourceData
            ] : $updatedResourceData);
        }

        $this->testDestroy(
            $data->getDestroyUri(),
            $data->getExpectedDestroyResponseData(),
            $data->getExpectedDestroyResponseCode()
        );

        // Test show after deleted (expect 404)
        $this->testShow($data->getDestroyUri(), null, 404);
    }

    /**
     * Test crud
     *
     * @param JsonCrudTestData $data
     * @return void
     */
    public function testCrud(JsonCrudTestData $data): void
    {
        // Test store
        $this->testStore(
            $data->getStoreUri(),
            $data->getStoreData(),
            $data->getExpectedStoreResponseData(),
            $data->getExpectedStoreResponseCode()
        );

        // Test update
        $this->testUpdate(
            $data->getUpdateUri(),
            $data->getUpdateData(),
            $data->getExpectedUpdateResponseData(),
            $data->getExpectedUpdateResponseCode()
        );

        // Test index
        $this->testIndex(
            $data->getIndexUri(),
            $data->getExpectedIndexResponseData(),
            $data->getExpectedIndexResponseCode()
        );

        // Test show
        $this->testShow(
            $data->getShowUri(),
            $data->getExpectedShowResponseData(),
            $data->getExpectedShowResponseCode()
        );

        // Test destroy
        $this->testDestroy(
            $data->getDestroyUri(),
            $data->getExpectedDestroyResponseData(),
            $data->getExpectedDestroyResponseCode()
        );
    }

    /**
     * Test store
     *
     * @param string $uri
     * @param array $data
     * @param array|null $expectedResponseData
     * @param int $expectedCode
     * @return TestResponse
     */
    public function testStore(string $uri, array $data, ?array $expectedResponseData = null, int $expectedCode = 201): TestResponse
    {
        $response = $this->testCase->postJson($uri, $data);

        try {
            $response->assertStatus($expectedCode);
            $response->assertJson(is_null($expectedResponseData) ? ($this->jsonApiDataWrap ? [$this->jsonApiDataWrap => $data] : $data) : $expectedResponseData);
        } catch (\Exception $e) {
            if ($this->dumpErrorResponse) {
                $this->dumpResponse($response);
            }
            throw $e;
        }

        return $response;
    }

    /**
     * Test update
     *
     * @param string $uri
     * @param array $data
     * @param array|null $expectedResponseData
     * @param int $expectedCode
     * @return TestResponse
     */
    public function testUpdate(string $uri, array $data, ?array $expectedResponseData = null, int $expectedCode = 200): TestResponse
    {
        $response = $this->testCase->patchJson($uri, $data);

        try {
            $response->assertStatus($expectedCode);
            $response->assertJson(is_null($expectedResponseData) ? ($this->jsonApiDataWrap ? [$this->jsonApiDataWrap => $data] : $data) : $expectedResponseData);
        } catch (\Exception $e) {
            if ($this->dumpErrorResponse) {
                $this->dumpResponse($response);
            }
            throw $e;
        }

        return $response;
    }

    /**
     * Test index
     *
     * @param string $uri
     * @param array|null $expectedResponseData
     * @param int $expectedCode
     * @return TestResponse
     */
    public function testIndex(string $uri, ?array $expectedResponseData = null, int $expectedCode = 200): TestResponse
    {
        $response = $this->testCase->getJson($uri);

        try {
            $response->assertStatus($expectedCode);

            if (is_array($expectedResponseData)) {
                $response->assertJson($expectedResponseData);
            }
        } catch (\Exception $e) {
            if ($this->dumpErrorResponse) {
                $this->dumpResponse($response);
            }
            throw $e;
        }

        return $response;
    }

    /**
     * Test show
     *
     * @param string $uri
     * @param array|null $expectedResponseData
     * @param int $expectedCode
     * @return TestResponse
     */
    public function testShow(string $uri, ?array $expectedResponseData = null, int $expectedCode = 200): TestResponse
    {
        $response = $this->testCase->getJson($uri);

        try {
            $response->assertStatus($expectedCode);

            if (is_array($expectedResponseData)) {
                $response->assertJson($expectedResponseData);
            }
        } catch (\Exception $e) {
            if ($this->dumpErrorResponse) {
                $this->dumpResponse($response);
            }
            throw $e;
        }

        return $response;
    }

    /**
     * Test delete
     *
     * @param string $uri
     * @param array|null $expectedResponseData
     * @param int $expectedCode
     * @return TestResponse
     */
    public function testDestroy(string $uri, ?array $expectedResponseData = null, int $expectedCode = 200): TestResponse
    {
        $response = $this->testCase->deleteJson($uri);

        try {
            $response->assertStatus($expectedCode);

            if (is_array($expectedResponseData)) {
                $response->assertJson($expectedResponseData);
            }
        } catch (\Exception $e) {
            if ($this->dumpErrorResponse) {
                $this->dumpResponse($response);
            }
            throw $e;
        }

        return $response;
    }

    protected function dumpResponse(TestResponse $response): void
    {
        dump('--- Dump Response Start ---');
        dump('Status: '.$response->getStatusCode());
        dump('Headers:');
        $response->dumpHeaders();
        dump('Body:');
        $response->dump();
        dump('--- Dump Response End ---');
    }
}
