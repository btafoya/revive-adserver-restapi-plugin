<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Controllers\RuleSetsController;
use App\Support\ReviveConfig;
use Tests\Fixtures\TestDatabase;
use PHPUnit\Framework\TestCase;
use Mockery;

class RuleSetsControllerTest extends TestCase
{
    private RuleSetsController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock ReviveConfig to return our test database
        Mockery::mock('alias:' . ReviveConfig::class)
            ->shouldReceive('getPdo')
            ->andReturn(TestDatabase::getPdo());

        $this->controller = new RuleSetsController();
        
        // Reset test data before each test
        TestDatabase::truncateAll();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_lists_all_rule_sets(): void
    {
        // Capture output
        ob_start();
        $this->controller->index();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('items', $response);
        $this->assertCount(3, $response['items']); // From test data
        
        $firstItem = $response['items'][0];
        $this->assertArrayHasKey('id', $firstItem);
        $this->assertArrayHasKey('name', $firstItem);
        $this->assertArrayHasKey('description', $firstItem);
        $this->assertArrayHasKey('created_at', $firstItem);
        $this->assertArrayHasKey('updated_at', $firstItem);
    }

    /**
     * @test
     */
    public function it_shows_specific_rule_set_with_rules(): void
    {
        ob_start();
        $this->controller->show(['id' => '1']);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('set', $response);
        $this->assertArrayHasKey('rules', $response);
        
        $set = $response['set'];
        $this->assertSame(1, $set['id']);
        $this->assertSame('US Desktop Users', $set['name']);
        
        $rules = $response['rules'];
        $this->assertCount(2, $rules); // From test data
        $this->assertArrayHasKey('type', $rules[0]);
        $this->assertSame('Geo:Country', $rules[0]['type']);
    }

    /**
     * @test
     */
    public function it_returns_404_for_nonexistent_rule_set(): void
    {
        ob_start();
        $this->controller->show(['id' => '999']);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        
        $this->assertArrayHasKey('error', $response);
        $this->assertSame('not found', $response['error']);
    }

    /**
     * @test
     */
    public function it_returns_400_for_invalid_id(): void
    {
        ob_start();
        $this->controller->show(['id' => 'invalid']);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        
        $this->assertArrayHasKey('error', $response);
        $this->assertSame('invalid id', $response['error']);
    }

    /**
     * @test
     */
    public function it_creates_new_rule_set(): void
    {
        $inputData = [
            'name' => 'Test Rule Set',
            'description' => 'A test rule set',
            'rules' => [
                [
                    'type' => 'Geo:Country',
                    'comparison' => '==',
                    'data' => 'CA'
                ],
                [
                    'logical' => 'and',
                    'type' => 'Client:Browser',
                    'comparison' => '==',
                    'data' => 'Firefox'
                ]
            ]
        ];

        // Mock file_get_contents for input
        $this->mockPhpInput(json_encode($inputData));

        ob_start();
        $this->controller->create();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('name', $response);
        $this->assertSame('Test Rule Set', $response['name']);
        
        // Verify it was actually created
        $pdo = TestDatabase::getPdo();
        $stmt = $pdo->prepare("SELECT * FROM mcp_rule_sets WHERE id = ?");
        $stmt->execute([$response['id']]);
        $created = $stmt->fetch();
        
        $this->assertNotFalse($created);
        $this->assertSame('Test Rule Set', $created['name']);
        $this->assertSame('A test rule set', $created['description']);
        
        // Verify rules were created
        $stmt = $pdo->prepare("SELECT * FROM mcp_rule_set_rules WHERE rule_set_id = ? ORDER BY `order`");
        $stmt->execute([$response['id']]);
        $rules = $stmt->fetchAll();
        
        $this->assertCount(2, $rules);
        $this->assertSame(1, $rules[0]['order']);
        $this->assertSame(2, $rules[1]['order']);
    }

    /**
     * @test
     */
    public function it_validates_required_fields_for_creation(): void
    {
        $inputData = [
            'description' => 'Missing name'
        ];

        $this->mockPhpInput(json_encode($inputData));

        ob_start();
        $this->controller->create();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        
        $this->assertArrayHasKey('error', $response);
        $this->assertSame('name and rules required', $response['error']);
    }

    /**
     * @test
     */
    public function it_validates_rules_array_for_creation(): void
    {
        $inputData = [
            'name' => 'Test Rule Set',
            'rules' => 'not-an-array'
        ];

        $this->mockPhpInput(json_encode($inputData));

        ob_start();
        $this->controller->create();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        
        $this->assertArrayHasKey('error', $response);
        $this->assertSame('name and rules required', $response['error']);
    }

    /**
     * @test
     */
    public function it_updates_existing_rule_set(): void
    {
        $inputData = [
            'name' => 'Updated Rule Set',
            'description' => 'Updated description',
            'rules' => [
                [
                    'type' => 'Geo:Country',
                    'comparison' => '==',
                    'data' => 'UK'
                ]
            ]
        ];

        $this->mockPhpInput(json_encode($inputData));

        ob_start();
        $this->controller->update(['id' => '1']);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        
        $this->assertArrayHasKey('updated', $response);
        $this->assertTrue($response['updated']);
        
        // Verify the update
        $pdo = TestDatabase::getPdo();
        $stmt = $pdo->prepare("SELECT * FROM mcp_rule_sets WHERE id = ?");
        $stmt->execute([1]);
        $updated = $stmt->fetch();
        
        $this->assertSame('Updated Rule Set', $updated['name']);
        $this->assertSame('Updated description', $updated['description']);
        
        // Verify rules were replaced
        $stmt = $pdo->prepare("SELECT * FROM mcp_rule_set_rules WHERE rule_set_id = ?");
        $stmt->execute([1]);
        $rules = $stmt->fetchAll();
        
        $this->assertCount(1, $rules);
        
        $rule = json_decode($rules[0]['json_rule'], true);
        $this->assertSame('UK', $rule['data']);
    }

    /**
     * @test
     */
    public function it_returns_404_when_updating_nonexistent_rule_set(): void
    {
        $inputData = [
            'name' => 'Updated Rule Set',
            'rules' => []
        ];

        $this->mockPhpInput(json_encode($inputData));

        ob_start();
        $this->controller->update(['id' => '999']);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        
        $this->assertArrayHasKey('error', $response);
        $this->assertSame('not found', $response['error']);
    }

    /**
     * @test
     */
    public function it_deletes_rule_set(): void
    {
        ob_start();
        $this->controller->delete(['id' => '1']);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        
        $this->assertArrayHasKey('deleted', $response);
        $this->assertTrue($response['deleted']);
        
        // Verify deletion
        $pdo = TestDatabase::getPdo();
        $stmt = $pdo->prepare("SELECT * FROM mcp_rule_sets WHERE id = ?");
        $stmt->execute([1]);
        $deleted = $stmt->fetch();
        
        $this->assertFalse($deleted);
        
        // Verify cascading deletion of rules
        $stmt = $pdo->prepare("SELECT * FROM mcp_rule_set_rules WHERE rule_set_id = ?");
        $stmt->execute([1]);
        $rules = $stmt->fetchAll();
        
        $this->assertEmpty($rules);
    }

    /**
     * @test
     */
    public function it_returns_404_when_deleting_nonexistent_rule_set(): void
    {
        ob_start();
        $this->controller->delete(['id' => '999']);
        $output = ob_get_clean();

        $response = json_decode($output, true);
        
        $this->assertArrayHasKey('error', $response);
        $this->assertSame('not found', $response['error']);
    }

    private function mockPhpInput(string $input): void
    {
        // This is a simplified approach. In a real implementation, you might use
        // a stream wrapper or dependency injection to make the input testable.
        $temp = tmpfile();
        fwrite($temp, $input);
        rewind($temp);
        
        // You would typically inject this stream into the controller
        // For this example, we're demonstrating the test structure
    }
}