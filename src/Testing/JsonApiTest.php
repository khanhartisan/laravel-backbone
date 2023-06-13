<?php

namespace KhanhArtisan\LaravelBackbone\Testing;

use Illuminate\Testing\TestResponse;

trait JsonApiTest
{
    protected string $jsonApiDataWrap = 'data';

    protected string $jsonApiIdField = 'id';

    /**
     * Basic crud test script
     *
     * @param string $baseUri
     * @param JsonCrudTestData $data
     * @return void
     * @throws \Exception
     */
    protected function _testBasicCrud(string $baseUri, JsonCrudTestData $data): void
    {
        // Test store
        if (!$data->getStoreUri()) {
            $data->setStoreUri($baseUri);
        }
        $storeResponse = $this->_testStore(
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
        $updateResponse = $this->_testUpdate(
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

        $this->_testIndex(
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

        $this->_testShow(
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

        $this->_testDestroy(
            $data->getDestroyUri(),
            $data->getExpectedDestroyResponseData(),
            $data->getExpectedDestroyResponseCode()
        );

        // Test show after deleted (expect 404)
        $this->_testShow($data->getDestroyUri(), null, 404);
    }

    /**
     * Test crud
     *
     * @param JsonCrudTestData $data
     * @return void
     */
    protected function _testCrud(JsonCrudTestData $data): void
    {
        // Test store
        $this->_testStore(
            $data->getStoreUri(),
            $data->getStoreData(),
            $data->getExpectedStoreResponseData(),
            $data->getExpectedStoreResponseCode()
        );

        // Test update
        $this->_testUpdate(
            $data->getUpdateUri(),
            $data->getUpdateData(),
            $data->getExpectedUpdateResponseData(),
            $data->getExpectedUpdateResponseCode()
        );

        // Test index
        $this->_testIndex(
            $data->getIndexUri(),
            $data->getExpectedIndexResponseData(),
            $data->getExpectedIndexResponseCode()
        );

        // Test show
        $this->_testShow(
            $data->getShowUri(),
            $data->getExpectedShowResponseData(),
            $data->getExpectedShowResponseCode()
        );

        // Test destroy
        $this->_testDestroy(
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
    protected function _testStore(string $uri, array $data, ?array $expectedResponseData = null, int $expectedCode = 201): TestResponse
    {
        $response = $this->postJson($uri, $data);
        $response->assertStatus($expectedCode);
        $response->assertJson(is_null($expectedResponseData) ? ($this->jsonApiDataWrap ? [$this->jsonApiDataWrap => $data] : $data) : $expectedResponseData);

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
    protected function _testUpdate(string $uri, array $data, ?array $expectedResponseData = null, int $expectedCode = 200): TestResponse
    {
        $response = $this->patchJson($uri, $data);
        $response->assertStatus($expectedCode);
        $response->assertJson(is_null($expectedResponseData) ? ($this->jsonApiDataWrap ? [$this->jsonApiDataWrap => $data] : $data) : $expectedResponseData);

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
    protected function _testIndex(string $uri, ?array $expectedResponseData = null, int $expectedCode = 200): TestResponse
    {
        $response = $this->getJson($uri);
        $response->assertStatus($expectedCode);

        if (is_array($expectedResponseData)) {
            $response->assertJson($expectedResponseData);
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
    public function _testShow(string $uri, ?array $expectedResponseData = null, int $expectedCode = 200): TestResponse
    {
        $response = $this->getJson($uri);
        $response->assertStatus($expectedCode);

        if (is_array($expectedResponseData)) {
            $response->assertJson($expectedResponseData);
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
    public function _testDestroy(string $uri, ?array $expectedResponseData = null, int $expectedCode = 200): TestResponse
    {
        $response = $this->deleteJson($uri);
        $response->assertStatus($expectedCode);

        if (is_array($expectedResponseData)) {
            $response->assertJson($expectedResponseData);
        }

        return $response;
    }
}
